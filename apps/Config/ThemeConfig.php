<?php declare (strict_types=1);

/**
 * Which theme each side of the application wears.
 * --
 * The framework sets a default theme per surface in
 * `src/Config/ThemeConfig.php`. Names written here replace those defaults, one
 * surface at a time, so you can restyle the backend without touching the
 * public site.
 *
 * - `public`: the theme visitors see, taken from `public/themes/<name>`.
 * - `private`: the theme staff see, taken from `apps/Themes/<name>`.
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
    'default' => [
        'private'   => 'admin',
    ],
];