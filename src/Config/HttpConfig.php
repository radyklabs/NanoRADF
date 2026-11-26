<?php declare (strict_types=1);

/**
 * HTTP Service configuration — server-to-server communication defaults.
 * --
 * Framework-level defaults for HttpService. Application-level overrides belong
 * in `apps/Config/HttpConfig.php`, which is layered on top.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

return [
    // Request timeout in milliseconds
    'timeout_ms' => 3000,

    // Connection timeout in milliseconds
    'connect_timeout_ms' => 1000,

    // Number of retry attempts (total attempts = retry_attempts + 1)
    'retry_attempts' => 2,

    // Base delay between retries in milliseconds (exponential backoff)
    'retry_delay_ms' => 100,

    // Circuit breaker configuration (structure only; implementation deferred)
    'circuit_breaker' => [
        'enabled' => true,
        'failure_threshold' => 5,
        'reset_timeout_ms' => 30000,
    ],

    // SSL verification (set false only for local dev with self-signed certs)
    'verify_ssl' => true,

    // User-Agent header
    'user_agent' => 'NanoRADF/0.0.1',
];