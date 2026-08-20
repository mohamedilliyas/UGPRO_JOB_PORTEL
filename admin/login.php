<?php
/**
 * Administrator Login - UgPro
 */
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in as admin
if (is_admin()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($usernameOrEmail) && !empty($password)) {
        if ($connect) {
            $stmt = $connect->prepare("SELECT id, username, email, password, role FROM admins WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    // Set complete user session with stateless signed cookie
                    set_user_session($row['id'], 'admin', $row['username'], $row['email'], 'images/logo.png');

                    set_flash('success', "Welcome to the Admin Portal, " . htmlspecialchars($row['username']) . "!");
                    header("Location: " . BASE_URL . "admin/index.php");
                    exit();
                } else {
                    $error = "Invalid administrator credentials.";
                }
            } else {
                $error = "No administrator account found with those credentials.";
            }
            $stmt->close();
        } else {
            $error = "Database connection failed.";
        }
    } else {
        $error = "Please enter both username/email and password.";
    }
}

$pageTitle = "Administrator Sign In - UgPro";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card row g-0" style="max-width: 500px;">
        <div class="col-12 auth-card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="bg-dark text-white p-3 rounded-circle d-inline-flex mb-3 shadow">
                    <i class="bi bi-shield-lock-fill fs-2 text-warning"></i>
                </div>
                <h2 class="auth-form-title">Admin Portal</h2>
                <p class="text-muted small">University Coordinator & System Moderation</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm rounded-3 py-2 small mb-4">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Admin Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="admin or admin@ugpro.lk" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Admin Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold shadow-sm">Sign In to Admin Control</button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="<?= BASE_URL ?>index.php" class="text-muted small text-decoration-none">&larr; Return to Public Portal</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
