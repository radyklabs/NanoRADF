<?php declare(strict_types=1);

/**
 * Phase 1 route smoke check (decision D7: tests live in /tests).
 * Run from anywhere: php tests/route_check.php
 *
 * Writes a temporary Routes.php to exercise the real table shape, then restores.
 * Covers flat `METHOD /path` keys and prefix groups, params, regex, method
 * mismatch, normalisation, and malformed targets. Pass = exit 0.
 */

chdir (dirname (__DIR__) . '/public');
require dirname (__DIR__) . '/public/index.php';

use System\Kernel\Router;

// Probe controller loaded through the autoloader; nothing in phase-3 can mask
// router behaviour.
if (!class_exists (RouteProbeController::class))
{
    eval ('namespace { final class RouteProbeController extends \System\Controllers\PublicController { public function index (): array { return ["status" => 200, "body" => "ok"]; } public function show (string $name): array { return ["status" => 200, "body" => "show ".$name]; } public function digits (string $n): array { return ["status" => 200, "body" => "num ".$n]; } } }');
}

$routesFile = interlace (appf, 'Config', 'Routes.php');
$backup     = file_get_contents ($routesFile);
$cluster    = '\\RouteProbeController';
$fixture    = "<?php\nreturn [\n"
    ."        'GET /'                         => {$cluster}::class.'::index',\n"
    ."        'GET /hello/{name}'             => {$cluster}::class.'::show',\n"
    ."        'GET /hello-digits/{name:\\d+}' => {$cluster}::class.'::digits',\n"
    ."        'POST /submit'                  => {$cluster}::class.'::index',\n"
    ."        '/admin' => [\n"
    ."            'GET /dashboard' => {$cluster}::class.'::index',\n"
    ."            'GET /stats/{id}' => {$cluster}::class.'::digits',\n"
    ."        ],\n"
    ."];\n";

file_put_contents ($routesFile, $fixture);

register_shutdown_function (function () use ($routesFile, $backup): void
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

$check ('exact "/" dispatches', Router::dispatch ('GET', '/') === ['status' => 200, 'body' => 'ok', 'headers' => []]);
$check ('param captured', Router::dispatch ('GET', '/hello/world')['body'] === 'show world');
$check ('regex rejects non-digit', Router::dispatch ('GET', '/hello-digits/abc')['status'] === 404);
$check ('regex accepts digit', Router::dispatch ('GET', '/hello-digits/123')['body'] === 'num 123');

$check ('group flat key', Router::dispatch ('GET', '/admin/dashboard')['body'] === 'ok');
$check ('group verb enforced', Router::dispatch ('GET', '/admin/stats/7')['body'] === 'num 7');
$check ('group wrong verb is miss', Router::dispatch ('POST', '/admin/stats/7')['status'] === 405);

$check ('POST route', Router::dispatch ('POST', '/submit')['status'] === 200);
$check ('DELETE / only => 405', Router::dispatch ('DELETE', '/')['status'] === 405);
$check ('unknown path is 404', Router::dispatch ('GET', '/missing')['status'] === 404);
$check ('HEAD falls back to GET', Router::dispatch ('HEAD', '/hello/x')['status'] === 200);

$check ('normalises duplicate/trailing slashes',
    Router::dispatch ('GET', '//hello/nano/')['body'] === 'show nano');

file_put_contents ($routesFile, "<?php\nreturn ['GET /bad' => '{RouteProbeController::class}'];\n");
Router::flush ();
try {Router::dispatch ('GET', '/bad'); $check ('malformed target throws', false);}
catch (RuntimeException $th) {$check ('malformed target throws', true);}

file_put_contents ($routesFile, $backup);
Router::flush ();

echo $pass ? "ROUTE-CHECK-PASS\n" : "ROUTE-CHECK-FAIL\n";
exit ($pass ? 0 : 1);
