<?php
/**
 * Global Navigation Bar Component - UgPro
 */
require_once __DIR__ . '/auth.php';
$user = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- Header Section -->
<header class="main-header">
    <div class="obj-width d-flex align-items-center justify-content-between">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>index.php" class="brand-logo d-flex align-items-center text-decoration-none">
            <img class="logo-img" src="<?= BASE_URL ?>images/logo.png" alt="UgPro Logo">
            <span class="brand-text">Ug<span>Pro</span></span>
        </a>

        <!-- Desktop Navigation Menu -->
        <ul id="menu" class="nav-menu">
            <li><a href="<?= BASE_URL ?>index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>jobs.php" class="<?= ($currentScript === 'jobs.php' || $currentScript === 'job_details.php') ? 'active' : '' ?>">Browse Jobs</a></li>
            <li><a href="<?= BASE_URL ?>browse_candidates.php" class="<?= $currentScript === 'browse_candidates.php' ? 'active' : '' ?>">Talent Pool</a></li>
            <li><a href="<?= BASE_URL ?>contact.php" class="<?= $currentScript === 'contact.php' ? 'active' : '' ?>">Contact Us</a></li>
            
            <?php if (is_student()): ?>
                <!-- Student Dropdown -->
                <li class="nav-item dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle user-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= BASE_URL ?><?= !empty($user['avatar']) ? $user['avatar'] : 'images/fl-3.png' ?>" class="nav-avatar" alt="Avatar">
                        <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header">Undergraduate Portal</li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile_undergraduate.php"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile_undergraduate.php?tab=applications"><i class="bi bi-file-earmark-check me-2"></i>My Applications</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile_undergraduate.php?tab=saved"><i class="bi bi-bookmark me-2"></i>Saved Jobs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            <?php elseif (is_employer()): ?>
                <!-- Employer Dropdown -->
                <li class="nav-item dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle user-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= BASE_URL ?><?= !empty($user['avatar']) ? $user['avatar'] : 'images/google.png' ?>" class="nav-avatar" alt="Logo">
                        <span><?= htmlspecialchars($user['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header">Employer Portal</li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>profile_employer.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>employer_post_job.php"><i class="bi bi-plus-circle me-2"></i>Post a New Job</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>browse_candidates.php"><i class="bi bi-people me-2"></i>Search Talent</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            <?php elseif (is_admin()): ?>
                <!-- Admin Link -->
                <li><a href="<?= BASE_URL ?>admin/index.php" class="btn btn-warning btn-sm text-dark px-3 py-1 fw-bold rounded-pill"><i class="bi bi-shield-lock me-1"></i> Admin Panel</a></li>
                <li><a href="<?= BASE_URL ?>logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i></a></li>
            <?php else: ?>
                <!-- Guest Actions -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle signin-nav-link" href="#" role="button" data-bs-toggle="dropdown">Sign In</a>
                    <ul class="dropdown-menu shadow-sm">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>signin_undergraduate.php"><i class="bi bi-mortarboard me-2"></i>As Undergraduate</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>signin_employer.php"><i class="bi bi-building me-2"></i>As Employer</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-muted" href="<?= BASE_URL ?>admin/login.php"><i class="bi bi-shield-lock me-2"></i>Admin Login</a></li>
                    </ul>
                </li>
                <li>
                    <button onclick="showRegisterOptions()" class="btn-register" id="w-btn">Register</button>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Mobile Menu Toggle Button -->
        <i id="bar" class='bx bx-menu mobile-menu-icon'></i>
    </div>
</header>

<!-- Role Selection Modal for Register -->
<div id="registerModal" class="modal-backdrop-custom" style="display: none;">
    <div class="role-modal-card">
        <span onclick="closeModal()" class="modal-close-btn">&times;</span>
        <div class="role-modal-icon">
            <i class="bi bi-person-badge"></i>
        </div>
        <h3>Join UgPro</h3>
        <p class="text-muted small">Choose your account type to get started</p>
        
        <div class="role-options">
            <a href="<?= BASE_URL ?>signup_undergraduate.php" class="role-btn student-role-btn text-decoration-none">
                <div class="role-btn-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="role-btn-text">
                    <strong>I am an Undergraduate</strong>
                    <span>Find internships, verified jobs & build profile</span>
                </div>
                <i class="bi bi-chevron-right ms-auto"></i>
            </a>

            <a href="<?= BASE_URL ?>signup_employer.php" class="role-btn employer-role-btn text-decoration-none">
                <div class="role-btn-icon"><i class="bi bi-building-fill"></i></div>
                <div class="role-btn-text">
                    <strong>I am an Employer</strong>
                    <span>Post job vacancies & hire top university talent</span>
                </div>
                <i class="bi bi-chevron-right ms-auto"></i>
            </a>
        </div>
        
        <div class="text-center mt-3 small text-muted">
            Already have an account? <a href="<?= BASE_URL ?>signin_undergraduate.php" class="text-success fw-bold">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/alerts.php'; ?>
