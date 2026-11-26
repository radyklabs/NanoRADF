<?php declare (strict_types=1);
 
  /**
  * Request dispatcher — handles assets and routes for each HTTP request.
  * --
  * Entry point after routing. Intercepts masked asset requests before routing,
  * dispatches matched routes through the Router, and emits responses with
  * security headers. Unhandled 404/405 errors render themed error pages via
  * Drip instead of raw text.
  *
  * @package     NanoRADF
  * @author      RADLabs Development Team
  * @copyright   Radlabs Studio (c) 1986 - 2026
  * @since       0.0.1
  */
 
namespace System\Kernel;
 
use Throwable;
use RuntimeException;
use System\Services\AssetsService;
use System\Services\DripService;
use function System\gserver;
 
if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class Dispatcher
{
    private function __construct () {}

    /**
     * Dispatch the current HTTP request: assets first, then routes.
     *
     * Masked assets bypass routing entirely to prevent app routes from shadowing
     * them. After serving an asset (or skipping), the router handles the rest.
     * Runtime faults bubble to the gateway's single catch-all; handled 404/405
     * errors are rendered as themed pages via Drip.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    void
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
        catch (RuntimeException $th)
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
     * Resolve a masked asset path to an absolute file path on disk.
     *
     * Matches the normalised request path against URL mask patterns from config.
     * Returns the absolute file path when found; null when no match or file missing.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string $path Normalised request path.
     * @return    string|null Absolute file path, or null when not a masked asset.
     */
    public static function dispatchAssets (string $path): ?string
    {
        $result = AssetsService::resolve ($path);
        return $result ? $result['file'] : null;
    }

    /**
     * Serve a resolved asset file with appropriate HTTP headers.
     *
         * Delegates to AssetsService::serveAsset() for MIME, headers, streaming, and conditional GET.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string $file Absolute file path.
     * @param     bool   $head HEAD request: send headers, no body.
     * @return    void
     */
    private static function serveAsset (string $file, bool $head): void
    {
            AssetsService::serveAsset ($file, $head);
    }

    /**
     * Render a themed error page via Drip for the given status code.
     *
     * Renders the error view standalone: error pages are complete documents and
     * never borrow a theme layout, because the theme itself may be the failure.
     * Falls back to static markup when the view file is missing. Only shows
     * exception details when debug mode is active.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.2
     * @param     int              $code      HTTP status code.
     * @param     Throwable|null   $exception Optional exception for debug detail.
     * @return    void
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

        $data = [
            'code'    => $code,
            'title'   => statc ($code),
            'message' => ($exception instanceof Throwable && is_debug ())
                ? htmlspecialchars ((string) $exception->getMessage (), ENT_QUOTES, 'UTF-8')
                : null,
        ];

        http_response_code ($code);
        header ('Content-Type: text/html; charset=utf-8');
        echo DripService::renderFile ($view, $data, null, null);
    }

    /**
     * Send the response to the client: security headers, extra headers, then
     * status + media type, then the body.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     array{status:int, body:string, headers?:list<string>} $response
     * @return    void
     */
    private static function emit (array $response): void
    {
        $status  = (int) ($response['status'] ?? 200);
        $headers = (array) ($response['headers'] ?? []);
        $isJson  = in_array ('Content-Type: application/json; charset=utf-8', $headers, true);

            // Security headers (applied to all responses)
            self::securityHeaders ($isJson);

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

    /**
     * Apply security headers to every response.
     *
     * X-Content-Type-Options, X-Frame-Options, Referrer-Policy always sent.
     * HSTS conditional on HTTPS; CSP differentiated between JSON and HTML.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     bool $isJson Whether response is JSON (affects CSP).
     * @return    void
     */
    private static function securityHeaders (bool $isJson): void
        {
            // Prevent MIME type sniffing
            header ('X-Content-Type-Options: nosniff');

            // Prevent clickjacking
            header ('X-Frame-Options: DENY');

            // Referrer policy
            header ('Referrer-Policy: strict-origin-when-cross-origin');

            // HSTS (only in production with HTTPS)
            if (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            {
                header ('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }

            // Content Security Policy
            if ($isJson)
            {
                header ("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");
            }
            else
            {
                header ("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
            }
        }
}
