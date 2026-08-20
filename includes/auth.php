<?php
/**
 * Authentication and Helper Functions - UgPro Portal
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conf/dbconf.php';

// Flash message helpers
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// CSRF Token Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Auth State Checkers
function is_logged_in() {
    return isset($_SESSION['user_role']) && !empty($_SESSION['user_id']);
}

function is_student() {
    return is_logged_in() && $_SESSION['user_role'] === 'student';
}

function is_employer() {
    return is_logged_in() && $_SESSION['user_role'] === 'employer';
}

function is_admin() {
    return is_logged_in() && $_SESSION['user_role'] === 'admin';
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'guest',
        'avatar' => $_SESSION['user_avatar'] ?? 'images/fl-3.png',
        'course' => $_SESSION['user_course'] ?? ''
    ];
}

// Auth Protection Guards
function require_student_auth($redirectUrl = 'signin_undergraduate.php') {
    if (!is_student()) {
        set_flash('warning', 'Please sign in as an undergraduate to access this page.');
        header("Location: " . BASE_URL . $redirectUrl);
        exit();
    }
}

function require_employer_auth($redirectUrl = 'signin_employer.php') {
    if (!is_employer()) {
        set_flash('warning', 'Please sign in as an employer to access this dashboard.');
        header("Location: " . BASE_URL . $redirectUrl);
        exit();
    }
}

function require_admin_auth($redirectUrl = 'admin/login.php') {
    if (!is_admin()) {
        set_flash('danger', 'Administrator privileges required.');
        header("Location: " . BASE_URL . $redirectUrl);
        exit();
    }
}

// Helper: Secure file uploader
function handle_file_upload($fileArray, $targetDir, $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'], $maxSize = 5242880) {
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $fileName = $fileArray['name'];
    $fileTmpName = $fileArray['tmp_name'];
    $fileSize = $fileArray['size'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed: ' . implode(', ', $allowedExtensions)];
    }

    if ($fileSize > $maxSize) {
        $mb = round($maxSize / (1024 * 1024));
        return ['success' => false, 'error' => "File is too large. Maximum size allowed is {$mb}MB."];
    }

    if (!file_exists($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $uniqueName = uniqid('ugpro_', true) . '.' . $ext;
    $targetFilePath = rtrim($targetDir, '/') . '/' . $uniqueName;

    if (move_uploaded_file($fileTmpName, $targetFilePath)) {
        // Return relative path for web access
        $relativePath = 'uploads/' . basename($targetDir) . '/' . $uniqueName;
        return ['success' => true, 'filePath' => $relativePath, 'fileName' => $uniqueName];
    } else {
        return ['success' => false, 'error' => 'Failed to save uploaded file on server.'];
    }
}

// Helper: Sanitize string inputs
function clean_input($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper: Format relative time
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}
