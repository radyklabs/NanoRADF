<?php declare (strict_types=1);

/**
 * Explicit module registry — controls which modules are loaded and their access mode.
 * --
 * Only modules listed here with enabled=true will have their routes registered.
 * Access modes:
 *   - 'public'  : routes registered, accessible via HTTP
 *   - 'internal': no routes registered, but classes autoloadable for internal use
 *   - 'disabled': no routes, but classes still autoloadable (PSR-4)
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
    'Welcome' => [
        'enabled' => true,
        'access'  => 'public', // 'public' | 'internal' | 'disabled'
    ],
];