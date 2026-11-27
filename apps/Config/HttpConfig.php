<?php declare (strict_types=1);

/**
 * HTTP Service configuration — application-level overrides.
 * --
 * Layered on top of `src/Config/HttpConfig.php`. Only include keys you
 * want to change; missing keys fall back to the framework defaults.
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
    // Override via .env if needed:
    // 'timeout_ms' => (int) getenv('http.timeout_ms'),
    // 'verify_ssl' => getenv('http.verify_ssl') === 'false' ? false : true,
];