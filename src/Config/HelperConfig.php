<?php declare (strict_types=1);

/**
 * The framework's own helper registry.
 * --
 * Helpers are plain functions the whole application can call. This is the list
 * the framework ships with; it is loaded before any class is autoloaded, so the
 * functions are ready by the time your code runs. Application-level helpers go
 * in `apps/Config/HelperConfig.php`.
 *
 * Each entry is a file stem: `utilities` points to
 * `src/Helper/UtilitiesHelper.php`. A helper file holds functions only — no
 * classes, interfaces, traits, or enums.
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
    'uri',
    'config',
    'utilities',
    'theme',
];
