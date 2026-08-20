<?php
/**
 * Database Connection Configuration & Auto-Migration - UgPro Portal
 */

require_once __DIR__ . '/../config.php';

// Database configuration constants (for backward compatibility)
if (!defined('SERVERNAME')) define('SERVERNAME', DB_HOST);
if (!defined('USERNAME')) define('USERNAME', DB_USER);
if (!defined('PASSWORD')) define('PASSWORD', DB_PASS);
if (!defined('DBNAME')) define('DBNAME', DB_NAME);

// Attempt database connection with intelligent SSL and password fallback
$passwordsToTry = [DB_PASS, '', 'mariadb', 'root'];
$connect = null;
$lastError = '';

$passwordsToTry = array_values(array_unique($passwordsToTry));

foreach ($passwordsToTry as $pwd) {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);

        // 1. First attempt SSL connection (for cloud MySQL providers like Aiven)
        $conn = mysqli_init();
        if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
            @mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        }
        
        $flags = defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT') ? (MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT | MYSQLI_CLIENT_SSL) : MYSQLI_CLIENT_SSL;
        $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, $pwd, DB_NAME, (int)DB_PORT, NULL, $flags);

        if ($connected) {
            $connect = $conn;
            mysqli_set_charset($connect, 'utf8mb4');
            break;
        }

        // 2. Fallback to standard connect (for local dev / non-SSL servers)
        $conn = @mysqli_connect(DB_HOST, DB_USER, $pwd, DB_NAME, (int)DB_PORT);
        if ($conn) {
            $connect = $conn;
            mysqli_set_charset($connect, 'utf8mb4');
            break;
        } else {
            $lastError = mysqli_connect_error();
        }
    } catch (Exception $e) {
        $lastError = $e->getMessage();
    }
}

// Automatic Cloud Schema Migration: If connected, check if tables exist; if not, auto-import database.sql
if ($connect) {
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
        // Self-healing: Ensure demo account passwords in existing databases match updated bcrypt hashes
        $adminHash = '$2y$10$fuzTKOKUXYh4A0cCGm/tYefBIt5nF7xEn/62OL2PXSYYL0GhUsnK6'; // admin123
        $studentHash = '$2y$10$/ugmhBfdOwEvCe7Nl2ykw.1yvY2QqhxMg/s661DPVcVzCw8kzv3pC'; // student123
        $employerHash = '$2y$10$s3bsdmDM7srwijQw7AOD6u.lcm9OxfzvB.hxQNUzPXF23VhbIQVHi'; // employer123

        @mysqli_query($connect, "UPDATE admins SET password = '$adminHash' WHERE username = 'admin' AND password NOT LIKE '$2y$10$fuz%'");
        @mysqli_query($connect, "UPDATE undergraduate SET password = '$studentHash' WHERE email = 'illiyas@vau.ac.lk' AND password NOT LIKE '$2y$10$/ug%'");
        @mysqli_query($connect, "UPDATE employer SET password = '$employerHash' WHERE email IN ('careers@virtusa.com', 'recruitment@wso2.com', 'careers@ifs.com', 'hr@creativesoftware.com') AND password NOT LIKE '$2y$10$s3b%'");
    }
}

// If still unable to connect, handle gracefully
if (!$connect) {
    $dbError = "Database Connection Failed: " . ($lastError ?: "Unable to connect to MySQL server at " . DB_HOST);
    if (PHP_SAPI !== 'cli') {
        echo "<div style='font-family: sans-serif; background: #fff3f3; color: #d32f2f; padding: 20px; border-left: 5px solid #d32f2f; margin: 20px auto; max-width: 800px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>";
        echo "<h3 style='margin-top:0;'>⚠️ Database Connection Error</h3>";
        echo "<p>{$dbError}</p>";
        echo "<p>Please ensure your MySQL service is running and configured correctly in environment variables or <code>config.php</code>.</p>";
        echo "</div>";
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
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $passwords = [DB_PASS, '', 'mariadb', 'root'];
        $passwords = array_values(array_unique($passwords));
        
        foreach ($passwords as $pwd) {
            try {
                $pdo = new PDO($dsn, DB_USER, $pwd, $options);
                break;
            } catch (PDOException $e) {
                // Continue to try next password
            }
        }
    }
    return $pdo;
}
