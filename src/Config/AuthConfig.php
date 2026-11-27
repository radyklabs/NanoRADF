<?php declare (strict_types=1);

/**
 * Framework defaults for API token validation.
 * --
 * The core only knows how to read a token from the request; it does not know
 * what makes a token valid. An application registers a callable validator in
 * `apps/Config/AuthConfig.php`. With no validator, API access fails closed.
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
    'api_validator' => null,
];