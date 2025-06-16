<?php
// Simple authentication helper
// Returns user id if logged in, otherwise returns JSON error and exits.

use Core\Response;

if (!function_exists('auth_check')) {
    function auth_check(): int {
        // start session if not already
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        // not logged in
        Response::json(['success' => false, 'message' => 'Nepieciešama autorizācija'], 401);
        // Response::json exits, but keep return for static analysers
        return 0;
    }
}
