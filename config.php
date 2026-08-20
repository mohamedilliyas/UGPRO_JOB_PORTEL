<?php
/**
 * Master Configuration File - UgPro University Job Portal
 */

// Detect serverless environment (Vercel, AWS Lambda, etc.)
$isServerless = (getenv('VERCEL') || getenv('AWS_LAMBDA_FUNCTION_NAME') || !is_writable(__DIR__));

if ($isServerless) {
    if (!is_dir('/tmp/sessions')) {
        @mkdir('/tmp/sessions', 0777, true);
    }
    @session_save_path('/tmp/sessions');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Environment settings
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' or 'production'

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'vavuniyauniversity');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Application Meta
define('APP_NAME', 'UgPro - University Career & Job Portal');
define('APP_TAGLINE', 'Connecting Undergraduates with Top Industry Employers');
define('APP_EMAIL', 'support@ugpro.lk');

// Dynamic Base URL calculation
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Root path helper
define('ROOT_PATH', rtrim(str_replace('\\', '/', __DIR__), '/') . '/');

// Base URL helper
$currentDir = rtrim($scriptDir, '/');
if ($isServerless) {
    $baseUrl = $protocol . $host . '/';
} elseif (substr($currentDir, -6) === '/admin') {
    $baseUrl = $protocol . $host . substr($currentDir, 0, -6) . '/';
} elseif (substr($currentDir, -5) === '/jobs') {
    $baseUrl = $protocol . $host . substr($currentDir, 0, -5) . '/';
} else {
    $baseUrl = $protocol . $host . ($currentDir ? $currentDir . '/' : '/');
}
define('BASE_URL', $baseUrl);

// Upload Directories
if ($isServerless) {
    define('UPLOAD_DIR', '/tmp/uploads/');
} else {
    define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
}
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'profiles/');
define('LOGO_UPLOAD_DIR', UPLOAD_DIR . 'logos/');
define('RESUME_UPLOAD_DIR', UPLOAD_DIR . 'resumes/');

// Ensure upload folders exist safely
foreach ([UPLOAD_DIR, PROFILE_UPLOAD_DIR, LOGO_UPLOAD_DIR, RESUME_UPLOAD_DIR] as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
}
