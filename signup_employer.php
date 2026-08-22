<?php
/**
 * Employer Sign Up - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_employer()) {
    header("Location: profile_employer.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = clean_input($_POST['companyName'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $website = clean_input($_POST['website'] ?? '');
    $location = clean_input($_POST['location'] ?? 'Colombo, Sri Lanka');
    $industry = clean_input($_POST['industry'] ?? 'Information Technology');
    $phone = clean_input($_POST['phone'] ?? '');
    $about = clean_input($_POST['about'] ?? '');

    // Validation
    if (empty($companyName)) $errors[] = "Company or Organization name is required.";
    if (!$email) $errors[] = "A valid corporate or business email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

    // Handle Logo Upload
    $logoPath = 'images/google.png'; // Default placeholder
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $logoUpload = handle_file_upload($_FILES['company_logo'], LOGO_UPLOAD_DIR, ['jpg', 'jpeg', 'png', 'webp', 'svg'], 5242880);
        if ($logoUpload['success']) {
            $logoPath = $logoUpload['filePath'];
        } else {
            $errors[] = "Company Logo: " . $logoUpload['error'];
        }
    }

    if (empty($errors)) {
        if (is_db_connected()) {
            try {
                // Check if email already registered
                $checkStmt = @$connect->prepare("SELECT id FROM employer WHERE email = ?");
                if ($checkStmt) {
                    $checkStmt->bind_param("s", $email);
                    $checkStmt->execute();
                    if ($checkStmt->get_result()->num_rows > 0) {
                        $errors[] = "An employer account with this email already exists.";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = @$connect->prepare("INSERT INTO employer (company_name, email, password, company_logo, website, location, industry, about, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("sssssssss", $companyName, $email, $hashedPassword, $logoPath, $website, $location, $industry, $about, $phone);

                            if ($stmt->execute()) {
                                $newId = $stmt->insert_id;
                                set_user_session(
                                    $newId,
                                    'employer',
                                    $companyName,
                                    $email,
                                    !empty($logoPath) ? $logoPath : 'images/google.png'
                                );

                                $_SESSION['employer_id'] = $newId;
                                $_SESSION['company_name'] = $companyName;

                                // Clear stats cache so new employer count is reflected immediately
                                unset($_SESSION['_ugpro_stats'], $_SESSION['_ugpro_stats_time']);

                                set_flash('success', "Welcome to UgPro! Your company account has been created.");
                                header("Location: " . BASE_URL . "profile_employer.php");
                                exit();
                            } else {
                                $errors[] = "Registration failed: " . $connect->error;
                            }
                            $stmt->close();
                        }
                    }
                    $checkStmt->close();
                }
            } catch (Throwable $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            // Simulated demo registration
            set_user_session(
                888,
                'employer',
                $companyName,
                $email,
                !empty($logoPath) ? $logoPath : 'images/google.png'
            );
            $_SESSION['employer_id'] = 888;
            $_SESSION['company_name'] = $companyName;
            set_flash('success', "Welcome to UgPro! Your company account has been created (demo mode).");
            header("Location: " . BASE_URL . "profile_employer.php");
            exit();
        }
    }
}

$pageTitle = "Employer Registration - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card row g-0">
        <div class="col-lg-4 auth-card-sidebar d-none d-lg-flex">
            <div>
                <a href="<?= BASE_URL ?>index.php" class="text-white text-decoration-none">
                    <img src="<?= BASE_URL ?>images/logo.png" width="80" height="80" alt="Logo" class="mb-3">
                    <h2>UgPro</h2>
                </a>
                <p>Post verified vacancies, explore qualified undergraduate talent, and build university hiring pipelines.</p>
            </div>
            
            <div class="auth-sidebar-icon">
                <i class="bi bi-building-check"></i>
            </div>
            
            <div>
                <p class="small mb-0">Already registered your company?</p>
                <a href="<?= BASE_URL ?>signin_employer.php" class="btn btn-outline-light btn-sm rounded-pill px-4 mt-2">Employer Sign In</a>
            </div>
        </div>

        <div class="col-lg-8 auth-card-body">
            <h2 class="auth-form-title">Employer Registration</h2>
            <p class="text-muted small mb-4">Create your recruiter profile to post jobs and recruit top university graduates</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm rounded-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="signup_employer.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="companyName" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="companyName" name="companyName" placeholder="e.g. Virtusa Sri Lanka" value="<?= htmlspecialchars($_POST['companyName'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Work / Recruiter Email *</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="recruitment@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+94 11 XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="industry" class="form-label">Industry / Sector</label>
                        <select class="form-select" id="industry" name="industry">
                            <option value="Information Technology">Information Technology & Software</option>
                            <option value="Banking & Financial Services">Banking & Financial Services</option>
                            <option value="Telecommunications">Telecommunications</option>
                            <option value="E-Commerce & Digital">E-Commerce & Digital</option>
                            <option value="Consulting & Business Services">Consulting & Business Services</option>
                            <option value="Engineering & Manufacturing">Engineering & Manufacturing</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Headquarters / Location</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Colombo, Sri Lanka" value="<?= htmlspecialchars($_POST['location'] ?? 'Colombo, Sri Lanka') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="website" class="form-label">Company Website URL</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://www.company.com" value="<?= htmlspecialchars($_POST['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="company_logo" class="form-label">Company Logo (PNG, JPG, SVG)</label>
                        <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                    </div>

                    <div class="col-12">
                        <label for="about" class="form-label">About Company / Overview</label>
                        <textarea class="form-control" id="about" name="about" rows="3" placeholder="Brief overview of your company, culture, and mission..."><?= htmlspecialchars($_POST['about'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn-primary-ugpro py-3">Register Company Account</button>
                    </div>
                </div>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted small">Already have an employer account? <a href="<?= BASE_URL ?>signin_employer.php" class="text-success fw-bold">Sign In as Employer</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
