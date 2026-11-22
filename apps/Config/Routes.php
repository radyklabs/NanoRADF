<?php declare (strict_types=1);

/**
 * Central route table — single source of truth for URL → handler.
 *
 * Flat form: `'GET /hello/{name}' => \App\Controllers\HomeController::class . '::hello'`
 * Prefix group form: '/admin' => ['prefix' => '/admin', 'routes' => [ 'GET /dashboard' => ...]]
 *   — inner keys may be `METHOD /path` or bare `/path` (verb defaults to GET).
 *
 * `{name}` segments are required and captured as `[^/]+`; `{name:regex}`
 * narrows them. Exact routes win over parameterised ones within a method;
 * params reach the action positionally in placeholder-declaration order.
 *
 * @since 0.0.1
 */

return [
    'GET /'             => App\Controllers\HomeController::class . '::index',
    'GET /hello/{name}' => App\Controllers\HomeController::class . '::hello',
    'GET /ping'         => App\Controllers\PingController::class . '::index',
    'GET /status/{code:[45]\d\d}' => App\Controllers\PingController::class . '::fail',
];
