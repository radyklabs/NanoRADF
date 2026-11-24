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
 * - `resolver`: optional class name implementing `ThemeResolverInterface`. When
 *   set, it decides the theme first and the `default` map is the fallback. This
 *   is the seam for a database-backed or per-user theme in a later phase; leave
 *   it `null` while config is the only source.
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

    // Optional resolver hook for Phase 5 (session/auth). When set, the
    // resolver is called with the surface name and its return value wins
    // over the `default` map above. Leave null to keep config as the
    // single source of truth.
    'resolver' => null,
];