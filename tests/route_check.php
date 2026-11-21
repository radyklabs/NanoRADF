<?php declare(strict_types=1);

/**
 * Phase 1 route-match smoke check (decision D7: tests live in /tests).
 * Run from anywhere: php tests/route_check.php
 *
 * Boots the real entry chain, loads System\Kernel\Router, and asserts:
 *   - exact route wins over a parameterised one,
 *   - required params captured + regex constrained,
 *   - method mismatch on a routed path is 405 (POST /),
 *   - unrouted path is 404,
 *   - bad controller target throws.
 * Pass = exit 0.
 */

chdir (dirname (__DIR__) . '/public');
require dirname (__DIR__) . '/public/index.php';

use System\Kernel\Router;

// A throwaway controller so no phase-3 file can mask router behaviour.
if (!class_exists (RouteProbeController::class))
{
    eval ('namespace { final class RouteProbeController extends \System\Controllers\PublicController { public function index (): array { return ["status" => 200, "body" => "ok"]; } public function show (string $name): array { return ["status" => 200, "body" => "show ".$name]; } } }');
}

$routesFile = interlace (appf, 'Config', 'Routes.php');
$expected   = "<?php\nreturn [\n    'GET' => [\n        '/'                    => [\\{cls}::class, 'index'],\n        '/hello/{name}'        => [\\{cls}::class, 'show'],\n        '/hello-digits/{name:\\d+}' => [\\{cls}::class, 'show'],\n    ],\n];\n";
$backup     = file_get_contents ($routesFile);
file_put_contents ($routesFile, str_replace ('{cls}', RouteProbeController::class, $expected));

register_shutdown_function (function () use ($routesFile, $backup)
{
    file_put_contents ($routesFile, $backup);
});

Router::flush ();

$pass  = true;
$check = function (string $label, bool $cond) use (&$pass): void
{
    echo ($cond ? 'ok  ' : 'FAIL')." {$label}\n";
    if (!$cond) {$pass = false;}
};

// Exact route wins over `/hello/{name}` for the same shape.
$check ('exact "/" dispatches', Router::dispatch ('GET', '/') === ['status' => 200, 'body' => 'ok']);

// Param route captures + passes positionally.
$r = Router::dispatch ('GET', '/hello/nanoradfw');
$check ('param captured', $r['status'] === 200 && $r['body'] === 'show nanoradfw');

// Regex-constrained variant rejects non-digits with 404 (falls through).
$check ('regex constraint rejects non-digit', Router::dispatch ('GET', '/hello-digits/abc')['status'] === 404);
$check ('regex constraint accepts digits', Router::dispatch ('GET', '/hello-digits/123')['body'] === 'show 123');

// Method mismatch on a routed path → 405; unknown path → 404.
$check ('wrong method is 405', Router::dispatch ('POST', '/')['status'] === 405);
$check ('unknown path is 404', Router::dispatch ('GET', '/missing')['status'] === 404);

// Trailing slash and duplicate slashes normalise to the canonical path.
$check ('trailing slash normalises',
    Router::dispatch ('GET', '//hello/nano/')['body'] === 'show nano'
);

// Malformed target throws (missing class).
file_put_contents ($routesFile, "<?php\nreturn ['GET' => ['/' => [\\Nope\\Missing::class, 'index']]];\n");
Router::flush ();
try {Router::dispatch ('GET', '/'); $check ('missing target throws', false);}
catch (RuntimeException $th) {$check ('missing target throws', true);}

file_put_contents ($routesFile, $backup);
Router::flush ();

echo $pass ? "ROUTE-CHECK-PASS\n" : "ROUTE-CHECK-FAIL\n";
exit ($pass ? 0 : 1);
