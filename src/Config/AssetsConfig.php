<?php declare (strict_types=1);

/**
 * The framework's built-in asset map.
 * --
 * Everything the framework knows about its own styles and scripts lives here:
 * the registries it uses for each surface, the queues it fills for each
 * surface, and the URL patterns that are allowed to reach a file on disk.
 * Application-level additions belong in `apps/Config/AssetsConfig.php`, which
 * is layered on top of this file.
 *
 * - `register_styles` / `register_scripts`: explicit asset entries with an
 *   `id` attribute and a full masked `path` (including extension). A missing
 *   `id` falls back to the basename without extension. Entries are merged
 *   from `apps/Config` on top of this file.
 * - `enqueue_styles` / `bone_enqueue_styles` / `enqueue_scripts` /
 *   `bone_enqueue_scripts`: paths queued for the public frontend (face) and
 *   the backend (bone). Values are plain strings — symbolic ids are resolved
 *   via `register_*` before reaching this list. No filesystem path is written
 *   in the queue; `mask_urls` translates them on demand.
 * - `mask_urls`: the only doors between a URL and a file on disk. `{*}`
 *   matches any remaining path segments, and `{face_theme_active_name}` /
 *   `{bone_theme_active_name}` stand in for the active theme name. A URL with
 *   no matching pattern is not reachable.
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
    // Framework asset registries. Each entry carries `id` (the HTML attribute)
    // and `path` (full mask-relative path including extension). Omit `id` to
    // derive it from the basename automatically.
    'register_styles'  => [
        ['id' => 'style',         'path' => '/css/style.css'],
        ['id' => 'design-system', 'path' => '/system/css/System.css'],
    ],
    'register_scripts' => [
        ['id' => 'app', 'path' => '/js/app.js'],
    ],

    // Face (public frontend) asset queues — populated by apps/Config.
    'enqueue_styles'  => [],
    'enqueue_scripts' => [],

    // Bone (backend) asset queues — populated by apps/Config.
    'bone_enqueue_styles'  => [],
    'bone_enqueue_scripts' => [],

    'mask_urls' => [
        '/css/{*}'        => '/public/themes/{face_theme_active_name}/css/{*}',
        '/js/{*}'         => '/public/themes/{face_theme_active_name}/js/{*}',
        '/assets/css/{*}' => '/apps/Themes/{bone_theme_active_name}/css/{*}',
        '/assets/js/{*}'  => '/apps/Themes/{bone_theme_active_name}/js/{*}',
        '/system/css/{*}' => '/src/Views/Tokens/{*}',
        '/system/js/{*}'  => '/src/Views/Scripts/{*}',
    ],
];
