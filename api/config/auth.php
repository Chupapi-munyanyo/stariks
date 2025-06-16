<?php

use Core\Response;

if (!function_exists('auth_check')) {
    function auth_check(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        Response::json(['success' => false, 'message' => 'Nepieciešama autorizācija'], 401);
        return 0;
    }
}
