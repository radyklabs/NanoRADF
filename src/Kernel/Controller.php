<?php declare (strict_types=1);

/**
 * Controller — shared request lifecycle and response helpers for all surfaces.
 * --
 * Base class for every controller. Knows which surface it serves (`public`,
 * `private`, or `api`) by looking at its own class hierarchy, provides an
 * authorization gate, and builds responses. Controllers never echo directly;
 * they return arrays or strings consumed by the Dispatcher.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Kernel;

use System\Contracts\Interfaces\SurfaceAwareInterface;
use System\Contracts\Traits\SurfaceResolverTrait;
use System\Services\ViewsService;

if (!defined ('basepath'))
{
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

class Controller implements SurfaceAwareInterface
{
    use SurfaceResolverTrait;

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
     * Build a response for the current surface.
     *
     * Public/private surfaces return `['status', 'body', 'headers']`. The api
     * surface additionally accepts a `json` key (data payload encoded as JSON)
     * and always emits Content-Type: application/json.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     mixed                $body   HTML body, or data payload on the api surface.
     * @param     int                  $status HTTP status.
     * @param     array<string, mixed> $extra  `json` envelope or custom `headers`.
     * @return    array{status:int, body:string, headers:list<string>}
     */
    protected function respond (mixed $body = '', int $status = 200, array $extra = []): array
    {
        if ($this->getSurface () === 'api')
        {
            $payload = json_encode ($extra['json'] ?? ['data' => $body], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return ['status' => $status, 'body' => (string) $payload, 'headers' => ['Content-Type: application/json; charset=utf-8']];
        }

        return ['status' => $status, 'body' => (string) $body, 'headers' => $extra['headers'] ?? []];
    }

    /**
     * Render a Drip view bound to the current controller's surface.
     *
     * Module controllers resolve plain names against their own `Views/` folder;
     * app controllers resolve against `apps/Views/`. The layout comes from the
     * active theme for this controller's surface.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string      $name   View name without extension (`file` or `folder.file`).
     * @param     array       $data   Variables for the template scope.
     * @param     string|null $layout Layout name overriding the theme layout.
     * @return    array{status:int, body:string, headers:list<string>}
     */
    protected function view (string $name, array $data = [], ?string $layout = null): array
    {
        return $this->respond (ViewsService::render (
            $name,
            $data,
            $layout,
            ViewsService::context (static::class),
            $this->getSurface ()
        ));
    }

    /**
     * Standard 404 response — themed stub body.
     *
     * Returns a minimal 404 response ready for the dispatcher to emit. Phase 6
     * may replace this with the full Drip-rendered error page.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    array{status:int, body:string}
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
