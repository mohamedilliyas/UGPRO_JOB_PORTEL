<?php
/**
 * Unified User Logout - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Clear both PHP session and stateless auth cookie
clear_user_session();

// Start clean session for flash notification
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
set_flash('info', 'You have been successfully signed out. See you soon!');

header("Location: " . BASE_URL . "index.php");
exit();
