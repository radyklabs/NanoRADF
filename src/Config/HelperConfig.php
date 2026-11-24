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
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

return [
    'uri',
    'config',
    'utilities',
    'theme',
];
