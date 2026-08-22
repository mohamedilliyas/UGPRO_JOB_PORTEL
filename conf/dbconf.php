<?php
/**
 * Ultra-Fast Database Connection Configuration & Auto-Migration - UgPro Portal
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/fallback_data.php';

// Database configuration constants (for backward compatibility)
if (!defined('SERVERNAME')) define('SERVERNAME', DB_HOST);
if (!defined('USERNAME')) define('USERNAME', DB_USER);
if (!defined('PASSWORD')) define('PASSWORD', DB_PASS);
if (!defined('DBNAME')) define('DBNAME', DB_NAME);

$connect = null;
$db_error_message = '';
$is_database_connected = false;

// Fast single-pass connection attempt (1.5s timeout for instant responses)
$passwordsToTry = [DB_PASS];
if (in_array(DB_HOST, ['127.0.0.1', 'localhost']) && empty(DB_PASS)) {
    $passwordsToTry = ['', 'root', 'mariadb'];
}

foreach ($passwordsToTry as $pwd) {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = mysqli_init();
        if (!$conn) continue;

        // Ultra-fast 1.5s connection timeout to eliminate loading lag
        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
            @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        }

        if (DB_SSL_ENABLED) {
            if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
                @mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            }
            $flags = defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT') 
                ? (MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT | MYSQLI_CLIENT_SSL) 
                : MYSQLI_CLIENT_SSL;
            
            $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, $pwd, DB_NAME, (int)DB_PORT, NULL, $flags);
        } else {
            $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, $pwd, DB_NAME, (int)DB_PORT);
        }

        if ($connected) {
            $connect = $conn;
            $is_database_connected = true;
            mysqli_set_charset($connect, 'utf8mb4');
            break;
        } else {
            $db_error_message = mysqli_connect_error() ?: 'Unable to connect to database host: ' . DB_HOST;
        }
    } catch (Throwable $e) {
        $db_error_message = $e->getMessage();
    }
}

/**
 * Check if database is active and connected
 */
if (!function_exists('is_db_connected')) {
    function is_db_connected() {
        global $connect, $is_database_connected;
        return ($connect instanceof mysqli && $is_database_connected);
    }
}

/**
 * Get the last database error message
 */
if (!function_exists('get_db_error')) {
    function get_db_error() {
        global $db_error_message;
        return $db_error_message;
    }
}

/**
 * PDO Helper Function for secure prepared queries
 */
function get_pdo() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (Throwable $e) {
            // Silently handled
        }
    }
    return $pdo;
}
