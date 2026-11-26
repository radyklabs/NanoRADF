<?php declare (strict_types=1);

/**
 * Welcome service — business logic orchestrator for the Welcome module.
 * Uses module config (gconfig('welcome')) for feature flags, pagination, API endpoints.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace Modules\Welcome\Services;

use Throwable;
use function System\genv;
use function gconfig;
use Modules\Welcome\Model\WelcomeModel;

if (!defined ('basepath'))
{
    try {exit (@require realpath (__DIR__.'/../../').DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class WelcomeService
{
    private WelcomeModel $model;
    private array $config;

    public function __construct ()
    {
        $this->model = new WelcomeModel();
        $this->config = gconfig('welcome', []);
    }

    /**
     * Prepare data array for the welcome view.
     * Reads feature flags, pagination, SEO from module config.
     *
     * @return array<string, mixed>
     */
    public function prepareData (): array
    {
        $data = $this->model->getWelcomeData();
        $data['mode'] = genv('app.mode', 'full');
        $data['loud'] = true;
        $data['unsafe'] = '<script>alert(\'parsing successful\')</script>';

        // Feature flags from config
        $features = $this->config['features'] ?? [];
        $data['show_features_list'] = $features['show_features_list'] ?? true;
        $data['show_mode_badge']    = $features['show_mode_badge'] ?? true;
        $data['show_raw_demo']      = $features['show_raw_demo'] ?? true;

        // Pagination defaults
        $pagination = $this->config['pagination'] ?? [];
        $data['per_page'] = $pagination['per_page'] ?? 10;

        // SEO meta
        $seo = $this->config['seo'] ?? [];
        $data['seo_title']       = $seo['title'] ?? 'Welcome — NanoRADF';
        $data['seo_description'] = $seo['description'] ?? '';
        $data['seo_og_image']    = $seo['og_image'] ?? '';

        return $data;
    }

    /**
     * Get API endpoint config for server-to-server calls (Phase 4).
     *
     * @return array<string, mixed>
     */
    public function getApiConfig (): array
    {
        return $this->config['api'] ?? [];
    }
}