<?php
/**
 * Undergraduate Profile & Career Dashboard - UgPro
 */
require_once __DIR__ . '/includes/auth.php';
require_student_auth();

$studentId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'overview';
$updateErrors = [];
$updateSuccess = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = clean_input($_POST['fullName'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $faculty = clean_input($_POST['faculty'] ?? '');
    $course = clean_input($_POST['course'] ?? '');
    $gradYear = intval($_POST['gradYear'] ?? 2025);
    $regNo = clean_input($_POST['regNo'] ?? '');
    $bio = clean_input($_POST['bio'] ?? '');
    $skills = clean_input($_POST['skills'] ?? '');
    $projects = clean_input($_POST['projects'] ?? '');
    $github = clean_input($_POST['github'] ?? '');
    $linkedin = clean_input($_POST['linkedin'] ?? '');
    $portfolio = clean_input($_POST['portfolio'] ?? '');

    // Fetch current image and resume to preserve if not updated
    $currStmt = $connect->prepare("SELECT profile_image, resume_file FROM undergraduate WHERE id = ?");
    $currStmt->bind_param("i", $studentId);
    $currStmt->execute();
    $currRow = $currStmt->get_result()->fetch_assoc();
    $currStmt->close();

    $profileImage = $currRow['profile_image'] ?? 'images/fl-3.png';
    $resumeFile = $currRow['resume_file'] ?? null;

    // Handle Profile Image Replacement
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imgUpload = handle_file_upload($_FILES['profile_image'], PROFILE_UPLOAD_DIR, ['jpg', 'jpeg', 'png', 'webp'], 5242880);
        if ($imgUpload['success']) {
            $profileImage = $imgUpload['filePath'];
            $_SESSION['user_avatar'] = $profileImage;
        } else {
            $updateErrors[] = "Photo Upload: " . $imgUpload['error'];
        }
    }

    // Handle Resume Replacement
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
        $resUpload = handle_file_upload($_FILES['resume_file'], RESUME_UPLOAD_DIR, ['pdf'], 10485760);
        if ($resUpload['success']) {
            $resumeFile = $resUpload['filePath'];
        } else {
            $updateErrors[] = "Resume Upload: " . $resUpload['error'];
        }
    }

    // Handle Password Update if filled
    $passwordSql = "";
    $newPassword = $_POST['new_password'] ?? '';
    if (!empty($newPassword)) {
        if (strlen($newPassword) >= 6) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $passwordSql = ", password = '" . mysqli_real_escape_string($connect, $hashed) . "'";
        } else {
            $updateErrors[] = "New password must be at least 6 characters.";
        }
    }

    if (empty($updateErrors)) {
        $upStmt = $connect->prepare("UPDATE undergraduate SET full_name = ?, phone = ?, faculty = ?, course = ?, graduation_year = ?, reg_no = ?, bio = ?, skills = ?, projects = ?, github = ?, linkedin = ?, portfolio_url = ?, profile_image = ?, resume_file = ? {$passwordSql} WHERE id = ?");
        $upStmt->bind_param("ssssisssssssssi", $fullName, $phone, $faculty, $course, $gradYear, $regNo, $bio, $skills, $projects, $github, $linkedin, $portfolio, $profileImage, $resumeFile, $studentId);

        if ($upStmt->execute()) {
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_course'] = $course;
            $_SESSION['fullname'] = $fullName;
            $updateSuccess = "Your profile has been updated successfully!";
            $activeTab = 'overview';
        } else {
            $updateErrors[] = "Database update error: " . $connect->error;
        }
        $upStmt->close();
    }
}

// Fetch fresh student profile data
$stmt = $connect->prepare("SELECT * FROM undergraduate WHERE id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: signin_undergraduate.php");
    exit();
}

// Fetch Student Applications
$appQuery = "SELECT a.*, j.title AS job_title, j.job_type, j.location AS job_location, j.salary_range, e.company_name, e.company_logo 
             FROM job_applications a 
             JOIN jobs j ON a.job_id = j.id 
             JOIN employer e ON j.employer_id = e.id 
             WHERE a.undergraduate_id = ? 
             ORDER BY a.applied_at DESC";
$appStmt = $connect->prepare($appQuery);
$appStmt->bind_param("i", $studentId);
$appStmt->execute();
$applications = $appStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appStmt->close();

// Fetch Saved Jobs
$savedQuery = "SELECT s.id AS saved_id, s.created_at AS saved_at, j.*, e.company_name, e.company_logo 
               FROM saved_jobs s 
               JOIN jobs j ON s.job_id = j.id 
               JOIN employer e ON j.employer_id = e.id 
               WHERE s.undergraduate_id = ? 
               ORDER BY s.created_at DESC";
$savedStmt = $connect->prepare($savedQuery);
$savedStmt->bind_param("i", $studentId);
$savedStmt->execute();
$savedJobs = $savedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$savedStmt->close();

// Skill tags array helper
$skillsList = array_filter(array_map('trim', explode(',', $student['skills'] ?? '')));

$pageTitle = "My Student Profile - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Profile Hero Banner -->
<div class="dashboard-header-banner">
    <div class="obj-width">
        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
            <img src="<?= BASE_URL ?><?= !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'images/fl-3.png' ?>" alt="Profile Picture" class="profile-avatar-lg">
            <div class="text-center text-md-start flex-grow-1">
                <h1 class="h2 text-white fw-bold mb-1"><?= htmlspecialchars($student['full_name']) ?></h1>
                <p class="text-white-50 mb-2">
                    <i class="bi bi-mortarboard me-1"></i> <?= htmlspecialchars($student['course'] ?? 'Undergraduate') ?> &bull; Class of <?= htmlspecialchars($student['graduation_year'] ?? '2025') ?>
                </p>
                <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 small text-white-50">
                    <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($student['email']) ?></span>
                    <?php if (!empty($student['phone'])): ?>
                        <span><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($student['phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($student['reg_no'])): ?>
                        <span><i class="bi bi-card-text me-1"></i> <?= htmlspecialchars($student['reg_no']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="?tab=edit" class="btn btn-outline-light rounded-pill px-3"><i class="bi bi-pencil me-1"></i> Edit Profile</a>
                <?php if (!empty($student['resume_file'])): ?>
                    <a href="<?= BASE_URL ?><?= htmlspecialchars($student['resume_file']) ?>" target="_blank" class="btn btn-success rounded-pill px-3"><i class="bi bi-file-earmark-pdf me-1"></i> View CV</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Tabs -->
<div class="obj-width my-5">
    <?php if (!empty($updateSuccess)): ?>
        <div class="alert alert-success shadow-sm rounded-3 d-flex align-items-center mb-4">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($updateSuccess) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($updateErrors)): ?>
        <div class="alert alert-danger shadow-sm rounded-3 mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($updateErrors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs nav-tabs-modern">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" href="?tab=overview"><i class="bi bi-person me-2"></i>Profile Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'applications' ? 'active' : '' ?>" href="?tab=applications">
                <i class="bi bi-send me-2"></i>My Applications <span class="badge bg-secondary ms-1"><?= count($applications) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'saved' ? 'active' : '' ?>" href="?tab=saved">
                <i class="bi bi-bookmark me-2"></i>Saved Jobs <span class="badge bg-secondary ms-1"><?= count($savedJobs) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'edit' ? 'active' : '' ?>" href="?tab=edit"><i class="bi bi-gear me-2"></i>Edit Profile</a>
        </li>
    </ul>

    <!-- Tab 1: Profile Overview -->
    <?php if ($activeTab === 'overview'): ?>
        <div class="row g-4">
            <!-- Left Info Column -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-success me-2"></i>Academic Details</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="text-muted small d-block">Faculty</span>
                            <strong><?= htmlspecialchars($student['faculty'] ?? 'Faculty of Applied Science') ?></strong>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Degree / Course</span>
                            <strong><?= htmlspecialchars($student['course'] ?? 'Not specified') ?></strong>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small d-block">Index / Reg Number</span>
                            <strong><?= htmlspecialchars($student['reg_no'] ?? 'N/A') ?></strong>
                        </li>
                        <li class="mb-0">
                            <span class="text-muted small d-block">Graduation Year</span>
                            <strong><?= htmlspecialchars($student['graduation_year'] ?? '2025') ?></strong>
                        </li>
                    </ul>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-link-45deg text-success me-2"></i>Online Links</h5>
                    <div class="d-flex flex-column gap-2">
                        <?php if (!empty($student['github'])): ?>
                            <a href="<?= htmlspecialchars($student['github']) ?>" target="_blank" class="btn btn-outline-dark rounded-pill text-start"><i class="bi bi-github me-2"></i> GitHub Profile</a>
                        <?php endif; ?>
                        <?php if (!empty($student['linkedin'])): ?>
                            <a href="<?= htmlspecialchars($student['linkedin']) ?>" target="_blank" class="btn btn-outline-primary rounded-pill text-start"><i class="bi bi-linkedin me-2"></i> LinkedIn Profile</a>
                        <?php endif; ?>
                        <?php if (!empty($student['portfolio_url'])): ?>
                            <a href="<?= htmlspecialchars($student['portfolio_url']) ?>" target="_blank" class="btn btn-outline-success rounded-pill text-start"><i class="bi bi-globe me-2"></i> Personal Portfolio</a>
                        <?php endif; ?>
                        <?php if (empty($student['github']) && empty($student['linkedin']) && empty($student['portfolio_url'])): ?>
                            <p class="text-muted small mb-0">No links added yet. Click <a href="?tab=edit">Edit Profile</a> to add your GitHub and LinkedIn.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Details Column -->
            <div class="col-lg-8">
                <!-- Bio -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-card-text text-success me-2"></i>About Me / Career Objective</h5>
                    <p class="text-muted mb-0"><?= !empty($student['bio']) ? nl2br(htmlspecialchars($student['bio'])) : 'No biography added yet. Update your profile to write a summary about your career goals and interests.' ?></p>
                </div>

                <!-- Skills -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-stars text-success me-2"></i>Skills & Competencies</h5>
                    <?php if (!empty($skillsList)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($skillsList as $sk): ?>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-weight-normal fs-6"><?= htmlspecialchars($sk) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No skills listed yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Projects -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-kanban text-success me-2"></i>Projects & Experience</h5>
                    <p class="text-muted mb-0"><?= !empty($student['projects']) ? nl2br(htmlspecialchars($student['projects'])) : 'No project details added yet. Add details about your university and personal projects in the Edit Profile tab.' ?></p>
                </div>
            </div>
        </div>

    <!-- Tab 2: My Applications -->
    <?php elseif ($activeTab === 'applications'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">Job Application History</h4>
            <?php if (!empty($applications)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Company & Job Title</th>
                                <th>Job Type</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Employer Notes</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= BASE_URL ?><?= !empty($app['company_logo']) ? htmlspecialchars($app['company_logo']) : 'images/google.png' ?>" class="rounded-3 border p-1" width="40" height="40" alt="Logo">
                                            <div>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($app['job_title']) ?></strong>
                                                <span class="text-muted small"><?= htmlspecialchars($app['company_name']) ?> &bull; <?= htmlspecialchars($app['job_location']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-type"><?= htmlspecialchars($app['job_type']) ?></span></td>
                                    <td class="text-muted small"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= htmlspecialchars($app['status']) ?>">
                                            <i class="bi bi-circle-fill" style="font-size: 6px;"></i> <?= ucfirst(htmlspecialchars($app['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= !empty($app['employer_notes']) ? htmlspecialchars($app['employer_notes']) : '—' ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>job_details.php?id=<?= $app['job_id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">View Job</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-briefcase text-muted fs-1 mb-3 d-block"></i>
                    <h5>No applications yet</h5>
                    <p class="text-muted small">You have not applied for any jobs yet. Start exploring opportunities now.</p>
                    <a href="<?= BASE_URL ?>jobs.php" class="btn btn-primary-ugpro rounded-pill px-4 mt-2" style="width: auto;">Browse Job Vacancies</a>
                </div>
            <?php endif; ?>
        </div>

    <!-- Tab 3: Saved Jobs -->
    <?php elseif ($activeTab === 'saved'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">Saved Job Bookmarks</h4>
            <?php if (!empty($savedJobs)): ?>
                <div class="row g-4">
                    <?php foreach ($savedJobs as $job): ?>
                        <div class="col-md-6">
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div class="company-logo-wrap">
                                        <img src="<?= BASE_URL ?><?= !empty($job['company_logo']) ? htmlspecialchars($job['company_logo']) : 'images/google.png' ?>" alt="Company Logo">
                                    </div>
                                    <div class="job-title-wrap">
                                        <h3 class="job-card-title"><a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a></h3>
                                        <span class="company-name-text"><?= htmlspecialchars($job['company_name']) ?></span>
                                    </div>
                                </div>
                                <div class="job-card-badges">
                                    <span class="badge-type"><?= htmlspecialchars($job['job_type']) ?></span>
                                    <span class="badge-workplace"><?= htmlspecialchars($job['workplace_type']) ?></span>
                                    <span class="badge-location"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                </div>
                                <div class="job-card-salary"><?= htmlspecialchars($job['salary_range']) ?></div>
                                <div class="job-card-footer">
                                    <span class="job-card-time">Saved on <?= date('M d, Y', strtotime($job['saved_at'])) ?></span>
                                    <a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>" class="btn-view-job">View & Apply</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bookmark text-muted fs-1 mb-3 d-block"></i>
                    <h5>No saved jobs</h5>
                    <p class="text-muted small">Bookmark interesting jobs while browsing to review and apply later.</p>
                    <a href="<?= BASE_URL ?>jobs.php" class="btn btn-primary-ugpro rounded-pill px-4 mt-2" style="width: auto;">Browse Job Vacancies</a>
                </div>
            <?php endif; ?>
        </div>

    <!-- Tab 4: Edit Profile Form -->
    <?php elseif ($activeTab === 'edit'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h4 class="fw-bold mb-4"><i class="bi bi-pencil-square text-success me-2"></i>Update Career Profile</h4>
            <form method="POST" enctype="multipart/form-data" action="profile_undergraduate.php?tab=edit">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="fullName" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" value="<?= htmlspecialchars($student['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email (Read Only)</label>
                        <input type="email" class="form-control bg-light" id="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="regNo" class="form-label">Registration / Index Number</label>
                        <input type="text" class="form-control" id="regNo" name="regNo" value="<?= htmlspecialchars($student['reg_no'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="faculty" class="form-label">Faculty</label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="Faculty of Applied Science" <?= ($student['faculty'] ?? '') === 'Faculty of Applied Science' ? 'selected' : '' ?>>Faculty of Applied Science</option>
                            <option value="Faculty of Business Studies" <?= ($student['faculty'] ?? '') === 'Faculty of Business Studies' ? 'selected' : '' ?>>Faculty of Business Studies</option>
                            <option value="Faculty of Technological Studies" <?= ($student['faculty'] ?? '') === 'Faculty of Technological Studies' ? 'selected' : '' ?>>Faculty of Technological Studies</option>
                            <option value="Other Institution" <?= ($student['faculty'] ?? '') === 'Other Institution' ? 'selected' : '' ?>>Other Institution</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="course" class="form-label">Degree / Course *</label>
                        <input type="text" class="form-control" id="course" name="course" value="<?= htmlspecialchars($student['course'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label for="gradYear" class="form-label">Graduation Year</label>
                        <input type="number" class="form-control" id="gradYear" name="gradYear" value="<?= htmlspecialchars($student['graduation_year'] ?? '2025') ?>">
                    </div>

                    <div class="col-12">
                        <label for="bio" class="form-label">About Me / Career Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="skills" class="form-label">Skills (Comma separated)</label>
                        <input type="text" class="form-control" id="skills" name="skills" value="<?= htmlspecialchars($student['skills'] ?? '') ?>" placeholder="PHP, MySQL, React, Python, UI/UX">
                    </div>

                    <div class="col-12">
                        <label for="projects" class="form-label">Projects & Experience</label>
                        <textarea class="form-control" id="projects" name="projects" rows="3"><?= htmlspecialchars($student['projects'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="github" class="form-label">GitHub Profile URL</label>
                        <input type="url" class="form-control" id="github" name="github" value="<?= htmlspecialchars($student['github'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="linkedin" class="form-label">LinkedIn Profile URL</label>
                        <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?= htmlspecialchars($student['linkedin'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="portfolio" class="form-label">Portfolio URL</label>
                        <input type="url" class="form-control" id="portfolio" name="portfolio" value="<?= htmlspecialchars($student['portfolio_url'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="profile_image" class="form-label">Change Profile Photo</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        <span class="small text-muted">Leave empty to keep existing photo.</span>
                    </div>
                    <div class="col-md-6">
                        <label for="resume_file" class="form-label">Upload New Resume / CV (PDF)</label>
                        <input type="file" class="form-control" id="resume_file" name="resume_file" accept="application/pdf">
                        <span class="small text-muted">Leave empty to keep existing resume.</span>
                    </div>

                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password (Optional)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-ugpro py-3" style="max-width: 300px;">Save Profile Changes</button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
