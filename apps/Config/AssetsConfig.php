<?php declare (strict_types=1);

/**
 * Which styles and scripts the application loads, and where they come from.
 * --
 * This is the application's own say over its assets. The framework ships a
 * base set in `src/Config/AssetsConfig.php`; whatever is written here is laid
 * on top of it, so an entry listed here replaces the base entry of the same
 * name instead of being appended to it. Leave a key out to keep the base
 * behaviour.
 *
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
    // Face (public frontend) asset queues
    'enqueue_styles'  => ['style'],
    'enqueue_scripts' => [],

    // Bone (backend) asset queues
    'bone_enqueue_styles'  => [],
    'bone_enqueue_scripts' => [],

    // Assets mask URL patterns.
    'mask_urls' => [],
];
