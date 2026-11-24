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
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
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