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
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
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