<?php
/**
 * Redirect to root jobs.php
 */
require_once __DIR__ . '/../config.php';
header("Location: " . BASE_URL . "jobs.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();