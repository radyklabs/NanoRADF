<?php declare (strict_types=1);

namespace App\Controllers;

use System\Controllers\PrivateController;
use System\Kernel\Router;
use System\Services\AssetsService;

/**
 * Asset serving — public and masked URLs.
 *
 * `GET /themes/<scope>/<path>` and masked `GET /Files/<slug>/<path>` requests
 * reach PHP when the web server passes the miss to the front controller (the
 * Apache `.htaccess` has no static-file guard). Unknown or out-of-root paths
 * return 404.
 *
 * @since 0.0.1
 */
final class AssetsController extends PrivateController
{
    /**
     * Stream a theme asset to the client.
     *
     * The route placeholder only exists so multi-segment paths match; the real
     * path is the normalised request URI, which carries the mask prefix.
     *
     * @since  0.0.1
     * @param  string $path Path after the front controller (unused; see above).
     * @return void
     */
    public function serve (string $path): void
    {
        $file = AssetsService::resolve ($this->routePath ());

        if ($file === null)
        {
            http_response_code (404);
            header ('Content-Type: text/html; charset=utf-8');
            exit ('<h1>404 Not Found</h1>');
        }

        header ('Content-Type: '.$file['mime']);
        header ('Cache-Control: public, max-age=3600');
        header ('ETag: "'.md5_file ($file['file']).'"');
        header ('Content-Length: '.filesize ($file['file']));
        readfile ($file['file']);
        exit;
    }

    /**
     * Normalised request path (mask prefix included).
     *
     * @since  0.0.1
     * @return string
     */
    private function routePath (): string
    {
        return ltrim (Router::normalise ((string) gserver ('request.uri', '/')), '/');
    }
}
