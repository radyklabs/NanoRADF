<?php declare (strict_types=1);

namespace App\Controllers;

use System\Kernel\Controller;

/**
 * API probe — `GET /ping` returns a JSON envelope and sets the JSON media type.
 *
 * @since 0.0.1
 */
final class PingController extends Controller
{
    protected string $surface = 'api';

    /**
     * @since  0.0.1
     * @return array<string, mixed>
     */
    public function index (): array
    {
        return $this->respond (null, 200, ['json' => ['ok' => true, 'data' => ['pong' => gmdate ('c')] ]]);
    }
}
