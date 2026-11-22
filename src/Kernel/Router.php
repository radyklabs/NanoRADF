<?php declare (strict_types=1);

namespace System\Kernel;

/**
 * Router — matches a request against the central route table and resolves it
 * to a controller action.
 *
 * Table shape lives in `apps/Config/Routes.php`:
 *   - flat: `'GET /hello/{name}' => Controller::class . '::hello'`
 *   - prefix group (the string key IS the prefix; inner keys must be full
 *     `METHOD /path` entries — a bare `/stats` is rejected):
 *       '/admin' => [
 *           'GET /dashboard'   => Ctrl::class . '::index',
 *           'GET /stats/{id}'  => Ctrl::class . '::show',
 *       ]
 *
 * `{name}` is required (`[^/]+`); `{name:regex}` narrows a segment. Exact
 * routes win over parameterised ones within a method. A missing class or
 * action on a matched target is a wiring error, not a miss.
 *
 * @since 0.0.1
 */
final class Router
{
    private function __construct () {}

    /**
     * Compiled groups keyed by upper-case method. Each entry is
     * `[regexp, "Class::action", paramNames]`. Built once per process.
     *
     * @var array<string, list<array{0:string, 1:string, 2:list<string>}>>|null
     */
    private static ?array $groups = null;

    /**
     * Resolve a request. `['status', 'body']` for the first match; an unrouted
     * path yields 404, and a routed path under another verb yields 405 with the
     * real `Allow:` set from the table.
     *
     * @since  0.0.1
     * @param  string $method HTTP method (upper-cased here).
     * @param  string $path   Request path without query string.
     * @return array{status:int, body:string}
     */
    public static function dispatch (string $method, string $path): array
    {
        $method = strtoupper ($method);
        $hit    = self::match ($method, $path);

        if ($hit !== null) {return $hit;}

        // HEAD behaves as GET for router purposes but must not execute a body;
        // the gateway trims it for HEAD, so treat it as a GET candidate.
        if ($method === 'HEAD')
        {
                    // HEAD is served as a GET candidate; the gateway drops the body.
            $head = self::match ('GET', $path);
            if ($head !== null)
            {
                        if (!headers_sent ()) {header ('Content-Length: '.strlen ($head['body']));}
                        return ['status' => $head['status'], 'body' => '', 'headers' => $head['headers']];
            }
        }

        $allowed = self::allowedFor ($path);

        // A routed path under another verb is 405, not 404.
        if ($allowed !== [])
        {
            if (!headers_sent ()) {header ('Allow: '.implode (', ', $allowed));}
            return ['status' => 405, 'body' => '<h1>405 '.statc (405).'</h1><p>The request method is not allowed for this route.</p>'];
        }

        return ['status' => 404, 'body' => '<h1>404 '.statc (404).'</h1><p>The requested page could not be found.</p>'];
    }

    /**
     * Match only — null when no route matches.
     *
     * @since  0.0.1
     * @param  string $method HTTP method.
     * @param  string $path   Request path.
     * @return array{status:int, body:string}|null
     */
    public static function match (string $method, string $path): ?array
    {
        $method = strtoupper ($method);
        $path   = self::normalise ($path);

        // Exact routes win before any pattern, so `/book/create` beats `/book/{id}`.
        $dynamic = [];
        foreach ((self::table ()[$method] ?? []) as $route)
        {
            if ($route[2] === [] && preg_match ($route[0], $path) === 1)
            {
                return self::run ($route[1]);
            }

            if ($route[2] !== []) {$dynamic[] = $route;}
        }

        foreach ($dynamic as $route)
        {
            $params = self::capture ($route[0], $route[2], $path);
            if ($params === null) {continue;}

            return self::run ($route[1], $params);
        }

        return null;
    }

    /**
     * Load and compile the route table.
     *
     * @since  0.0.1
     * @return array<string, list<array{0:string, 1:string, 2:list<string>}>>
     */
    public static function table (): array
    {
        if (self::$groups !== null) {return self::$groups;}

        $file = interlace (appf, 'Config', 'Routes.php');
        $raw  = is_readable ($file) ? require $file : [];

        if (!is_array ($raw))
        {
            @trigger_error ('apps/Config/Routes.php must return an array.', E_USER_WARNING);
            $raw = [];
        }

        $compiled = [];
        foreach ($raw as $key => $value)
        {
            // Prefix group: `'/admin' => [ 'GET /dash' => ..., ... ]` — the string
            // key is the prefix; every inner key must be a full `METHOD /path`.
            if (is_string ($key) && is_array ($value) && str_starts_with ($key, '/'))
            {
                $prefix = self::normalise ($key);

                foreach ($value as $inner => $target)
                {
                    [$verb, $path] = self::splitKey ((string) $inner);
                    $full = rtrim ($prefix, '/').'/'.ltrim ($path, '/');
                    $full = $full === '' ? '/' : $full;

                    self::parseTarget ($target);
                    $compiled[strtoupper ($verb)][] = self::compile ($verb, self::normalise ($full), $target);
                }

                continue;
            }

            // Flat route: 'METHOD /path' => Controller::method
            [$verb, $pattern] = self::splitKey ((string) $key);
            self::parseTarget ($value); // fail-loud for the whole table
            $compiled[strtoupper ($verb)][] = self::compile ($verb, $pattern, $value);
        }

        return self::$groups = $compiled;
    }

    /**
     * Split a route key into `[verb, path]`; requires the `METHOD /path` form.
     *
     * @since  0.0.1
     * @param  string $key Route key as written in the table.
     * @return array{0:string, 1:string}
     */
    private static function splitKey (string $key): array
    {
        [$verb, $path] = explode (' ', $key, 2) + ['', ''];

        if (!in_array (strtoupper (trim ($verb)), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true))
        {
            throw new \InvalidArgumentException ("Route key '{$key}' must start with an HTTP verb (e.g. 'GET /').");
        }

        if (trim ($path) === '' || trim ($path)[0] !== '/')
        {
            throw new \InvalidArgumentException ("Route key '{$key}': path part must start with '/'.");
        }

        return [trim ($verb), trim ($path)];
    }

    /**
     * Re-read the table (explicit rebuild helper).
     *
     * @since  0.0.1
     * @return void
     */
    public static function flush (): void
    {
        self::$groups = null;
    }

    /**
     * Normalise a request path for matching.
     *
     * Duplicate leading separators are collapsed before `parse_url` so that
     * `//a/b` is never read as a scheme-relative URL (authority + path); then
     * percent-decoding, separator collapse, and trailing-slash trim produce the
     * canonical `/a/b` form. Query strings never reach the router.
     *
     * @since  0.0.1
     * @param  string $path Raw path or URI.
     * @return string Canonical request path with one leading slash.
     */
    public static function normalise (string $path): string
    {
        $path = preg_replace ('~^[\\\\/]{2,}~', '/', trim ($path));
        $path = rawurldecode ((string) parse_url ((string) $path, PHP_URL_PATH));
        $path = preg_replace ('~[\\\\/]+~', '/', (string) $path);
        $path = rtrim ((string) $path, '/');

        if ($path === '') {return '/';}
        return str_starts_with ($path, '/') ? $path : '/'.$path;
    }

    /**
     * Methods whose compiled regexp matches `$path`; empty when routed nowhere.
     *
     * @since  0.0.1
     * @param  string $path Raw request path.
     * @return list<string>
     */
    private static function allowedFor (string $path): array
    {
        $path  = self::normalise ($path);
        $found = [];

        foreach (self::table () as $verb => $routes)
        {
            foreach ($routes as $route)
            {
                if (preg_match ($route[0], $path) === 1) {$found[] = $verb; break;}
            }
        }

        return $found;
    }

    /**
     * Compile one raw entry into `[regexp, "Class::action", [params]]`.
     * Malformed entries throw at boot, not on the first hit.
     *
     * @since  0.0.1
     * @param  string     $method Owner verb (for messages).
     * @param  string     $pattern Raw route path pattern, must start with '/'.
     * @param  mixed      $target  `Controller::class . '::' . action`.
     * @return array{0:string, 1:string, 2:array<string, string>}
     */
    private static function compile (string $method, string $pattern, mixed $target): array
    {
        if (!is_string ($target) || substr_count ($target, '::') !== 1)
        {
            throw new \InvalidArgumentException ("Route {$method} {$pattern}: target must be \"Controller::class::action\".");
        }

        if ($pattern === '' || $pattern[0] !== '/')
        {
            throw new \InvalidArgumentException ("Route {$method} {$pattern}: path must start with '/'.");
        }

            self::parseTarget ($target); // fail-loud for bad class/action

        $names  = [];
        $regexp = preg_replace_callback (
            '/\{\s*([A-Za-z_][A-Za-z0-9_]*)(?:\s*:\s*([^{}]+))?\s*\}/',
            function (array $m) use ($method, $pattern, &$names): string
            {
                $name = $m[1];
                $rule = isset ($m[2]) && trim ($m[2]) !== '' ? trim ($m[2]) : '[^/]+';

                // `#` is the delimiter; a literal `#` inside a rule must be escaped.
                if (str_contains ($rule, '#') && !str_contains ($rule, '\#'))
                {
                    throw new \InvalidArgumentException ("Route {$method} {$pattern}: regex fragment '{{$name}}' may not contain '#'.");
                }

                if (@preg_match ("~^(?:{$rule})$~u", 'x') === false)
                {
                    throw new \InvalidArgumentException ("Route {$method} {$pattern}: invalid regex fragment '{{$name}:{$rule}}'.");
                }

                        // Canonical group name avoids collisions between sibling routes
                        // whose declared params differ (e.g. /item/{name} vs /item/{id}).
                        $slot       = count ($names);
                        $group      = 'p'.$slot;
                        $names[$name] = $group;

                        return "(?P<{$group}>{$rule})";
            },
            $pattern
        );

        if (!is_string ($regexp))
        {
            throw new \InvalidArgumentException ("Route {$method} {$pattern}: could not parse path.");
        }

        return ['~^'.$regexp.'$~u', $target, $names];
    }

    /**
     * Capture params from a route match. `$names` maps declared param → regex
     * group slot (`name => p0`), so a collision between sibling patterns cannot
     * lose a value.
     *
     * @since  0.0.1
     * @param  string                $regexp Route regexp including delimiters.
     * @param  array<string, string> $names  Declared param => capture group.
     * @param  string                $path   Normalised request path.
     * @return array<string, string>|null
     */
    private static function capture (string $regexp, array $names, string $path): ?array
    {
        if (preg_match ($regexp, $path, $m) !== 1) {return null;}
        if ($names === []) {return [];}

        $params = [];
        foreach ($names as $name => $group)
        {
            $params[$name] = rawurldecode ((string) ($m[$group] ?? ''));
        }

        return $params;
    }

    /**
     * Split a `"Class::method"` target and validate both halves exist.
     *
     * @since  0.0.1
     * @param  string $target "Class::method".
     * @return list{0:class-string, 1:string}
     */
    private static function parseTarget (mixed $target): array
    {
        [$class, $action] = explode ('::', (string) $target) + [1 => null];

        if ($action === null || $action === '' || !class_exists ($class))
        {
            throw new \RuntimeException ("Route target class not exist: {$target}");
        }

        if (!method_exists ($class, $action))
        {
            throw new \RuntimeException ("Route target method '{$action}' not exist on {$class}.");
        }

        return [$class, $action];
    }

    /**
     * Invoke the matched target and normalise its answer. Parameters are passed
     * positionally, in placeholder-declaration order.
     *
     * @since  0.0.1
     * @param  string                     $target "Class::action".
     * @param  array<string, string>|null $params Named path params, decoded.
     * @return array{status:int, body:string, headers:list<string>}
     */
    private static function run (string $target, ?array $params = null): array
    {
        [$class, $action] = self::resolve ($target);
            $controller = new $class ();

        // Gate runs before the action; Phase 5 overrides `authorize()` on
        // private/api controllers.
        if ($controller instanceof Controller && !$controller->authorize ())
        {
                $body = $controller->surface === 'api'
                    ? json_encode (['ok' => false, 'error' => 'unauthenticated'], JSON_UNESCAPED_SLASHES)
                    : '<h1>403 Forbidden</h1><p>You are not allowed to access this page.</p>';

                return [
                    'status'  => $controller->surface === 'api' ? 401 : 403,
                    'body'    => $body,
                    'headers' => $controller->surface === 'api' ? ['Content-Type: application/json; charset=utf-8'] : [],
                ];
        }

        // Route params arrive as decoded strings, positional in declaration order.
        $response = $params === null || $params === []
            ? $controller->{$action}()
            : $controller->{$action}(...array_values ($params));

        if (is_array ($response))
        {
            return [
                'status'  => (int) ($response['status'] ?? 200),
                'body'    => (string) ($response['body'] ?? ''),
                'headers' => $response['headers'] ?? [],
            ];
        }

        return ['status' => 200, 'body' => (string) $response];
    }

    /**
     * Resolve a `Class::action` target to a verified pair.
     *
     * @since  0.0.1
     * @param  string $target "Class::action".
     * @return list{0:class-string, 1:string}
     */
    private static function resolve (string $target): array
    {
        $pair = explode ('::', $target);
        $class = $pair[0];
        $action = $pair[1] ?? '';

        if (!class_exists ($class))
        {
            throw new \RuntimeException ("Route target class not found: {$target}");
        }

        if ($action === '' || !method_exists ($class, $action))
        {
            throw new \RuntimeException ("Route action '{$action}' not exist on {$class}.");
        }

        return [$class, $action];
    }
}
