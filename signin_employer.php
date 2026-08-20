<?php
/**
 * Employer Sign In - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_employer()) {
    header("Location: profile_employer.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        if ($connect) {
            $stmt = $connect->prepare("SELECT id, company_name, email, password, company_logo, status FROM employer WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                if ($row['status'] === 'suspended') {
                    $error = "This company account is currently suspended. Please contact portal administration.";
                } elseif (password_verify($password, $row['password'])) {
                    // Set complete user session
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['company_name'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_role'] = 'employer';
                    $_SESSION['user_avatar'] = !empty($row['company_logo']) ? $row['company_logo'] : 'images/google.png';

                    // Backward compatibility
                    $_SESSION['employer_id'] = $row['id'];
                    $_SESSION['company_name'] = $row['company_name'];

                    set_flash('success', "Welcome back, " . htmlspecialchars($row['company_name']) . "!");
                    header("Location: profile_employer.php");
                    exit();
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No employer account found with this email.";
            }
            $stmt->close();
        } else {
            $error = "Database connection error.";
        }
    } else {
        $error = "Please provide both your company email and password.";
    }
}

$pageTitle = "Employer Sign In - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card row g-0" style="max-width: 840px;">
        <div class="col-lg-5 auth-card-sidebar d-none d-lg-flex">
            <div>
                <a href="<?= BASE_URL ?>index.php" class="text-white text-decoration-none">
                    <img src="<?= BASE_URL ?>images/logo.png" width="70" height="70" alt="Logo" class="mb-3">
                    <h2>UgPro</h2>
                </a>
                <p>Recruit the brightest university undergraduates, review live applications, and manage hiring campaigns.</p>
            </div>
            
            <div class="auth-sidebar-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            
            <div>
                <p class="small mb-0">New company to UgPro?</p>
                <a href="<?= BASE_URL ?>signup_employer.php" class="btn btn-outline-light btn-sm rounded-pill px-4 mt-2">Register Company</a>
            </div>
        </div>

        <div class="col-lg-7 auth-card-body">
            <h2 class="auth-form-title">Employer Sign In</h2>
            <p class="text-muted small mb-4">Access your hiring dashboard and post new vacancies</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm rounded-3 py-2">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="signin_employer.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Company Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-building text-muted"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" placeholder="recruitment@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="password" class="form-label">Password</label>
                        <a href="contact.php" class="small text-muted text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberEmployer">
                    <label class="form-check-label small text-muted" for="rememberEmployer">Remember this company account</label>
                </div>

                <button type="submit" class="btn-primary-ugpro py-3">Sign In to Dashboard</button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <p class="text-muted small mb-2">Are you an undergraduate student looking for jobs?</p>
                <a href="<?= BASE_URL ?>signin_undergraduate.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-mortarboard me-1"></i> Switch to Student Sign In</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
