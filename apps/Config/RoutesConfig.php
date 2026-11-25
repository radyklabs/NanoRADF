<?php declare (strict_types=1);

/**
 * Every URL the application answers, and who answers it.
 * --
 * One table, one place to look. Each line pairs a method and path with the
 * controller action that handles it, so adding a page means adding a line here
 * rather than hunting through the code.
 *
 * Two ways to write an entry:
 *
 * - Flat, for a single address:
 *   `'GET /hello/{name}' => 'App\Controllers\HomeController::hello'`
 * - Grouped under a shared prefix, so the prefix is not repeated:
 *   `'/admin' => ['prefix' => '/admin', 'routes' => ['GET /dashboard' => ...]]`
 *   Inner keys may carry the method or leave it out, in which case GET is
 *   assumed.
 *
 * A `{name}` part of the path is required and matches one segment;
 * `{name:regex}` narrows what that segment may be. When two entries could
 * match the same request, the one without placeholders wins. Captured values
 * are handed to the action in the order they appear in the path.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

return [
    'GET /'             => 'App\Controllers\WelcomeController::index',
    'GET /home'         => 'App\Controllers\HomeController::index',
    'GET /hello/{name}' => 'App\Controllers\HomeController::hello',
    'GET /page/{line}'  => 'App\Controllers\HomeController::page',
    'GET /ping'         => 'App\Controllers\PingController::index',
    'GET /welcome'      => 'App\Controllers\WelcomeController::index',
    'GET /status/{code:[45]\d\d}' => 'App\Controllers\PingController::fail',
];
