<?php declare (strict_types=1);

/**
 * The framework's default theme for each side of the application.
 * --
 * One name per surface, used whenever the application has not chosen something
 * else. Override either surface in `apps/Config/ThemeConfig.php`; leaving a key
 * out there keeps the default written here.
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
        'public'    => 'default',
        'private'   => 'default',
    ],
];