<?php

const CYNA_USER_SESSION_LIFETIME = 604800;
const CYNA_PERSIST_COOKIE = 'cyna_persist';

function cyna_session_is_https(): bool
{
    if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function cyna_session_is_admin_request(): bool
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return str_contains($script, '/admin/');
}

function cyna_session_cookie_lifetime(): int
{
    if (cyna_session_is_admin_request()) {
        return 0;
    }

    if (isset($_COOKIE[CYNA_PERSIST_COOKIE]) && $_COOKIE[CYNA_PERSIST_COOKIE] === '1') {
        return CYNA_USER_SESSION_LIFETIME;
    }

    return 0;
}

function cyna_session_set_cookie_params(int $lifetime): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => cyna_session_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params($lifetime, '/', '', false, true);
    }

    ini_set('session.gc_maxlifetime', (string) max($lifetime, 3600));
}

function cyna_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    cyna_session_set_cookie_params(cyna_session_cookie_lifetime());
    session_start();
}

function cyna_session_apply_login_policy(bool $isAdmin): void
{
    if (headers_sent()) {
        return;
    }

    $lifetime = $isAdmin ? 0 : CYNA_USER_SESSION_LIFETIME;

    if ($isAdmin) {
        setcookie(CYNA_PERSIST_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => cyna_session_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[CYNA_PERSIST_COOKIE]);
    } else {
        setcookie(CYNA_PERSIST_COOKIE, '1', [
            'expires'  => time() + CYNA_USER_SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => cyna_session_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[CYNA_PERSIST_COOKIE] = '1';
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        setcookie(session_name(), session_id(), [
            'expires'  => $lifetime > 0 ? time() + $lifetime : 0,
            'path'     => '/',
            'secure'   => cyna_session_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function cyna_session_destroy(): void
{
    if (! headers_sent()) {
        setcookie(CYNA_PERSIST_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => cyna_session_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    unset($_COOKIE[CYNA_PERSIST_COOKIE]);

    $_SESSION = [];

    if (ini_get('session.use_cookies') && ! headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
