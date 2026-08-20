<?php
/**
 * Vercel Serverless Entrypoint & Router - UgPro
 */

// Parse the requested path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Handle Root
if (empty($path) || $path === 'index.php') {
    require __DIR__ . '/../index.php';
    exit();
}

// Map to physical file in root or subdirectories
$targetFile = __DIR__ . '/../' . $path;

// Handle directories (e.g. /admin -> /admin/index.php)
if (is_dir($targetFile)) {
    if (file_exists($targetFile . '/index.php')) {
        $targetFile .= '/index.php';
    }
}

// If request is e.g. /jobs -> check jobs.php
if (!file_exists($targetFile) && file_exists($targetFile . '.php')) {
    $targetFile .= '.php';
}

if (file_exists($targetFile) && is_file($targetFile)) {
    // If it's a PHP file, execute it
    if (pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = realpath($targetFile);
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim($path, '/');
        require $targetFile;
        exit();
    }
    
    // If it's a static file (css, image), serve with appropriate header
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'json' => 'application/json'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($targetFile);
    exit();
}

// Fallback to index.php
require __DIR__ . '/../index.php';
