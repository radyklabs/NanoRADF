<?php declare (strict_types=1);

/**
 * Health check endpoint — verifies API is running.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace App\Controllers\Api;

use System\Controllers\ApiController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class HealthController extends ApiController
{
    /**
     * GET /api/v1/health — health check endpoint.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function index (): array
    {
        return $this->ok([
            'status'    => 'ok',
            'timestamp' => time(),
            'version'   => \System\genv('app.version', '0.0.1'),
            'mode'      => \System\genv('app.mode', 'full'),
        ]);
    }
}