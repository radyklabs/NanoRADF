<?php declare (strict_types=1);

/**
 * Application-wide site settings model — static config for v1.
 * Phase 4: reads from headless backend API.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace App\Model;

use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class SettingsModel extends BaseModel
{
    /**
     * Get site-wide settings for templates.
     *
     * @return array<string, mixed>
     */
    public function getSettings (): array
    {
        return [
            'site_name'        => $this->getStaticData('app_name'),
            'site_version'     => $this->getStaticData('app_version'),
            'environment'      => $this->getStaticData('environment'),
            'maintenance_mode' => false,
            'contact_email'    => 'hello@nanoradf.local',
            'social_links'     => [
                'github'  => 'https://github.com/radyklabs/NanoRADF',
                'twitter' => 'https://twitter.com/nanoradf',
            ],
        ];
    }

    /**
     * Check if maintenance mode is active.
     *
     * @return bool
     */
    public function isMaintenanceMode (): bool
    {
        return $this->getSettings()['maintenance_mode'] ?? false;
    }
}