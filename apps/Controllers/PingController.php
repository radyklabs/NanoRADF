<?php declare (strict_types=1);

/**
 * API probe — `GET /ping` returns an ok envelope; `GET /fail/{code}` returns the
 * error envelope with the requested status. Demonstrates the api surface end to
 * end (JSON body + application/json content type).
 *
 * @since 0.0.1
 */

namespace App\Controllers;

use System\Controllers\ApiController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class PingController extends ApiController
{
    /**
     * Healthy endpoint.
     *
     * @since  0.0.1
     * @return array<string, mixed>
     */
    public function index (): array
    {
        return $this->json (['pong' => gmdate ('c')], 200);
    }

    /**
     * Failure endpoint: `{code}` is constrained to a valid HTTP status; used by
     * tests/route_check.php for a scripted 4xx/5xx check.
     *
     * @since  0.0.1
     * @param  string $code URL segment; cast locally because path params arrive
     *                      as strings from the router.
     * @return array<string, mixed>
     */
    public function fail (string $code): array
    {
        return $this->json ('scoped failure', (int) $code, false);
    }
}
