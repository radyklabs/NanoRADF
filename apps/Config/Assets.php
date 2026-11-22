<?php declare (strict_types=1);

/**
 * Asset configuration — the single source for enqueued assets and URL masks.
 *
 * Scope `default` serves `public/themes/default`; scope `admin` serves
 * `src/Themes/admin`. Both keys are read directly by
 * `System\Services\AssetsService`.
 *
 * @since 0.0.1
 */

return [
    // Public theme. Values are relative to the scope root; the extension is
    // appended when omitted.
    'enqueue_styles'  => ['css/style'],
    'enqueue_scripts' => ['js/app'],

    // Admin theme.
    'admin_enqueue_styles'  => ['css/style'],
    'admin_enqueue_scripts' => ['js/app'],

    // Disk → URL mapping. `slug` is a virtual prefix; requests under it are
    // served by the `GET /Files/...` route in apps/Config/Routes.php.
    'mask_urls' => [
        'theme_css' => [
            'slug' => 'Files/c',
            'dir'  => interlace (publicpath, 'themes', 'default', 'css'),
        ],
        'theme_js' => [
            'slug' => 'Files/j',
            'dir'  => interlace (publicpath, 'themes', 'default', 'js'),
        ],
    ],
];
