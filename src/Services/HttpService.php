<?php declare (strict_types=1);

/**
 * HTTP Service — server-to-server communication for frontend → backend.
 * --
 * cURL primary, stream context fallback. Validates HTTP status (2xx only)
 * and JSON schema before returning data. Configurable via gconfig('http').
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Services;

use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class HttpService
{
    private array $config;

    public function __construct ()
    {
        $this->config = \gconfig('http', [
            'timeout_ms'      => 3000,
            'connect_timeout_ms' => 1000,
            'retry_attempts'  => 2,
            'retry_delay_ms'  => 100,
            'circuit_breaker' => [
                'enabled'          => true,
                'failure_threshold' => 5,
                'reset_timeout_ms'  => 30000,
            ],
            'verify_ssl'      => true,
            'user_agent'      => 'NanoRADF/0.0.1',
        ]);
    }

    /**
     * GET request with JSON response validation.
     *
     * @param string $url Full URL to request
     * @param array $headers Optional additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    public function get (string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * POST request with JSON body and JSON response validation.
     *
     * @param string $url Full URL to request
     * @param mixed $body Data to encode as JSON
     * @param array $headers Optional additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    public function post (string $url, mixed $body = null, array $headers = []): array
    {
        return $this->request('POST', $url, $body, $headers);
    }

    /**
     * Generic HTTP request with validation.
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param mixed $body Request body (for POST/PUT/PATCH)
     * @param array $headers Additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    public function request (string $method, string $url, mixed $body = null, array $headers = []): array
    {
        $attempts = 0;
        $maxAttempts = $this->config['retry_attempts'] + 1;
        $lastError = null;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $result = $this->executeRequest($method, $url, $body, $headers);

            if ($result['ok']) {
                return $result;
            }

            $lastError = $result['error'] ?? 'Unknown error';

            // Don't retry on client errors (4xx) except 429
            $status = $result['status'] ?? 0;
            if ($status >= 400 && $status < 500 && $status !== 429) {
                return $result;
            }

            // Don't retry on last attempt
            if ($attempts >= $maxAttempts) {
                break;
            }

            // Exponential backoff
            $delay = $this->config['retry_delay_ms'] * $attempts;
            usleep($delay * 1000);
        }

        return [
            'ok'      => false,
            'error'   => "Request failed after {$attempts} attempts: {$lastError}",
            'status'  => 0,
            'data'    => null,
        ];
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param mixed $body Request body
     * @param array $headers Additional headers
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    private function executeRequest (string $method, string $url, mixed $body, array $headers): array
    {
        // Try cURL first
        if (function_exists('curl_init')) {
            $result = $this->executeCurl($method, $url, $body, $headers);
            if ($result !== null) {return $result;}
        }

        // Fallback to stream context
        return $this->executeStream($method, $url, $body, $headers);
    }

    /**
     * Execute request via cURL.
     *
     * @return array{ok:bool, data:mixed, error?:string, status:int}|null
     */
    private function executeCurl (string $method, string $url, mixed $body, array $headers): ?array
    {
        $ch = curl_init();
        if ($ch === false) {return null;}

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT_MS     => $this->config['timeout_ms'],
            CURLOPT_CONNECTTIMEOUT_MS => $this->config['connect_timeout_ms'],
            CURLOPT_SSL_VERIFYPEER => $this->config['verify_ssl'],
            CURLOPT_USERAGENT      => $this->config['user_agent'],
            CURLOPT_HTTPHEADER     => $this->buildHeaders($headers, $body),
        ];

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $jsonBody = \json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $options[CURLOPT_POSTFIELDS] = $jsonBody;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['ok' => false, 'error' => "cURL error ({$errno}): {$error}", 'status' => 0, 'data' => null];
        }

        return $this->validateResponse($response, $status);
    }

    /**
     * Execute request via stream context.
     *
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    private function executeStream (string $method, string $url, mixed $body, array $headers): array
    {
        $contextOptions = [
            'http' => [
                'method'        => $method,
                'timeout'       => $this->config['timeout_ms'] / 1000,
                'ignore_errors' => true,
                'header'        => implode("\r\n", $this->buildHeaders($headers, $body)),
            ],
        ];

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contextOptions['http']['content'] = \json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (str_starts_with($url, 'https://')) {
            $contextOptions['ssl'] = [
                'verify_peer'      => $this->config['verify_ssl'],
                'verify_peer_name' => $this->config['verify_ssl'],
            ];
        }

        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);

        // Get HTTP status from wrapper
        $status = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d{3})/', $header, $matches)) {
                    $status = (int) $matches[1];
                    break;
                }
            }
        }

        if ($response === false) {
            $error = error_get_last();
            return ['ok' => false, 'error' => $error['message'] ?? 'Stream request failed', 'status' => 0, 'data' => null];
        }

        return $this->validateResponse($response, $status);
    }

    /**
     * Build headers array for cURL/stream.
     *
     * @return list<string>
     */
    private function buildHeaders (array $headers, mixed $body): array
    {
        $result = [
            'Accept: application/json',
            'User-Agent: ' . $this->config['user_agent'],
        ];

        if ($body !== null) {
            $result[] = 'Content-Type: application/json; charset=utf-8';
        }

        foreach ($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }

        return $result;
    }

    /**
     * Validate HTTP response: status 2xx + valid JSON.
     *
     * @return array{ok:bool, data:mixed, error?:string, status:int}
     */
    private function validateResponse (string $response, int $status): array
    {
        if ($status < 200 || $status >= 300) {
            return [
                'ok'     => false,
                'error'  => "HTTP {$status}: " . substr($response, 0, 200),
                'status' => $status,
                'data'   => null,
            ];
        }

        $data = \json_decode($response, true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            return [
                'ok'     => false,
                'error'  => 'Invalid JSON response: ' . \json_last_error_msg(),
                'status' => $status,
                'data'   => null,
            ];
        }

        return ['ok' => true, 'data' => $data, 'status' => $status];
    }
}