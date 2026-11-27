<?php declare (strict_types=1);

/**
 * Phase 5 auth/session smoke check (decision D7: test files live in /tests).
 * Run from anywhere: php tests/auth_check.php
 *
 * Covers: session stays dormant after boot, token storage + expiry,
 * corrupt-session safety, CSRF token round-trip, API guard fail-closed.
 * Pass = exit 0.
 */

chdir (dirname (__DIR__).'/public');
require dirname (__DIR__).'/public/index.php';

// CLI: buffer output so session_start() (called by Session::set) does not
// emit "headers already sent" after the first echo. Production (web) is
// unaffected — the Router guard runs before Dispatcher::emit().
ob_start ();

use System\Kernel\Session;
use System\Services\AuthService;
use System\Services\CSRFService;
use System\Kernel\Router;

$pass = 0;
$fail = 0;

function check (string $label, callable $test): void
{
    global $pass, $fail;

    try
    {
        if ($test () === true) {$pass++; echo "  ok   {$label}\n";}
        else {$fail++; echo "  FAIL {$label}\n";}
    }
    catch (\Throwable $e)
    {
        $fail++;
        echo "  FAIL {$label} — ".$e->getMessage ()."\n";
    }
}

echo "Session lifecycle\n";
check ('session is not started by the bootstrap', static fn (): bool => !Session::isActive ());

echo "Token storage & expiry\n";
AuthService::setTokens ('access-1', 'refresh-1', 3600, ['id' => 7]);
check ('access token is stored and readable', static fn (): bool => AuthService::getAccessToken () === 'access-1');
check ('refresh token is stored', static fn (): bool => AuthService::getRefreshToken () === 'refresh-1');
check ('user data round-trips', static fn (): bool => AuthService::getUser () === ['id' => 7]);
check ('isAuthenticated reflects a valid token', static fn (): bool => AuthService::isAuthenticated ());

check ('an expired token is not valid', static function (): bool {
    AuthService::setTokens ('access-2', 'refresh-2', 1);
    return AuthService::getAccessToken () === null;
});

check ('an expired token with a refresh flag is refreshable', static function (): bool {
    AuthService::setTokens ('access-3', 'refresh-3', 1);
    return AuthService::canRefresh ();
});

check ('a clear() removes stored tokens', static function (): bool {
    AuthService::setTokens ('access-4', 'refresh-4', 3600);
    AuthService::clear ();
    return AuthService::isAuthenticated () === false;
});

echo "Corrupt session safety\n";
check ('a non-array record returns no access token', static function (): bool {
    Session::set ('auth', 'garbage');
    return AuthService::getAccessToken () === null;
});
check ('a non-array record yields an empty user', static function (): bool {
    Session::set ('auth', 123);
    return AuthService::getUser () === [] && AuthService::getRefreshToken () === null;
});
AuthService::clear ();

echo "CSRF token\n";
$token = CSRFService::token ();
check ('token is generated', static fn (): bool => $token !== '');
check ('stored token matches', static fn (): bool => CSRFService::validate ($token));
check ('a wrong token is rejected', static fn (): bool => !CSRFService::validate ('wrong'));
check ('an empty token is rejected', static fn (): bool => !CSRFService::validate (''));

$regenerated = CSRFService::regenerate ();
check ('regenerate() rotates the token', static fn (): bool => $regenerated !== $token && CSRFService::validate ($regenerated));
check ('the old token is rejected after rotation', static fn (): bool => !CSRFService::validate ($token));

echo "API guard fails closed\n";
$response = Router::dispatch ('GET', '/api/v1/health', false);
check ('an unauthenticated API call is rejected with 401', static fn (): bool => $response['status'] === 401);

echo "CSRF gate on Router::run\n";
// CSRF only fires on state-changing methods; GET/HEAD bypass it.
$_POST['_csrf'] = 'invalid';
$response = Router::dispatch ('GET', '/api/v1/health', false);
check ('GET bypasses CSRF gate (stateless)', static fn (): bool => $response['status'] === 401);

echo "\n";
echo ($fail === 0) ? "PASS — {$pass} passed, 0 failed\n" : "FAIL — {$fail} failed\n";
ob_end_flush ();
exit ($fail === 0 ? 0 : 1);