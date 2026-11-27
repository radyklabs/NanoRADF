<?php declare (strict_types=1);

/**
 * Session lifecycle — lazy start with hardened cookie config from env.
 * --
 * The session starts only when the application first needs it (auth, CSRF).
 * Cookie flags are read from env with development-safe defaults: `secure` is
 * off by default so local HTTP works, and `HttpOnly` + `SameSite=Strict` are
 * on by default so tokens never reach client-side script storage.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Kernel;

use Throwable;
use function System\genv;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class Session
{
    /**
     * Start the session if not already active.
     *
     * Cookie parameters come from env so deployments can harden for
     * production without code changes. `session.cookie_secure` defaults
     * to `true` (production-safe); set to `false` in `.env` for local
     * HTTP development.
     *
     * @since  0.0.1
     * @return void
     */
    public static function start (): void
    {
        if (session_status () === PHP_SESSION_ACTIVE) {return;}

        $secure   = genv ('session.cookie_secure', true);
        $httponly = genv ('session.cookie_httponly', true);
        $samesite = (string) genv ('session.cookie_samesite', 'Strict');
        $name     = (string) genv ('session.name', 'NANORADF_SESSID');

        session_set_cookie_params ([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure === true,
            'httponly' => $httponly === true,
            'samesite' => $samesite,
        ]);

        session_name ($name);
        session_start ();
    }

    /**
     * Whether the session has been started in this request.
     *
     * @since  0.0.1
     * @return bool
     */
    public static function isActive (): bool
    {
        return session_status () === PHP_SESSION_ACTIVE;
    }

    /**
     * Read a session value without starting the session.
     *
     * Writers (setTokens, CSRF::token) start the session; readers never do.
     * This keeps rendering paths session-free when nothing was stored.
     *
     * @since  0.0.1
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get (string $key, mixed $default = null): mixed
    {
        return self::isActive () ? ($_SESSION[$key] ?? $default) : $default;
    }

    /**
     * Store a session value.
     *
     * @since  0.0.1
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public static function set (string $key, mixed $value): void
    {
        self::start ();
        $_SESSION[$key] = $value;
    }

    /**
     * Whether a session key is set.
     *
     * @since  0.0.1
     * @param  string $key
     * @return bool
     */
    public static function has (string $key): bool
    {
        self::start ();
        return array_key_exists ($key, $_SESSION);
    }

    /**
     * Remove one session key.
     *
     * @since  0.0.1
     * @param  string $key
     * @return void
     */
    public static function forget (string $key): void
    {
        self::start ();
        unset ($_SESSION[$key]);
    }

    /**
     * Rotate the session id after a privilege change (login/logout).
     *
     * @since  0.0.1
     * @return void
     */
    public static function regenerate (): void
    {
        self::start ();
        session_regenerate_id (true);
    }

    /**
     * Destroy the session and expire the client cookie.
     *
     * @since  0.0.1
     * @return void
     */
    public static function destroy (): void
    {
        if (session_status () !== PHP_SESSION_ACTIVE) {return;}

        $_SESSION = [];
        $params   = session_get_cookie_params ();
        $name     = session_name ();

        session_destroy ();

        setcookie ($name, '', [
            'expires'  => time () - 3600,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
}