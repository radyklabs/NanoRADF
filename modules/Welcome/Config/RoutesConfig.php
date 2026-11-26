<?php declare (strict_types=1);

/**
 * Welcome module routes — auto-discovered and merged into the main route table.
 * --
 * Each module can define its own routes. The Router merges them at boot time.
 * Keys follow the same format as apps/Config/RoutesConfig.php:
 * - Flat: 'GET /path' => 'Modules\Welcome\Controllers\WelcomeController::action'
 * - Grouped: '/welcome' => ['GET /' => ..., 'GET /about' => ...]
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require realpath (__DIR__.'/../../').DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

return [
    // Module routes under /welcome prefix
    '/welcome' => [
        'GET /'           => 'Modules\Welcome\Controllers\WelcomeController::index',
        'GET /about'      => 'Modules\Welcome\Controllers\WelcomeController::about',
        'GET /features'   => 'Modules\Welcome\Controllers\WelcomeController::features',
    ],
];