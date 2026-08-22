<?php
/**
 * Undergraduate Sign In - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_student()) {
    header("Location: profile_undergraduate.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $authenticated = false;

        if (is_db_connected()) {
            try {
                $stmt = @$connect->prepare("SELECT id, full_name, email, password, course, profile_image, status FROM undergraduate WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows === 1) {
                        $row = $result->fetch_assoc();
                        
                        if ($row['status'] === 'banned') {
                            $error = "Your student account has been suspended. Please contact university career guidance.";
                        } elseif (password_verify($password, $row['password'])) {
                            set_user_session(
                                $row['id'],
                                'student',
                                $row['full_name'],
                                $row['email'],
                                !empty($row['profile_image']) ? $row['profile_image'] : 'images/fl-3.png',
                                $row['course'] ?? ''
                            );
                            $authenticated = true;
                        } else {
                            $error = "Incorrect password. Please try again.";
                        }
                    }
                    $stmt->close();
                }
            } catch (Throwable $e) {
                // Silently fallback
            }
        }

        // Fallback demo authentication if DB offline or testing demo account
        if (!$authenticated && empty($error)) {
            $fallbackUser = verify_fallback_demo_auth('student', $email, $password);
            if ($fallbackUser) {
                set_user_session(
                    $fallbackUser['id'],
                    'student',
                    $fallbackUser['full_name'],
                    $fallbackUser['email'],
                    $fallbackUser['profile_image'],
                    $fallbackUser['course']
                );
                $authenticated = true;
            } else {
                $error = "No student account found with this email. Please check your credentials or create an account.";
            }
        }

        if ($authenticated) {
            set_flash('success', "Welcome back, " . htmlspecialchars($_SESSION['user_name']) . "!");
            $redirect = $_GET['redirect'] ?? 'profile_undergraduate.php';
            header("Location: " . BASE_URL . $redirect);
            exit();
        }
    } else {
        $error = "Please provide both a valid email and password.";
    }
}

$pageTitle = "Undergraduate Sign In - UgPro";
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
                <p>Welcome back! Sign in to view job recommendations, track applications, and engage with employers.</p>
            </div>
            
            <div class="auth-sidebar-icon">
                <i class="bi bi-person-workspace"></i>
            </div>
            
            <div>
                <p class="small mb-0">New to UgPro?</p>
                <a href="<?= BASE_URL ?>signup_undergraduate.php" class="btn btn-outline-light btn-sm rounded-pill px-4 mt-2">Create Account</a>
            </div>
        </div>

        <div class="col-lg-7 auth-card-body">
            <h2 class="auth-form-title">Student Sign In</h2>
            <p class="text-muted small mb-4">Access your university career dashboard and job applications</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm rounded-3 py-2">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="signin_undergraduate.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" placeholder="name@vau.ac.lk" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="password" class="form-label">Password</label>
                        <a href="contact.php" class="small text-muted text-decoration-none">Need help?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label small text-muted" for="rememberMe">Keep me signed in</label>
                </div>

                <button type="submit" class="btn-primary-ugpro py-3">Sign In as Undergraduate</button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <p class="text-muted small mb-2">Are you an employer or recruiter?</p>
                <a href="<?= BASE_URL ?>signin_employer.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-building me-1"></i> Switch to Employer Sign In</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
