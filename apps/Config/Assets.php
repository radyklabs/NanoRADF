<?php declare (strict_types=1);

/**
 * Asset configuration.
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
    'theme' => [
        'face' => 'default',
        'bone' => 'default',
    ],

    'enqueue_styles'  => ['css/style', 'ds/System'],
    'enqueue_scripts' => ['js/app'],

    'bone_enqueue_styles'  => ['css/style'],
    'bone_enqueue_scripts' => ['js/app'],

    'mask_urls' => [
        '/css/{*}'        => '/public/themes/{face_theme_active_name}/css/{*}',
        '/js/{*}'         => '/public/themes/{face_theme_active_name}/js/{*}',
        '/ds/{*}'         => '/src/Views/Tokens/{*}',
        '/assets/css/{*}' => '/src/Themes/{bone_theme_active_name}/css/{*}',
        '/assets/js/{*}'  => '/src/Themes/{bone_theme_active_name}/js/{*}',
    ],
];
