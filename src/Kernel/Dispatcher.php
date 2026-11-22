<?php declare (strict_types=1);

namespace System\Kernel;

use System\Services\AssetsService;

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
     * @since  0.0.1
     * @return void
     */
    public static function handle (): void
    {
        $method = strtoupper ((string) gserver ('request.method', 'GET'));
        $path   = Router::normalise ((string) gserver ('request.uri', '/'));

        // Masked assets are a system concern, not a route: intercept before
        // dispatch so no app route can shadow them.
        if (in_array ($method, ['GET', 'HEAD'], true) && self::asset ($path, $method === 'HEAD'))
        {
            return;
        }

        self::emit (Router::dispatch ($method, $path));
    }

    /**
     * Serve a masked asset when the path resolves to a real file.
     *
     * @since  0.0.1
     * @param  string $path Normalised request path.
     * @param  bool   $head HEAD request: send headers, no body.
     * @return bool True when an asset was served (caller must stop).
     */
    private static function asset (string $path, bool $head): bool
    {
        $file = AssetsService::resolve ($path);

        if ($file === null) {return false;}

        header ('Content-Type: '.$file['mime']);
        header ('Cache-Control: public, max-age=3600');
        header ('ETag: "'.md5_file ($file['file']).'"');
        header ('Content-Length: '.filesize ($file['file']));

        if (!$head) {readfile ($file['file']);}

        return true;
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
