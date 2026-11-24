<?php declare (strict_types=1);

/**
 * The framework's built-in asset map.
 * --
 * Everything the framework knows about its own styles and scripts lives here:
 * the short names it uses for them, the queues it fills for each surface, and
 * the URL patterns that are allowed to reach a file on disk. Application-level
 * additions belong in `apps/Config/AssetsConfig.php`, which is layered on top
 * of this file.
 *
 * - `id_registry`: short names for files, so a queue can ask for `style`
 *   instead of spelling out a path. The name becomes the `id` attribute of the
 *   rendered tag.
 * - `enqueue_*` / `bone_enqueue_*`: the names queued for the public frontend
 *   (face) and the backend (bone). Each name is resolved to a real URL through
 *   `mask_urls`; no filesystem path is written in the queue.
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
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

return [
    // Symbolic ids that become the `<link ... id="...">` / `<script ... id="...">`.
    // Values are the path relative to the matching mask-url domain (without leading '/').
    // The mask engine finds the owning pattern and rewrites accordingly.
    'id_registry' => [
        'style'         => 'css/style',
        'design-system' => 'system/css/System',
        'app'           => 'js/app',
    ],

    // Face (public frontend) asset queues
    'enqueue_styles'  => [],
    'enqueue_scripts' => [],

    // Bone (backend) asset queues
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
