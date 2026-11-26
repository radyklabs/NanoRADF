<?php declare (strict_types=1);

/**
 * Application-wide base model — common data access patterns.
 * v1: static data only; Phase 4 adds headless API client.
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

abstract class BaseModel
{
    /**
     * Get static data for a given key.
     * Override in concrete models for real data sources.
     *
     * @param string $key Data identifier
     * @return mixed
     */
    protected function getStaticData (string $key): mixed
    {
        $data = [
            'app_name'    => 'NanoRADF',
            'app_version' => '0.0.1',
            'environment' => \System\genv('app.env', 'development'),
        ];

        return $data[$key] ?? null;
    }
}