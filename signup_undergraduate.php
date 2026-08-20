<?php
/**
 * Undergraduate Sign Up - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_student()) {
    header("Location: profile_undergraduate.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = clean_input($_POST['fullName'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $regNo = clean_input($_POST['regNo'] ?? '');
    $faculty = clean_input($_POST['faculty'] ?? 'Faculty of Applied Science');
    $course = clean_input($_POST['course'] ?? '');
    $gradYear = intval($_POST['gradYear'] ?? 2025);
    $phone = clean_input($_POST['phone'] ?? '');
    $skills = clean_input($_POST['skills'] ?? '');
    $projects = clean_input($_POST['projects'] ?? '');
    $bio = clean_input($_POST['bio'] ?? '');
    $github = clean_input($_POST['github'] ?? '');
    $linkedin = clean_input($_POST['linkedin'] ?? '');
    $portfolio = clean_input($_POST['portfolio'] ?? '');

    // Validation
    if (empty($fullName)) $errors[] = "Full name is required.";
    if (!$email) $errors[] = "A valid university or personal email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
    if (empty($course)) $errors[] = "Degree / Course name is required.";

    // Handle Profile Image Upload
    $profileImagePath = 'images/fl-3.png'; // Default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imgUpload = handle_file_upload($_FILES['profile_image'], PROFILE_UPLOAD_DIR, ['jpg', 'jpeg', 'png', 'webp'], 5242880);
        if ($imgUpload['success']) {
            $profileImagePath = $imgUpload['filePath'];
        } else {
            $errors[] = "Profile Image: " . $imgUpload['error'];
        }
    }

    // Handle Resume PDF Upload
    $resumePath = null;
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
        $resumeUpload = handle_file_upload($_FILES['resume_file'], RESUME_UPLOAD_DIR, ['pdf'], 10485760);
        if ($resumeUpload['success']) {
            $resumePath = $resumeUpload['filePath'];
        } else {
            $errors[] = "Resume: " . $resumeUpload['error'];
        }
    }

    // If no validation errors, proceed to database
    if (empty($errors)) {
        if ($connect) {
            // Check if email already registered
            $checkStmt = $connect->prepare("SELECT id FROM undergraduate WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $errors[] = "This email is already registered. Please sign in instead.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $connect->prepare("INSERT INTO undergraduate (full_name, email, password, reg_no, faculty, course, graduation_year, phone, skills, projects, bio, github, linkedin, portfolio_url, profile_image, resume_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssisssssssss", $fullName, $email, $hashedPassword, $regNo, $faculty, $course, $gradYear, $phone, $skills, $projects, $bio, $github, $linkedin, $portfolio, $profileImagePath, $resumePath);

                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    // Auto login
                    $_SESSION['user_id'] = $newId;
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'student';
                    $_SESSION['user_avatar'] = $profileImagePath;
                    $_SESSION['user_course'] = $course;

                    set_flash('success', 'Registration successful! Welcome to your UgPro Career Hub.');
                    header("Location: profile_undergraduate.php");
                    exit();
                } else {
                    $errors[] = "Database error: " . $connect->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        } else {
            $errors[] = "Unable to connect to database. Please check configuration.";
        }
    }
}

$pageTitle = "Undergraduate Sign Up - UgPro";
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
                <p>Empowering Vavuniya University undergraduates to launch successful careers with top industry partners.</p>
            </div>
            
            <div class="auth-sidebar-icon">
                <i class="bi bi-mortarboard"></i>
            </div>
            
            <div>
                <p class="small mb-0">Already registered?</p>
                <a href="<?= BASE_URL ?>signin_undergraduate.php" class="btn btn-outline-light btn-sm rounded-pill px-4 mt-2">Sign In</a>
            </div>
        </div>

        <div class="col-lg-8 auth-card-body">
            <h2 class="auth-form-title">Student Registration</h2>
            <p class="text-muted small mb-4">Create your student career profile and connect with verified employers</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm rounded-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="signup_undergraduate.php">
                <div class="row g-3">
                    <!-- Personal Info -->
                    <div class="col-md-6">
                        <label for="fullName" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" placeholder="e.g. Mohamed Illiyas" value="<?= htmlspecialchars($_POST['fullName'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@vau.ac.lk or personal email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Create Password *</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+94 7X XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <!-- Academic Info -->
                    <div class="col-md-6">
                        <label for="regNo" class="form-label">Registration / Index No</label>
                        <input type="text" class="form-control" id="regNo" name="regNo" placeholder="e.g. 2020/ICT/42" value="<?= htmlspecialchars($_POST['regNo'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="faculty" class="form-label">Faculty</label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="Faculty of Applied Science">Faculty of Applied Science</option>
                            <option value="Faculty of Business Studies">Faculty of Business Studies</option>
                            <option value="Faculty of Technological Studies">Faculty of Technological Studies</option>
                            <option value="Other Faculty / Institution">Other Faculty / Institution</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label for="course" class="form-label">Degree / Course *</label>
                        <input type="text" class="form-control" id="course" name="course" placeholder="e.g. Information and Communication Technology (BICT)" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="gradYear" class="form-label">Graduation Year</label>
                        <input type="number" class="form-control" id="gradYear" name="gradYear" min="2020" max="2030" value="<?= htmlspecialchars($_POST['gradYear'] ?? '2025') ?>">
                    </div>

                    <!-- Skills & Projects -->
                    <div class="col-12">
                        <label for="skills" class="form-label">Key Skills (Comma separated)</label>
                        <input type="text" class="form-control" id="skills" name="skills" placeholder="e.g. PHP, JavaScript, React, MySQL, Python, UI/UX" value="<?= htmlspecialchars($_POST['skills'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="projects" class="form-label">Featured Projects / Experience</label>
                        <textarea class="form-control" id="projects" name="projects" rows="2" placeholder="Describe 1-2 major projects or extracurricular roles..."><?= htmlspecialchars($_POST['projects'] ?? '') ?></textarea>
                    </div>

                    <!-- Social / Links -->
                    <div class="col-md-4">
                        <label for="github" class="form-label">GitHub URL</label>
                        <input type="url" class="form-control" id="github" name="github" placeholder="https://github.com/username" value="<?= htmlspecialchars($_POST['github'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="linkedin" class="form-label">LinkedIn URL</label>
                        <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/username" value="<?= htmlspecialchars($_POST['linkedin'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="portfolio" class="form-label">Portfolio / Web</label>
                        <input type="url" class="form-control" id="portfolio" name="portfolio" placeholder="https://yourportfolio.com" value="<?= htmlspecialchars($_POST['portfolio'] ?? '') ?>">
                    </div>

                    <!-- File Uploads -->
                    <div class="col-md-6">
                        <label for="profile_image" class="form-label">Profile Photo (JPG, PNG, max 5MB)</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/png, image/jpeg, image/webp">
                    </div>
                    <div class="col-md-6">
                        <label for="resume_file" class="form-label">Resume / CV (PDF, max 10MB)</label>
                        <input type="file" class="form-control" id="resume_file" name="resume_file" accept="application/pdf">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn-primary-ugpro py-3">Complete Registration</button>
                    </div>
                </div>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted small">Already have an account? <a href="<?= BASE_URL ?>signin_undergraduate.php" class="text-success fw-bold">Sign In as Undergraduate</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
