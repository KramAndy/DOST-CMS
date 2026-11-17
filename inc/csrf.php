<?php
// CSRF protection helpers.
// One token per session; validated on every state-changing request.

if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    function csrf_verify() {
        $sent = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
        if (!isset($_SESSION['csrf_token']) || !is_string($sent) || $sent === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $sent);
    }
}
