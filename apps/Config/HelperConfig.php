<?php declare (strict_types=1);

/**
 * The application's additions to the helper registry.
 * --
 * Helpers are plain functions the whole application can call. The framework
 * keeps its own registry in `src/Config/HelperConfig.php`; this file is the
 * application's counterpart, meant for the helpers your own code adds on top.
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

return [];
