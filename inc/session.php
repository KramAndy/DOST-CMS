<?php
// Hardened session bootstrap.
// Enforces strict-mode sessions, HttpOnly + SameSite cookies, and Secure
// cookies over HTTPS. Must be called instead of a bare session_start().

if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

if (!function_exists('secure_session_start')) {
    function secure_session_start() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if ($https) {
            ini_set('session.cookie_secure', '1');
        }

        session_start();
    }
}
