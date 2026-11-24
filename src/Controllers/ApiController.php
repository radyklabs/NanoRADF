<?php declare (strict_types=1);

/**
 * Base class for endpoints that answer with JSON instead of a page.
 * --
 * Extend this when the caller is another program rather than a person: the
 * headless side of the application. Every response is shaped the same way, so a
 * client only has to learn one envelope.
 *
 * Access is currently open. Token checks arrive in Phase 5; until then
 * `authorize()` lets every request through.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Controllers;

use System\Kernel\Controller as BaseController;

if (!defined ('basepath'))
{
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

class ApiController extends BaseController
{
    protected string $surface = 'api';

    /**
     * Send a JSON answer, in the shape every API response uses.
     * --
     * On success the body is `{ok: true, data: …}`. On failure it is
     * `{ok: false, error: "…"}`, and `$data` is read as the message to show.
     *
     * @package     NanoRADF
     * @author      RADLabs Development Team
     * @copyright   Radlabs Studio (c) 1986 - 2026
     * @since       0.0.1
     * @param       mixed $data   Payload for `data`, or the error text when `$ok` is false.
     * @param       int   $status HTTP status code.
     * @param       bool  $ok     false switches the envelope to the error form.
     * @return      array{status:int, body:string, headers:list<string>}
     */
    protected function json (mixed $data = null, int $status = 200, bool $ok = true): array
    {
        $envelope = $ok ? ['ok' => true, 'data' => $data] : ['ok' => false, 'error' => is_string ($data) ? $data : 'Request failed'];

        return $this->respond (null, $status, ['json' => $envelope]);
    }
}
