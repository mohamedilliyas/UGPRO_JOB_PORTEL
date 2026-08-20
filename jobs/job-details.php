<?php
/**
 * Redirect to root job_details.php
 */
require_once __DIR__ . '/../config.php';
header("Location: " . BASE_URL . "job_details.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();