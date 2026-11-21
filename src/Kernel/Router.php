<?php declare (strict_types=1);

namespace System\Kernel;

/**
 * Router — matches a request against the central route table and resolves it
 * to a controller action.
 *
 * Routes come from `apps/Config/Routes.php` (see that file for the table shape).
 * Each entry maps one method + path to a `[class, action]` target; `{name}`
 * segments are required params and `{name:regex}` adds a per-segment pattern,
 * tested after URL-decoding so `%2F`/`%20` cannot smuggle a path break.
 *
 * Within a method, exact routes win before parameterised ones, matched in
 * declaration order. A missing class or method on a matched target is a fatal
 * wiring error, not a routing miss.
 *
 * @since 0.0.1
 */
final class Router
{
    private function __construct () {}

    /**
     * Compiled route groups keyed by upper-case method. Each entry is
     * `[regexp, "Class::action", paramNames]`. Populated once per process.
     *
     * @var array<string, list<array{0:string, 1:string, 2:list<string>}>>|null
     */
    private static ?array $groups = null;

    /**
     * Resolve a request. Returns a response array (`status`, `body`) for the
     * first match; an unmatched path yields 404, a path routed under another
     * method yields 405.
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

        // A routed path under another verb is 405, not 404. `Allow` is only set
        // when headers are still uncommitted.
        if (self::hasPath ($path))
        {
            if (!headers_sent ()) {header ('Allow: GET');}
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
        foreach ($raw as $method => $routes)
        {
            if (!is_string ($method) || !is_array ($routes)) {continue;}

            $method = strtoupper ($method);
            foreach ($routes as $path => $route)
            {
                // Entries are `'/{slug}' => [Controller::class, 'action']`.
                $compiled[$method][] = self::compile ($method, (string) $path, $route);
            }
        }

        return self::$groups = $compiled;
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
     * True when any compiled route of any method matches `$path` — used to
     * tell 405 from 404.
     *
     * @since  0.0.1
     * @param  string $path Raw request path.
     * @return bool
     */
    private static function hasPath (string $path): bool
    {
        $path = self::normalise ($path);
        foreach (self::table () as $routes)
        {
            foreach ($routes as $route)
            {
                if (preg_match ($route[0], $path) === 1) {return true;}
            }
        }

        return false;
    }


    /**
     * Compile one raw group entry into `[regexp, "Class::action", [params]]`.
     * Malformed entries throw during boot, not on the first hit.
     *
     * @since  0.0.1
     * @param  string     $method Owner method (for messages).
     * @param  string|int $index  Path key or list index (for messages).
     * @param  mixed  $route  Raw `[Controller::class, 'action']`.
     * @return array{0:string, 1:string, 2:list<string>}
     */
    private static function compile (string $method, string|int $index, mixed $route): array
    {
        if (!is_array ($route) || count ($route) !== 2
            || !is_string ($route[0]) || !is_string ($route[1]))
        {
            throw new \InvalidArgumentException ("Route {$method}#{$index}: expected [Controller::class, 'action'].");
        }

        [$class, $action] = $route;
        $pattern = (string) $index;

        if ($pattern === '' || $pattern[0] !== '/')
        {
            throw new \InvalidArgumentException ("Route {$method}#{$index}: path '{$pattern}' must start with '/'.");
        }

        $names = [];
        $regexp = preg_replace_callback (
            '/\{\s*([A-Za-z_][A-Za-z0-9_]*)(?:\s*:\s*([^{}]+))?\s*\}/',
            function (array $m) use ($method, $index, $pattern, &$names): string
            {
                $name = $m[1];
                $rule = isset ($m[2]) && trim ($m[2]) !== ''
                    ? trim ($m[2])
                    : '[^/]+';

                // `#` is the delimiter; a literal `#` inside a rule must be escaped.
                if (str_contains ($rule, '#') && !str_contains ($rule, '\#'))
                {
                    throw new \InvalidArgumentException ("Route {$method}#{$index}: regex fragment '{{$name}}' may not contain '#'.");
                }

                $check = @preg_match ("~^(?:{$rule})$~u", 'x');
                if ($check === false)
                {
                    throw new \InvalidArgumentException ("Route {$method}#{$index}: invalid regex fragment '{{$name}:{$rule}}'.");
                }

                $names[] = $name;
                return "(?P<{$name}>{$rule})";
            },
            $pattern
        );

        if (!is_string ($regexp))
        {
            throw new \InvalidArgumentException ("Route {$method}#{$index}: could not parse path '{$pattern}'.");
        }

        return
        [
            '~^'.$regexp.'$~u',
            $class.'::'.$action,
            $names,
        ];
    }

    /**
     * Test a path against one route regexp. Returns decoded params, `[]` for
     * an exact match, or null on no match.
     *
     * @since  0.0.1
     * @param  string       $regexp Route regexp including delimiters.
     * @param  list<string> $names  Param names in capture order.
     * @param  string       $path   Normalised request path.
     * @return array<string, string>|null
     */
    private static function capture (string $regexp, array $names, string $path): ?array
    {
        if (preg_match ($regexp, $path, $m) !== 1) {return null;}
        if ($names === []) {return [];}

        $params = [];
        foreach ($names as $name)
        {
            $params[$name] = rawurldecode ((string) ($m[$name] ?? ''));
        }

        return $params;
    }

    /**
     * Invoke the matched target and enforce the response contract.
     *
     * @since  0.0.1
     * @param  string                     $target "Class::action".
     * @param  array<string, string>|null $params Named path params, decoded.
     * @return array{status:int, body:string}
     */
        /**
     * Invoke the matched target and normalise its answer. Parameters are
     * passed positionally, in placeholder-declaration order.
     *
     * @since  0.0.1
     * @param  string                     $target "Class::action".
     * @param  array<string, string>|null $params Named path params, decoded.
     * @return array{status:int, body:string}
     */
    private static function run (string $target, ?array $params = null): array
    {
        $parts = explode ('::', $target);
        $class = $parts[0];
        $action = $parts[1] ?? '';

        if (!class_exists ($class))
        {
            throw new \RuntimeException ("Router target class not found: {$target}");
        }

        if ($action === '' || !method_exists ($class, $action))
        {
            throw new \RuntimeException ("Router action '{$action}' does not exist on {$class}.");
        }

        $controller = new $class ();

        // Surface gate runs before the action; Phase 5 overrides `authorize()`
        // on private/api controllers.
        if ($controller instanceof Controller && !$controller->authorize ())
        {
            return $controller->surface === 'api'
                ? ['status' => 401, 'body' => (string) json_encode (['error' => 'unauthenticated'], JSON_UNESCAPED_SLASHES)]
                : ['status' => 403, 'body' => '<h1>403 Forbidden</h1><p>You are not allowed to access this page.</p>'];
        }

        $response = $params === null || $params === []
            ? $controller->{$action}()
            : $controller->{$action}(...array_values ($params));

        if (is_array ($response))
        {
            return ['status' => (int) ($response['status'] ?? 200), 'body' => (string) ($response['body'] ?? '')];
        }

        return ['status' => 200, 'body' => (string) $response];
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
}