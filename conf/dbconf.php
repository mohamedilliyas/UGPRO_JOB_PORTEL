<?php
/**
 * Database Connection Configuration & Auto-Migration - UgPro Portal
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

// Attempt database connection with fast timeout and intelligent SSL handling
$passwordsToTry = [DB_PASS, '', 'mariadb', 'root'];
$passwordsToTry = array_values(array_unique($passwordsToTry));

foreach ($passwordsToTry as $pwd) {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = mysqli_init();
        if (!$conn) {
            continue;
        }

        // Set fast connection timeout (4 seconds) so serverless functions don't hang
        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
            @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 4);
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
        }

        // Fallback standard connect if SSL connection didn't connect
        $connStandard = mysqli_init();
        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
            @mysqli_options($connStandard, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        }
        $connectedStandard = @mysqli_real_connect($connStandard, DB_HOST, DB_USER, $pwd, DB_NAME, (int)DB_PORT);
        if ($connectedStandard) {
            $connect = $connStandard;
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

// Automatic Cloud Schema Migration: If connected, check if tables exist; if not, auto-import database.sql
if ($connect && $is_database_connected) {
    try {
        $tblCheck = @mysqli_query($connect, "SHOW TABLES LIKE 'undergraduate'");
        if ($tblCheck && mysqli_num_rows($tblCheck) === 0) {
            $sqlPath = __DIR__ . '/../database.sql';
            if (file_exists($sqlPath)) {
                $sqlContent = file_get_contents($sqlPath);
                if (!empty($sqlContent)) {
                    @mysqli_multi_query($connect, $sqlContent);
                    while (@mysqli_next_result($connect)) {
                        if ($res = @mysqli_store_result($connect)) {
                            @mysqli_free_result($res);
                        }
                    }
                }
            }
        } else {
            // Self-healing: Ensure demo account passwords match updated bcrypt hashes
            $adminHash = '$2y$10$fuzTKOKUXYh4A0cCGm/tYefBIt5nF7xEn/62OL2PXSYYL0GhUsnK6'; // admin123
            $studentHash = '$2y$10$/ugmhBfdOwEvCe7Nl2ykw.1yvY2QqhxMg/s661DPVcVzCw8kzv3pC'; // student123
            $employerHash = '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi'; // employer123

            @mysqli_query($connect, "UPDATE admins SET password = '$adminHash' WHERE username = 'admin' AND password NOT LIKE '$2y$10$fuz%'");
            @mysqli_query($connect, "UPDATE undergraduate SET password = '$studentHash' WHERE email = 'illiyas@vau.ac.lk' AND password NOT LIKE '$2y$10$/ug%'");
            @mysqli_query($connect, "UPDATE employer SET password = '$employerHash' WHERE email IN ('careers@virtusa.com', 'recruitment@wso2.com', 'careers@ifs.com', 'hr@creativesoftware.com') AND password NOT LIKE '$2y$10$s3b%'");
        }
    } catch (Throwable $e) {
        // Schema check error silently caught to avoid crashing
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
            PDO::ATTR_TIMEOUT            => 4,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $passwords = [DB_PASS, '', 'mariadb', 'root'];
        $passwords = array_values(array_unique($passwords));
        
        foreach ($passwords as $pwd) {
            try {
                $pdo = new PDO($dsn, DB_USER, $pwd, $options);
                break;
            } catch (Throwable $e) {
                // Continue to try next password
            }
        }
    }
    return $pdo;
}
