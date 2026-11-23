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
    'theme' => [
        'face' => 'default',
        'bone' => 'default',
    ],

    // Symbolic ids that become the `<link ... id="...">` / `<script ... id="...">`.
    // Values are the path relative to the matching mask-url domain (without leading '/').
    // The mask engine finds the owning pattern and rewrites accordingly.
    'id_registry' => [
        'style'         => 'css/style',
        'design-system' => 'system/css/System',
        'app'           => 'js/app',
    ],

    'enqueue_styles'  => ['style', 'design-system'],
    'enqueue_scripts' => ['app'],

    'bone_enqueue_styles'  => ['style'],
    'bone_enqueue_scripts' => ['app'],

    'mask_urls' => [
        '/css/{*}'        => '/public/themes/{face_theme_active_name}/css/{*}',
        '/js/{*}'         => '/public/themes/{face_theme_active_name}/js/{*}',
        '/assets/css/{*}' => '/src/Themes/{bone_theme_active_name}/css/{*}',
        '/assets/js/{*}'  => '/src/Themes/{bone_theme_active_name}/js/{*}',
        '/system/css/{*}' => '/src/Views/Tokens/{*}',
        '/system/js/{*}' => '/src/Views/Scripts/{*}',
    ],
];
