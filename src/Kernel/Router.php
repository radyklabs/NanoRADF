<?php declare (strict_types=1);

/**
 * Router — matches HTTP requests against the central route table.
 * --
 * Resolves incoming method + path combinations to controller actions.
 * Supports flat routes (`GET /hello/{name}`) and prefix groups
 * (`/admin` → `['GET /dashboard' => ...]`). Parameter placeholders use
 * `{name}` or `{name:regex}`; exact routes always win over patterns.
 * Unhandled paths return 404, mismatched verbs return 405 with an Allow header.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Kernel;

use RuntimeException;
use InvalidArgumentException;
use function System\interlace;
use function System\fl;
use function System\statc;
use function System\genv;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

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
     * Resolve the current HTTP request against the route table.
     *
     * Returns an array with `status`, `body`, and optional `headers`. On 404/405,
     * throws a RuntimeException so the Dispatcher can render themed error pages.
     * HEAD requests are served as GET candidates with body stripped afterward.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string $method HTTP method (upper-cased).
     * @param     string $path   Request path without query string.
     * @param     bool   $throw  Throw on 404/405 instead of returning?
     * @return    array{status:int, body:string, headers:list<string>}
     */
    public static function dispatch (string $method, string $path, bool $throw = false): array
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
            if ($throw) {throw new RuntimeException ("{$method} {$path} → 405");}
            return ['status' => 405, 'body' => '<h1>405 '.statc (405).'</h1><p>The request method is not allowed for this route.</p>'];
        }

        if ($throw) {throw new RuntimeException ("{$method} {$path} → 404");}
        return ['status' => 404, 'body' => '<h1>404 '.statc (404).'</h1><p>The requested page could not be found.</p>'];
    }

    /**
     * Match only — returns null when no route matches the given method + path.
     *
     * Exact routes are checked first; parameterised routes follow. Parameters
     * are decoded before being passed to the controller action.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string $method HTTP method.
     * @param     string $path   Request path.
     * @return    array{status:int, body:string, headers:list<string>}|null
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
         * Load, compile, and cache the route table from apps/Config/RoutesConfig.php
         * and all enabled modules' Config/RoutesConfig.php.
     *
     * Parses both flat entries (`'GET /hello/{name}' => Ctrl::method`) and prefix
     * groups (`'/admin' => ['GET /dashboard' => ...]`). Malformed entries throw at
     * boot time, not on the first hit. Results are cached in `$groups` per process.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    array<string, list<array{0:string, 1:string, 2:list<string>}>>
     */
    public static function table (): array
    {
        if (self::$groups !== null) {return self::$groups;}

            $raw = [];

            // 1. Main app routes (apps/Config/RoutesConfig.php)
            $appFile = interlace (configfapp, fl ('RoutesConfig'));
            if (is_readable ($appFile))
            {
                $appRoutes = require $appFile;
                if (is_array ($appRoutes)) {$raw = array_merge ($raw, $appRoutes);}
            }

            // 2. Module routes (modules/*/Config/RoutesConfig.php) — only when app.modules=true
            if (genv ('app.modules') === true && defined ('modulesf') && is_dir (modulesf))
            {
                $moduleDirs = glob (interlace (modulesf, '*'), GLOB_ONLYDIR);
                foreach ($moduleDirs as $moduleDir)
                {
                    $moduleName = basename ($moduleDir);

                                // Check Manifest.json for access control
                                // access: "public" (default), "internal", "both", "disabled"
                                $manifestFile = interlace ($moduleDir, 'Manifest.json');
                                $manifest = is_readable ($manifestFile) ? json_decode (file_get_contents ($manifestFile), true) : [];
                                $access = $manifest['access'] ?? 'public';

                                if ($access === 'disabled') {continue;} // Skip disabled modules entirely

                    $moduleConfig = interlace ($moduleDir, 'Config', 'RoutesConfig.php');
                    if (is_readable ($moduleConfig))
                    {
                        $moduleRoutes = require $moduleConfig;
                                    if (is_array ($moduleRoutes))
                                    {
                                        // Build routes based on access mode
                                        $finalRoutes = [];

                                        if ($access === 'public' || $access === 'both')
                                        {
                                            // Public routes as-is
                                            $finalRoutes = array_merge($finalRoutes, $moduleRoutes);
                                        }

                                        if ($access === 'internal' || $access === 'both')
                                        {
                                            // Internal routes prefixed with /_internal/
                                            $prefixed = [];
                                            foreach ($moduleRoutes as $key => $value)
                                            {
                                                if (is_string ($key) && is_array ($value) && str_starts_with ($key, '/'))
                                                {
                                                    // Prefix group
                                                    $prefixed['/_internal'.$key] = $value;
                                                }
                                                else
                                                {
                                                    // Flat route
                                                    [$verb, $path] = self::splitKey ((string) $key);
                                                    $prefixed[$verb.' /_internal'.$path] = $value;
                                                }
                                            }
                                            $finalRoutes = array_merge($finalRoutes, $prefixed);
                                        }

                                        $raw = array_merge ($raw, $finalRoutes);
                                    }
                    }
                }
            }

        if (!is_array ($raw))
        {
                @trigger_error ('Route config must return an array.', E_USER_WARNING);
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
                    throw new InvalidArgumentException ('route_config', 500);
        }

        if (trim ($path) === '' || trim ($path)[0] !== '/')
        {
                    throw new InvalidArgumentException ('route_config', 500);
        }

        return [trim ($verb), trim ($path)];
    }

    /**
     * Rebuild the compiled route table by clearing the cache.
     *
     * Use after modifying RoutesConfig.php during development. Does not reload
     * the config file — only resets the in-memory compiled form.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    void
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
                    throw new InvalidArgumentException ('route_config', 500);
        }

        if ($pattern === '' || $pattern[0] !== '/')
        {
                    throw new InvalidArgumentException ('route_config', 500);
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
                                    throw new InvalidArgumentException ('route_config', 500);
                }

                if (@preg_match ("~^(?:{$rule})$~u", 'x') === false)
                {
                                    throw new InvalidArgumentException ('route_config', 500);
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
                    throw new InvalidArgumentException ('route_config', 500);
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
                    throw new RuntimeException ('route_target', 500);
        }

        if (!method_exists ($class, $action))
        {
                    throw new RuntimeException ('route_target', 500);
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
                $isApi = $controller->getSurface () === 'api';

                $body = $isApi
                    ? json_encode (['ok' => false, 'error' => 'unauthenticated'], JSON_UNESCAPED_SLASHES)
                    : '<h1>403 Forbidden</h1><p>You are not allowed to access this page.</p>';

                return [
                    'status'  => $isApi ? 401 : 403,
                    'body'    => $body,
                    'headers' => $isApi ? ['Content-Type: application/json; charset=utf-8'] : [],
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
                    throw new RuntimeException ('route_target', 500);
        }

        if ($action === '' || !method_exists ($class, $action))
        {
                    throw new RuntimeException ('route_target', 500);
        }

        return [$class, $action];
    }
}
