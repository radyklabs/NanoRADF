<?php declare (strict_types=1);

/**
 * Central route table — single source of truth for URL → handler.
 *
 * Shape: `[ 'METHOD' => [ '/path/{param}' => [\Controller::class, 'action'], … ] ]`.
 * A `{name}` segment is required and captured as `[^/]+`; `{name:regex}`
 * narrows it (matched after URL-decoding, so `%2F` cannot escape a segment).
 * Within a method, exact paths must be declared before parameterised ones
 * that could swallow them. Params reach the action positionally in
 * declaration order.
 *
 * @since 0.0.1
 */

return [
    // 'GET' => [
    //     '/'            => [App\Controllers\HomeController::class, 'index'],
    //     '/welcome'     => [Modules\Welcome\Controllers\WelcomeController::class, 'index'],
    // ],
];
