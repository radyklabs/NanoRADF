<?php declare (strict_types=1);

/**
 * URL helpers — global, loaded from the helper registry.
 * --
 * Small functions that turn a path into a full address, so templates and
 * controllers never have to assemble a base URL by hand.
 *
 * Helper files hold functions only — no classes, interfaces, traits, or enums.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

/**
 * Full address for a path inside the application.
 *
 * @since  0.0.1
 * @param  string $path Path relative to the application root.
 * @return string Absolute URL.
 */
function baseurl (string $path = '/'): string
{
    return urlify (interlace ((string) genv ('app.url', 'http://localhost'), $path));
}