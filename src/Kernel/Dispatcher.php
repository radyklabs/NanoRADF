<?php declare (strict_types=1);

namespace System\Kernel;

use System\Services\AssetsService;
use System\Services\Drip;

/**
 * Request dispatcher — resolves the matched route and renders it.
 *
 * Called by the gateway after boot. Keeps header/status/body emission out of
 * the gateway so routing stays a one-liner there.
 *
 * @since 0.0.1
 */
final class Dispatcher
{
    private function __construct () {}

    /**
     * Dispatch the current HTTP request and emit headers/body. No catch here: a
     * runtime fault bubbles to the gateway's single catch-all so it renders with
     * exactly one disclosure rule.
     *
     * When a route misses (404) or conflicts (405), the dispatcher catches the
     * exception and delegates to errorPage() so the error response is rendered
     * through the Drip engine with whatever styling is configured.
     *
     * @since  0.0.1
     * @return void
     */
    public static function handle (): void
    {
        $method = strtoupper ((string) gserver ('request.method', 'GET'));
        $path   = Router::normalise ((string) gserver ('request.uri', '/'));

        // Masked assets are a system concern, not a route: intercept before
        // dispatch so no app route can shadow them.
        if (in_array ($method, ['GET', 'HEAD'], true))
        {
            $file = self::dispatchAssets ($path);
            if ($file !== null)
            {
                self::serveAsset ($file, $method === 'HEAD');
                return;
            }
        }

        try
        {
            self::emit (Router::dispatch ($method, $path, $throw = true));
        }
        catch (\RuntimeException $th)
        {
            $message = (string) $th->getMessage ();
            if (!str_contains ($message, '→ 404') && !str_contains ($message, '→ 405'))
            {
                throw $th;
            }

            $code = str_contains ($message, '→ 405') ? 405 : 404;
            self::errorPage ($code, null);
        }
    }

    /**
     * Resolve a masked asset path to an absolute file path.
     *
     * Takes normalised request path, matches against mask_urls config patterns,
     * and returns the absolute disk path when found. Returns null when no match
     * or file does not exist.
     *
     * @since  0.0.1
     * @param  string $path Normalised request path.
     * @return string|null Absolute file path, or null when not a masked asset.
     */
    public static function dispatchAssets (string $path): ?string
    {
        $result = AssetsService::resolve ($path);
        return $result ? $result['file'] : null;
    }

    /**
     * Serve a resolved asset file with proper HTTP headers.
     *
     * @since  0.0.1
     * @param  string $file Absolute file path.
     * @param  bool   $head HEAD request: send headers, no body.
     * @return void
     */
    private static function serveAsset (string $file, bool $head): void
    {
        // Determine MIME type from file extension (mime_content_type may not be available on Windows).
        $ext      = strtolower (pathinfo ($file, PATHINFO_EXTENSION));
        $mimes    = [
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'text/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'ico'  => 'image/x-icon',
            default=> 'application/octet-stream',
        ];
        $mime     = $mimes[$ext] ?? $mimes['default'];

        header ("Content-Type: {$mime}");
        header ('Cache-Control: public, max-age=3600');
        header ('ETag: "'.md5_file ($file).'"');
        header ('Content-Length: '.filesize ($file));

        if (!$head) {readfile ($file);}
    }

    /**
     * Render an error page from src/Views/Errors/ via Drip.
     *
     * Uses Drip directly (not Views::render) to avoid injecting face/bone
     * asset tags that belong to normal pages. Falls back to static markup
     * when the view file is missing.
     *
     * @since  0.0.2
     * @param  int               $code      HTTP status code.
     * @param  Throwable|null    $exception Optional exception for debug detail.
     * @return void
     */
    public static function errorPage (int $code, ?Throwable $exception = null): void
    {
        $view = interlace (basepath, 'src', 'Views', 'Errors', (string) $code.'.html');

        if (!is_file ($view))
        {
            http_response_code ($code);
            header ('Content-Type: text/html; charset=utf-8');
            exit ("<h1>{$code} " . statc ($code) . "</h1><p>Error page not available.</p>");
        }

        $layout = null;
        $testLayout = interlace (basepath, 'apps', 'Views', 'layouts', 'main.html');
        if (is_file ($testLayout)) {$layout = $testLayout;}

        $data = [
            'code'    => $code,
            'title'   => statc ($code),
            'message' => ($exception instanceof \Throwable && is_debug ())
                ? htmlspecialchars ((string) $exception->getMessage (), ENT_QUOTES, 'UTF-8')
                : null,
        ];

        http_response_code ($code);
        header ('Content-Type: text/html; charset=utf-8');
        echo Drip::renderFile ($view, $data, null, null);
    }

    /**
     * Send the response to the client: extra headers, then status + media type,
     * then the body.
     *
     * @since  0.0.1
     * @param  array{status:int, body:string, headers?:list<string>} $response
     * @return void
     */
    private static function emit (array $response): void
    {
        $status  = (int) ($response['status'] ?? 200);
        $headers = (array) ($response['headers'] ?? []);
        $isJson  = in_array ('Content-Type: application/json; charset=utf-8', $headers, true);

        if ($isJson)
        {
            header ('Content-Type: application/json; charset=utf-8', true, $status);
        }
        else
        {
            foreach ($headers as $line) {header ((string) $line);}
            header ('Content-Type: text/html; charset=utf-8', true, $status);
        }

        echo (string) ($response['body'] ?? '');
    }
}
