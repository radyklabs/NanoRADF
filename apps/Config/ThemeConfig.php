<?php declare (strict_types=1);

/**
 * Theme default configuration.
 *
 * - `theme`: active theme name per surface. `face` serves the public
 *   frontend (`public/themes/<name>`); `bone` serves the backend
 *   (`src/Themes/<name>`).
 * - `enqueue_*` / `bone_enqueue_*`: asset names queued for each surface
 *   (extension omitted; `.css`/`.js` appended by the service).
 * - `mask_urls`: URL pattern → disk pattern. `{*}` matches a path with any
 *   number of segments; `{face_theme_active_name}` / `{bone_theme_active_name}`
 *   expand to the active theme name. Only listed patterns are reachable.
 *
 * @since 0.0.1
 */

return [
    'default' => [
        'private'   => 'admin',
    ],
];