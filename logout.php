<?php
/**
 * User Logout Handler
 */

require_once __DIR__ . '/config/config.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session for flash message
session_start();
setFlash('info', 'You have been successfully logged out.');

header("Location: " . BASE_URL . "/login.php");
exit;
