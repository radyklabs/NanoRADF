<?php declare (strict_types=1);

/**
 * Welcome module configuration — mergeable via gconfig('welcome').
 * Controls module behavior: feature flags, pagination, external API endpoints, etc.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

return [
    'name'             => 'Welcome',
    'title'            => 'Welcome Module',
    'description'      => 'Default SSR demo module showcasing Drip templates.',
    'enabled'          => true,
    'view_namespace'   => 'modules/Welcome/Views',

    // Feature flags — toggle UI sections without code changes
    'features' => [
        'show_features_list' => true,
        'show_mode_badge'    => true,
        'show_raw_demo'      => true,
    ],

    // Pagination / limits for future data-driven views
    'pagination' => [
        'per_page' => 10,
        'max_page' => 100,
    ],

    // External API endpoints (Phase 4: headless backend communication)
    'api' => [
        'backend_url'     => '', // overridden by gconfig('app.backend_url')
        'timeout_ms'      => 3000,
        'retry_attempts'  => 2,
        'endpoints'       => [
            'welcome_data' => '/api/v1/welcome',
            'features'     => '/api/v1/features',
        ],
    ],

    // SEO / meta defaults for the welcome page
    'seo' => [
        'title'       => 'Welcome — NanoRADF',
        'description' => 'Drip Template Engine demo with SSR rendering.',
        'og_image'    => '/img/og-welcome.png',
    ],
];