<?php declare (strict_types=1);

/**
 * CSRF protection — one token per session, checked on state-changing requests.
 * --
 * The token is generated lazily and stored in the session. Validation reads
 * the request through `gserver()` so the framework has one request-access
 * convention. Applications embed the token in forms or meta tags; the login
 * module calls `regenerate()` after a privilege change.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Services;

use System\Kernel\Session;
use Throwable;
use function System\gserver;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class CSRFService
{
    private const KEY   = 'csrf_token';
    private const FIELD = '_csrf';

    /**
     * Current token, generated on first use.
     *
     * @since  0.0.1
     * @return string
     */
    public static function token (): string
    {
        $token = Session::get (self::KEY);
        if (is_string ($token) && $token !== '') {return $token;}

        $token = bin2hex (random_bytes (32));
        Session::set (self::KEY, $token);
        return $token;
    }

    /**
     * Compare a supplied token against the stored one in constant time.
     *
     * @since  0.0.1
     * @param  mixed $provided
     * @return bool
     */
    public static function validate (mixed $provided): bool
    {
        $stored = Session::get (self::KEY);
        if (!is_string ($stored) || $stored === '' || !is_string ($provided)) {return false;}

        return hash_equals ($stored, $provided);
    }

    /**
     * Validate the current request: header first, then POST field.
     *
     * @since  0.0.1
     * @return bool
     */
    public static function validateRequest (): bool
    {
        $header = gserver ('http.x_csrf_token');
        if (is_string ($header) && $header !== '') {return self::validate ($header);}

        $field = $_POST[self::FIELD] ?? null;
        return self::validate ($field);
    }

    /**
     * Whether the current method needs a CSRF token.
     *
     * @since  0.0.1
     * @return bool
     */
    public static function requiresValidation (): bool
    {
        $method = strtoupper ((string) gserver ('request.method', 'GET'));
        return in_array ($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * Rotate the token. Called by the login module after privilege change.
     *
     * @since  0.0.1
     * @return string New token.
     */
    public static function regenerate (): string
    {
        Session::forget (self::KEY);
        return self::token ();
    }

    /**
     * Form field name for the token.
     *
     * @since  0.0.1
     * @return string
     */
    public static function fieldName (): string
    {
        return self::FIELD;
    }
}