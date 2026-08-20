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

// Attempt database connection with intelligent password fallback for local dev
$passwordsToTry = [DB_PASS, '', 'mariadb', 'root'];
$connect = null;
$lastError = '';

// Filter unique passwords to avoid duplicate attempts
$passwordsToTry = array_values(array_unique($passwordsToTry));

foreach ($passwordsToTry as $pwd) {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect(DB_HOST, DB_USER, $pwd, DB_NAME, DB_PORT);
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

// If connection failed completely
if (!$connect) {
    // Check if database simply does not exist yet (error 1049)
    $tmpConn = @mysqli_connect(DB_HOST, DB_USER, '', '', DB_PORT);
    if (!$tmpConn) {
        $tmpConn = @mysqli_connect(DB_HOST, DB_USER, 'mariadb', '', DB_PORT);
    }
    
    if ($tmpConn) {
        // Attempt to auto-create database if possible
        @mysqli_query($tmpConn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        @mysqli_close($tmpConn);
        // Retry connect
        $connect = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if (!$connect) {
            $connect = @mysqli_connect(DB_HOST, DB_USER, '', DB_NAME, DB_PORT);
        }
    }
}

// Automatic Cloud Schema Migration: If connected, check if tables exist; if not, auto-import database.sql
if ($connect) {
    $tblCheck = @mysqli_query($connect, "SHOW TABLES LIKE 'undergraduate'");
    if ($tblCheck && mysqli_num_rows($tblCheck) === 0) {
        $sqlPath = __DIR__ . '/../database.sql';
        if (file_exists($sqlPath)) {
            $sqlContent = file_get_contents($sqlPath);
            // Remove comments and execute multi-query
            if (!empty($sqlContent)) {
                @mysqli_multi_query($connect, $sqlContent);
                // Flush multi-query results to prevent Commands out of sync errors
                while (@mysqli_next_result($connect)) {
                    if ($res = @mysqli_store_result($connect)) {
                        @mysqli_free_result($res);
                    }
                }
            }
        }
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
