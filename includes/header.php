<?php
/**
 * Global Header Component - UgPro
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UgPro - University Powered Career & Job Portal connecting undergraduates with top industry employers.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>images/logo.png">

    <!-- DNS Prefetch & Preconnect for maximum speed -->
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Optimized Google Fonts with swap -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons & Boxicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css?v=3.4">
    <style>
        /* Critical Navbar & Dropdown Styling */
        .nav-menu .dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18) !important;
            border-radius: 12px !important;
            padding: 8px !important;
            min-width: 240px !important;
            z-index: 99999 !important;
        }
        .nav-menu .dropdown-menu .dropdown-item,
        .nav-menu .dropdown-menu li a {
            color: #1e293b !important;
            background-color: transparent !important;
            font-size: 0.92rem !important;
            font-weight: 500 !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            transition: background 0.15s ease, color 0.15s ease !important;
        }
        .nav-menu .dropdown-menu .dropdown-item:hover,
        .nav-menu .dropdown-menu li a:hover {
            background-color: #f1f5f9 !important;
            color: #0f3d32 !important;
        }
        .nav-menu .dropdown-header {
            color: #64748b !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 6px 12px 4px !important;
        }
        .nav-menu .dropdown-divider {
            border-top: 1px solid #e2e8f0 !important;
            margin: 6px 0 !important;
        }
    </style>
</head>
<body>
