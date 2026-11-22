<?php declare (strict_types=1);

namespace System\Kernel;

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

        self::emit (Router::dispatch ($method, $path));
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
