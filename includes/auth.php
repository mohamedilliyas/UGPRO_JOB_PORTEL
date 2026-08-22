<?php
/**
 * Authentication and Helper Functions - UgPro Portal
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../conf/dbconf.php';

// Auth Secret Key for HMAC Stateless Cookie Tokens
if (!defined('AUTH_SECRET_KEY')) {
    define('AUTH_SECRET_KEY', getenv('AUTH_SECRET_KEY') ?: 'ugpro_secret_key_2026_vavuniya_edu_lk_secure_session_token_v2');
}

/**
 * Restore session from stateless signed cookie if session is empty (For Serverless)
 */
if (!function_exists('restore_auth_from_cookie')) {
    function restore_auth_from_cookie() {
        if (empty($_SESSION['user_id']) && !empty($_COOKIE['ugpro_auth_token'])) {
            $parts = explode('.', $_COOKIE['ugpro_auth_token'], 2);
            if (count($parts) === 2) {
                list($payload, $sig) = $parts;
                $expectedSig = hash_hmac('sha256', $payload, AUTH_SECRET_KEY);
                if (hash_equals($expectedSig, $sig)) {
                    $data = json_decode(base64_decode($payload), true);
                    if ($data && isset($data['exp']) && $data['exp'] > time() && !empty($data['id'])) {
                        $role = $data['role'] ?? 'student';
                        $id = (int)$data['id'];
                        $name = $data['name'] ?? 'User';

                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $data['email'] ?? '';
                        $_SESSION['user_avatar'] = !empty($data['avatar']) ? $data['avatar'] : ($role === 'employer' ? 'images/google.png' : 'images/fl-3.png');
                        $_SESSION['user_course'] = $data['course'] ?? '';
                        $_SESSION['fullname'] = $name;
                        $_SESSION['course'] = $data['course'] ?? '';

                        // Role-specific convenience session variables
                        if ($role === 'employer') {
                            $_SESSION['employer_id'] = $id;
                            $_SESSION['company_name'] = $name;
                        } elseif ($role === 'student') {
                            $_SESSION['student_id'] = $id;
                        } elseif ($role === 'admin') {
                            $_SESSION['admin_id'] = $id;
                            $_SESSION['admin_logged_in'] = true;
                        }
                    }
                }
            }
        }
    }
}

// Auto-run cookie restoration on file include
restore_auth_from_cookie();

/**
 * Set user session state in $_SESSION and encrypted auth cookie
 */
if (!function_exists('set_user_session')) {
    function set_user_session($id, $role, $name, $email, $avatar = '', $course = '') {
        $avatarPath = !empty($avatar) ? $avatar : ($role === 'employer' ? 'images/google.png' : 'images/fl-3.png');
        $id = (int)$id;

        // 1. Populate PHP Session
        $_SESSION['user_id'] = $id;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_avatar'] = $avatarPath;
        $_SESSION['user_course'] = $course;
        $_SESSION['fullname'] = $name;
        $_SESSION['course'] = $course;

        // Role-specific convenience session variables
        if ($role === 'employer') {
            $_SESSION['employer_id'] = $id;
            $_SESSION['company_name'] = $name;
        } elseif ($role === 'student') {
            $_SESSION['student_id'] = $id;
        } elseif ($role === 'admin') {
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_logged_in'] = true;
        }

        // 2. Issue HMAC-SHA256 signed stateless cookie (valid for 14 days)
        $data = [
            'id' => $id,
            'role' => $role,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatarPath,
            'course' => $course,
            'exp' => time() + (86400 * 14)
        ];

        $payload = base64_encode(json_encode($data));
        $sig = hash_hmac('sha256', $payload, AUTH_SECRET_KEY);
        $cookieVal = $payload . '.' . $sig;

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        setcookie('ugpro_auth_token', $cookieVal, [
            'expires' => time() + (86400 * 14),
            'path' => '/',
            'httponly' => true,
            'secure' => $isHttps,
            'samesite' => 'Lax'
        ]);

        $_COOKIE['ugpro_auth_token'] = $cookieVal;
    }
}

/**
 * Clear user session state from $_SESSION and auth cookie
 */
if (!function_exists('clear_user_session')) {
    function clear_user_session() {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        setcookie('ugpro_auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        unset($_COOKIE['ugpro_auth_token']);
    }
}

// Flash message helpers
if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// CSRF Token Protection
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Auth State Checkers
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        restore_auth_from_cookie();
        return isset($_SESSION['user_role']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('is_student')) {
    function is_student() {
        return is_logged_in() && $_SESSION['user_role'] === 'student';
    }
}

if (!function_exists('is_employer')) {
    function is_employer() {
        return is_logged_in() && $_SESSION['user_role'] === 'employer';
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return is_logged_in() && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('current_user')) {
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
}

// Auth Protection Guards
if (!function_exists('require_student_auth')) {
    function require_student_auth($redirectUrl = 'signin_undergraduate.php') {
        if (!is_student()) {
            set_flash('warning', 'Please sign in as an undergraduate to access this page.');
            header("Location: " . BASE_URL . $redirectUrl);
            exit();
        }
    }
}

if (!function_exists('require_employer_auth')) {
    function require_employer_auth($redirectUrl = 'signin_employer.php') {
        if (!is_employer()) {
            set_flash('warning', 'Please sign in as an employer to access this dashboard.');
            header("Location: " . BASE_URL . $redirectUrl);
            exit();
        }
    }
}

if (!function_exists('require_admin_auth')) {
    function require_admin_auth($redirectUrl = 'admin/login.php') {
        if (!is_admin()) {
            set_flash('danger', 'Administrator privileges required.');
            header("Location: " . BASE_URL . $redirectUrl);
            exit();
        }
    }
}

// Helper: Secure file uploader
if (!function_exists('handle_file_upload')) {
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
            $relativePath = 'uploads/' . basename($targetDir) . '/' . $uniqueName;
            return ['success' => true, 'filePath' => $relativePath, 'fileName' => $uniqueName];
        } else {
            return ['success' => false, 'error' => 'Failed to save uploaded file on server.'];
        }
    }
}

// Helper: Sanitize string inputs
if (!function_exists('clean_input')) {
    function clean_input($data) {
        if ($data === null) return '';
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Helper: Format relative time
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' mins ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        return date('M d, Y', $timestamp);
    }
}
