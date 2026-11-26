<?php declare (strict_types=1);

/**
 * Application-wide site service — business logic for site-wide features.
 * Uses SettingsModel for data access.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace App\Services;

use Throwable;
use App\Model\SettingsModel;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class SiteService extends BaseService
{
    private SettingsModel $settingsModel;

    public function __construct ()
    {
        $this->settingsModel = new SettingsModel();
    }

    /**
     * Get site settings for global template variables.
     *
     * @return array<string, mixed>
     */
    public function getSiteData (): array
    {
        $settings = $this->settingsModel->getSettings();

        return [
            'site_name'     => $settings['site_name'],
            'site_version'  => $settings['site_version'],
            'environment'   => $settings['environment'],
            'contact_email' => $settings['contact_email'],
            'social_links'  => $settings['social_links'],
            'current_year'  => (int) date('Y'),
        ];
    }

    /**
     * Check if site is in maintenance mode.
     *
     * @return bool
     */
    public function isMaintenanceMode (): bool
    {
        return $this->settingsModel->isMaintenanceMode();
    }

    /**
     * Get maintenance message if in maintenance mode.
     *
     * @return string|null
     */
    public function getMaintenanceMessage (): ?string
    {
        if (!$this->isMaintenanceMode()) {return null;}

        return 'Site is currently under maintenance. Please check back later.';
    }
}