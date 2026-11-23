<?php declare (strict_types=1);

/**
 * Asset configuration.
 *
 * - `theme`: active theme name per surface. `face` serves the public
 *   frontend (`public/themes/<name>`); `bone` serves the backend
 *   (`src/Themes/<name>`).
 * - `id_registry`: symbolic asset ids mapped to filename stem under Tokens
 *   or theme root. Rendered tags carry this id as the HTML `id` attribute.
 * - `enqueue_*` / `bone_enqueue_*`: ids queued for each surface. The framework
 *   resolves each id to its masked url via `mask_urls`; no path is written here.
 * - `mask_urls`: URL pattern → disk pattern. `{*}` matches a path with any
 *   number of segments; `{face_theme_active_name}` / `{bone_theme_active_name}`
 *   expand to the active theme name. Only listed patterns are reachable.
 *
 * @since 0.0.1
 */

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
