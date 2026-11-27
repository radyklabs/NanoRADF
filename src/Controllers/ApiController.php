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
use System\Services\HttpService;
use Throwable;
use function System\gserver;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

class ApiController extends BaseController
{
    /**
     * Allow the action only when the request token passes the app validator.
     * --
     * API calls are server-to-server and do not share a browser session, so
     * this guard reads the token from the request header. The core does not
     * decide what a valid token is: an application registers a callable in
     * `AuthConfig['api_validator']`. With no validator, access fails closed.
     *
     * @since  0.0.1
     * @return bool
     */
    public function authorize (): bool
    {
        $validator = gconfig ('auth') ['api_validator'] ?? null;
        if (!is_callable ($validator)) {return false;}

        $header = gserver ('http.authorization');
        $token  = is_string ($header) && str_starts_with ($header, 'Bearer ') ? substr ($header, 7) : '';

        return $token !== '' && $validator ($token) === true;
    }

    /**
     * HTTP client for server-to-server calls (frontend → backend).
     * Lazy-loaded on first use.
     *
     * @var HttpService|null
     */
    private ?HttpService $httpService = null;

    /**
     * Get the HTTP service instance.
     *
     * @return HttpService
     */
    protected function httpService (): HttpService
    {
        if ($this->httpService === null) {$this->httpService = new HttpService();}
        return $this->httpService;
    }

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

    /**
     * Send a successful JSON response.
     *
     * @param mixed $data Response payload
     * @param int $status HTTP status code (default 200)
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function ok (mixed $data = null, int $status = 200): array
    {
        return $this->json ($data, $status, true);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code (default 400)
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function error (string $message, int $status = 400): array
    {
        return $this->json ($message, $status, false);
    }

    /**
     * Send a not found JSON response.
     *
     * @param string $message Error message
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function notFound (string $message = 'Resource not found'): array
    {
        return $this->error ($message, 404);
    }

    /**
     * Send an unauthorized JSON response.
     *
     * @param string $message Error message
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function unauthorized (string $message = 'Unauthorized'): array
    {
        return $this->error ($message, 401);
    }

    /**
     * Send a forbidden JSON response.
     *
     * @param string $message Error message
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function forbidden (string $message = 'Forbidden'): array
    {
        return $this->error ($message, 403);
    }

    /**
     * Send a validation error JSON response.
     *
     * @param string|array $errors Validation error message(s)
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function validationError (string|array $errors): array
    {
        $message = is_array ($errors) ? implode (', ', $errors) : $errors;
        return $this->error ($message, 422);
    }

    /**
     * Send a server error JSON response.
     *
     * @param string $message Error message
     * @return array{status:int, body:string, headers:list<string>}
     */
    protected function serverError (string $message = 'Internal server error'): array
    {
        return $this->error ($message, 500);
    }

    /**
     * Make a GET request to the backend API.
     *
     * @param string $endpoint API endpoint (e.g., '/api/v1/users')
     * @param array $headers Optional additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    protected function backendGet (string $endpoint, array $headers = []): array
    {
        $baseUrl = \System\genv ('app.backend_url');
        if ($baseUrl === false || $baseUrl === '') {return $this->missingBackendUrlError();}

        return $this->httpService()->get (rtrim ($baseUrl, '/').'/'.ltrim ($endpoint, '/'), $headers);
    }

    /**
     * Make a POST request to the backend API.
     *
     * @param string $endpoint API endpoint (e.g., '/api/v1/users')
     * @param mixed $body Request body
     * @param array $headers Optional additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    protected function backendPost (string $endpoint, mixed $body = null, array $headers = []): array
    {
        $baseUrl = \System\genv ('app.backend_url');
        if ($baseUrl === false || $baseUrl === '') {return $this->missingBackendUrlError();}

        return $this->httpService()->post (rtrim ($baseUrl, '/').'/'.ltrim ($endpoint, '/'), $body, $headers);
    }

    /**
     * Make a generic request to the backend API.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param mixed $body Request body
     * @param array $headers Optional additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    protected function backendRequest (string $method, string $endpoint, mixed $body = null, array $headers = []): array
    {
        $baseUrl = \System\genv ('app.backend_url');
        if ($baseUrl === false || $baseUrl === '') {return $this->missingBackendUrlError();}

        return $this->httpService()->request ($method, rtrim ($baseUrl, '/').'/'.ltrim ($endpoint, '/'), $body, $headers);
    }

    /**
     * Standard error shape when app.backend_url is not configured.
     *
     * @return array{ok:bool, data:mixed, error:string, status:int}
     */
    private function missingBackendUrlError (): array
    {
        return ['ok' => false, 'error' => 'Backend URL not configured (app.backend_url)', 'status' => 0, 'data' => null];
    }
}
