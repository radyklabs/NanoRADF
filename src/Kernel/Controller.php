<?php declare (strict_types=1);

namespace System\Kernel;

/**
 * Controller — request lifecycle and response helpers shared by every surface.
 *
 * Actions return an array contract `['status' => int, 'body' => string]` (a bare
 * string is also accepted as a 200 body) and the gateway sends headers once;
 * controllers never echo. Path params arrive positionally in
 * placeholder-declaration order; request readers are exposed as instance helpers
 * so no action touches superglobals directly.
 *
 * @since 0.0.1
 */
class Controller
{
    /**
     * Surface key: 'public' | 'private' | 'api'. Drives `respond()` defaults.
     *
     * @var string
     */
    protected string $surface = 'public';

    /**
     * Guard applied before an action: true when the request may continue.
     * Router calls it so private/api surfaces gate centrally; Phase 5
     * overrides the default on those controllers with session/token auth.
     *
     * @since  0.0.1
     * @return bool
     */
    public function authorize (): bool
    {
        return true;
    }

    /**
     * Build a response for the current surface. Public/private return
     * `['status', 'body']`; the API surface additionally accepts `data` and
     * always emits JSON.
     *
     * @since  0.0.1
     * @param  mixed                    $body   HTML body, or data payload on the api surface.
     * @param  int                      $status HTTP status.
     * @param  array<string, mixed>     $extra  Surface-specific extras (`headers`, `json`).
     * @return array{status:int, body:string}
     */
    protected function respond (mixed $body = '', int $status = 200, array $extra = []): array
    {
        if ($this->surface === 'api')
        {
            $body = json_encode ($extra['json'] ?? ['data' => $body], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return ['status' => $status, 'body' => (string) $body];
    }

    /**
     * Standard 404 outcome — body is a themed stub; Phase 6 swaps it for the
     * error page from `src/Views/Errors/`.
     *
     * @since  0.0.1
     * @return array{status:int, body:string}
     */
    protected function notFound (): array
    {
        return $this->respond ("<h1>404 Not Found</h1><p>The requested page could not be found.</p>", 404);
    }

    /**
     * The normalised path of the current request (`/` for root).
     *
     * @since  0.0.1
     * @return string
     */
    protected function path (): string
    {
        return Router::normalise ((string) gserver ('request.uri', '/'));
    }
}
