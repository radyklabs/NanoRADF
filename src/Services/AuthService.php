<?php declare (strict_types=1);

/**
 * Auth token storage — access/refresh tokens live only in the session.
 * --
 * This class stores and reads tokens; it does not log in, log out, or call a
 * backend. Those are application features. Keeping storage separate lets any
 * module wire its own login/refresh flow without changing the framework.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Services;

use System\Kernel\Session;
use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class AuthService
{
    private const KEY = 'auth';

    /**
     * Store tokens in the session with an expiry timestamp.
     *
     * A 60-second buffer keeps a nearly-expired token from being handed out.
     *
     * @since  0.0.1
     * @param  string $accessToken
     * @param  string $refreshToken
     * @param  int    $expiresIn   Lifetime in seconds.
     * @param  array  $user        Optional user data.
     * @return void
     */
    public static function setTokens (string $accessToken, string $refreshToken, int $expiresIn, array $user = []): void
    {
        Session::set (self::KEY, [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at'    => time () + $expiresIn - 60,
            'user'          => $user,
        ]);
    }

    /**
     * Read the stored auth record, or null when absent/corrupt.
     *
     * @since  0.0.1
     * @return array<string, mixed>|null
     */
    private static function record (): ?array
    {
        $auth = Session::get (self::KEY);
        return is_array ($auth) ? $auth : null;
    }

    /**
     * Current access token, or null when missing or expired.
     *
     * @since  0.0.1
     * @return string|null
     */
    public static function getAccessToken (): ?string
    {
        $auth = self::record ();
        if ($auth === null) {return null;}

        if ((int) ($auth['expires_at'] ?? 0) <= time ()) {return null;}

        $token = $auth['access_token'] ?? '';
        return is_string ($token) && $token !== '' ? $token : null;
    }

    /**
     * Stored refresh token, or null when absent.
     *
     * @since  0.0.1
     * @return string|null
     */
    public static function getRefreshToken (): ?string
    {
        $token = self::record ()['refresh_token'] ?? null;
        return is_string ($token) && $token !== '' ? $token : null;
    }

    /**
     * Stored user data.
     *
     * @since  0.0.1
     * @return array<string, mixed>
     */
    public static function getUser (): array
    {
        $user = self::record ()['user'] ?? [];
        return is_array ($user) ? $user : [];
    }

    /**
     * Whether a valid (unexpired) access token is stored.
     *
     * @since  0.0.1
     * @return bool
     */
    public static function isAuthenticated (): bool
    {
        return self::getAccessToken () !== null;
    }

    /**
     * Whether the access token expired but a refresh token remains.
     *
     * @since  0.0.1
     * @return bool
     */
    public static function canRefresh (): bool
    {
        $auth = self::record ();
        if ($auth === null) {return false;}

        return (int) ($auth['expires_at'] ?? 0) <= time () && self::getRefreshToken () !== null;
    }

    /**
     * Token expiry timestamp, or null when absent.
     *
     * @since  0.0.1
     * @return int|null
     */
    public static function getExpiresAt (): ?int
    {
        $expires = self::record ()['expires_at'] ?? null;
        return is_int ($expires) ? $expires : null;
    }

    /**
     * Clear stored tokens. Session id rotation belongs to the caller.
     *
     * @since  0.0.1
     * @return void
     */
    public static function clear (): void
    {
        Session::forget (self::KEY);
    }
}