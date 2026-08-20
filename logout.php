<?php
/**
 * Unified User Logout - UgPro
 */
require_once __DIR__ . '/config.php';

// Unset all session variables
$_SESSION = [];

// Destroy session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start fresh session for flash message
session_start();
$_SESSION['flash'] = [
    'type' => 'info',
    'message' => 'You have been successfully signed out.'
];

header("Location: " . BASE_URL . "index.php");
exit();
