<?php
/**
 * Employer Dashboard & Job Management - UgPro
 */
require_once __DIR__ . '/includes/auth.php';
require_employer_auth();

$employerId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'jobs';
$updateErrors = [];
$updateSuccess = '';

// Handle Job Status Toggle (Active / Closed)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['job_id']) && is_db_connected()) {
    $jobId = intval($_GET['job_id']);
    try {
        $stmt = @$connect->prepare("UPDATE jobs SET status = IF(status = 'active', 'closed', 'active') WHERE id = ? AND employer_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $jobId, $employerId);
            if ($stmt->execute()) {
                set_flash('success', 'Job listing status updated successfully.');
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // Continue
    }
    header("Location: " . BASE_URL . "profile_employer.php?tab=jobs");
    exit();
}

// Handle Job Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_job' && isset($_GET['job_id']) && is_db_connected()) {
    $jobId = intval($_GET['job_id']);
    try {
        $stmt = @$connect->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $jobId, $employerId);
            if ($stmt->execute()) {
                set_flash('success', 'Job posting has been deleted.');
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // Continue
    }
    header("Location: " . BASE_URL . "profile_employer.php?tab=jobs");
    exit();
}

// Handle Company Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company_profile'])) {
    $companyName = clean_input($_POST['companyName'] ?? '');
    $website = clean_input($_POST['website'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $industry = clean_input($_POST['industry'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $about = clean_input($_POST['about'] ?? '');

    $currLogo = 'images/google.png';
    $currEmail = $_SESSION['user_email'] ?? 'careers@virtusa.com';

    if (is_db_connected()) {
        try {
            $currStmt = @$connect->prepare("SELECT company_logo, email FROM employer WHERE id = ?");
            if ($currStmt) {
                $currStmt->bind_param("i", $employerId);
                $currStmt->execute();
                $currRow = $currStmt->get_result()->fetch_assoc();
                $currLogo = $currRow['company_logo'] ?? 'images/google.png';
                $currEmail = $currRow['email'] ?? $_SESSION['user_email'];
                $currStmt->close();
            }
        } catch (Throwable $e) {
            // Keep default
        }
    }

    $logoPath = $currLogo;
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $logoUpload = handle_file_upload($_FILES['company_logo'], LOGO_UPLOAD_DIR, ['jpg', 'jpeg', 'png', 'webp', 'svg'], 5242880);
        if ($logoUpload['success']) {
            $logoPath = $logoUpload['filePath'];
            $_SESSION['user_avatar'] = $logoPath;
        } else {
            $updateErrors[] = "Logo Upload: " . $logoUpload['error'];
        }
    }

    $passwordSql = "";
    $newPassword = $_POST['new_password'] ?? '';
    if (!empty($newPassword)) {
        if (strlen($newPassword) >= 6) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            if (is_db_connected()) {
                $passwordSql = ", password = '" . mysqli_real_escape_string($connect, $hashed) . "'";
            }
        } else {
            $updateErrors[] = "New password must be at least 6 characters.";
        }
    }

    if (empty($updateErrors)) {
        if (is_db_connected()) {
            try {
                $upStmt = @$connect->prepare("UPDATE employer SET company_name = ?, website = ?, location = ?, industry = ?, phone = ?, about = ?, company_logo = ? {$passwordSql} WHERE id = ?");
                if ($upStmt) {
                    $upStmt->bind_param("sssssssi", $companyName, $website, $location, $industry, $phone, $about, $logoPath, $employerId);
                    if ($upStmt->execute()) {
                        set_user_session($employerId, 'employer', $companyName, $currEmail, $logoPath);
                        $_SESSION['employer_id'] = $employerId;
                        $_SESSION['company_name'] = $companyName;
                        $updateSuccess = "Company profile updated successfully!";
                        $activeTab = 'settings';
                    } else {
                        $updateErrors[] = "Database update error: " . $connect->error;
                    }
                    $upStmt->close();
                }
            } catch (Throwable $e) {
                $updateErrors[] = "Update failed: " . $e->getMessage();
            }
        } else {
            set_user_session($employerId, 'employer', $companyName, $currEmail, $logoPath);
            $_SESSION['employer_id'] = $employerId;
            $_SESSION['company_name'] = $companyName;
            $updateSuccess = "Company profile updated (simulated demo mode).";
            $activeTab = 'settings';
        }
    }
}

$employer = null;
$postedJobs = [];

if (is_db_connected()) {
    try {
        $empStmt = @$connect->prepare("SELECT * FROM employer WHERE id = ?");
        if ($empStmt) {
            $empStmt->bind_param("i", $employerId);
            $empStmt->execute();
            $employer = $empStmt->get_result()->fetch_assoc();
            $empStmt->close();
        }

        // Fetch employer's jobs with applicant counts
        $jobsQuery = "SELECT j.*, c.name AS category_name, 
                      (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) AS total_applicants,
                      (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id AND status = 'shortlisted') AS shortlisted_applicants
                      FROM jobs j 
                      LEFT JOIN job_categories c ON j.category_id = c.id 
                      WHERE j.employer_id = ? 
                      ORDER BY j.created_at DESC";
        $jobsStmt = @$connect->prepare($jobsQuery);
        if ($jobsStmt) {
            $jobsStmt->bind_param("i", $employerId);
            $jobsStmt->execute();
            $postedJobs = $jobsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $jobsStmt->close();
        }
    } catch (Throwable $e) {
        $employer = null;
    }
}

if (!$employer) {
    $fallbackJobs = get_fallback_jobs();
    $employer = [
        'id' => $employerId,
        'company_name' => $_SESSION['user_name'] ?? 'Virtusa (Pvt) Ltd',
        'email' => $_SESSION['user_email'] ?? 'careers@virtusa.com',
        'company_logo' => $_SESSION['user_avatar'] ?? 'images/google.png',
        'website' => 'https://www.virtusa.com',
        'location' => 'Colombo 07, Sri Lanka',
        'industry' => 'Information Technology & Services',
        'phone' => '+94 11 234 5678',
        'about' => 'Virtusa Corporation is a global provider of digital business strategy, digital engineering, and IT services.'
    ];
    $postedJobs = array_slice($fallbackJobs, 0, 2);
}

// Stats calculation
$totalJobsCount = count($postedJobs);
$activeJobsCount = 0;
$totalApplicantsCount = 0;
$shortlistedCount = 0;

foreach ($postedJobs as $pj) {
    if ($pj['status'] === 'active') $activeJobsCount++;
    $totalApplicantsCount += intval($pj['total_applicants']);
    $shortlistedCount += intval($pj['shortlisted_applicants']);
}

$pageTitle = "Employer Dashboard - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Banner -->
<div class="dashboard-header-banner">
    <div class="obj-width">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white p-2 rounded-4 shadow-sm" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    <img src="<?= BASE_URL ?><?= !empty($employer['company_logo']) ? htmlspecialchars($employer['company_logo']) : 'images/google.png' ?>" alt="Company Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div>
                    <h1 class="h2 text-white fw-bold mb-1"><?= htmlspecialchars($employer['company_name']) ?></h1>
                    <p class="text-white-50 mb-0 small">
                        <i class="bi bi-briefcase me-1"></i> <?= htmlspecialchars($employer['industry'] ?? 'Information Technology') ?> &bull; 
                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($employer['location'] ?? 'Colombo, Sri Lanka') ?>
                    </p>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>employer_post_job.php" class="btn btn-success rounded-pill px-4 shadow-sm"><i class="bi bi-plus-circle me-1"></i> Post a New Job</a>
                <a href="<?= BASE_URL ?>browse_candidates.php" class="btn btn-outline-light rounded-pill px-3"><i class="bi bi-search me-1"></i> Talent Pool</a>
            </div>
        </div>
    </div>
</div>

<!-- Metrics Counters -->
<div class="obj-width my-5">
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon green"><i class="bi bi-briefcase-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $activeJobsCount ?></h4>
                    <p>Active Job Posts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $totalApplicantsCount ?></h4>
                    <p>Total Applicants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon purple"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $shortlistedCount ?></h4>
                    <p>Shortlisted Candidates</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon amber"><i class="bi bi-collection-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $totalJobsCount ?></h4>
                    <p>Total Campaigns</p>
                </div>
            </div>
        </div>
    </div>

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
            <a class="nav-link <?= $activeTab === 'jobs' ? 'active' : '' ?>" href="?tab=jobs"><i class="bi bi-list-task me-2"></i>My Job Postings</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'settings' ? 'active' : '' ?>" href="?tab=settings"><i class="bi bi-gear me-2"></i>Company Settings</a>
        </li>
    </ul>

    <!-- Tab 1: Posted Jobs Table -->
    <?php if ($activeTab === 'jobs'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Active & Past Job Listings</h4>
                <a href="<?= BASE_URL ?>employer_post_job.php" class="btn btn-sm btn-primary-ugpro rounded-pill px-3" style="width: auto;"><i class="bi bi-plus-lg me-1"></i> Add Vacancy</a>
            </div>

            <?php if (!empty($postedJobs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Job Title & Category</th>
                                <th>Type & Workplace</th>
                                <th>Location & Salary</th>
                                <th>Status</th>
                                <th>Applicants</th>
                                <th>Deadline</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($postedJobs as $job): ?>
                                <tr>
                                    <td>
                                        <strong class="d-block text-dark"><a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a></strong>
                                        <span class="text-muted small"><?= htmlspecialchars($job['category_name'] ?? 'General') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-type"><?= htmlspecialchars($job['job_type']) ?></span>
                                        <span class="badge-workplace ms-1"><?= htmlspecialchars($job['workplace_type']) ?></span>
                                    </td>
                                    <td>
                                        <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></div>
                                        <div class="small fw-semibold text-success"><?= htmlspecialchars($job['salary_range']) ?></div>
                                    </td>
                                    <td>
                                        <a href="?action=toggle_status&job_id=<?= $job['id'] ?>" title="Click to toggle status" class="status-badge <?= $job['status'] === 'active' ? 'active' : 'closed' ?> text-decoration-none">
                                            <i class="bi bi-circle-fill" style="font-size: 6px;"></i> <?= ucfirst($job['status']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>employer_applicants.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-people me-1"></i> View (<?= $job['total_applicants'] ?>)
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?= !empty($job['deadline']) ? date('M d, Y', strtotime($job['deadline'])) : 'Open' ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>employer_edit_job.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Job"><i class="bi bi-pencil"></i></a>
                                            <a href="?action=delete_job&job_id=<?= $job['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this job post?')" class="btn btn-sm btn-outline-danger" title="Delete Job"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-briefcase text-muted fs-1 mb-3 d-block"></i>
                    <h5>No jobs posted yet</h5>
                    <p class="text-muted small">Post your first vacancy to start receiving applications from university undergraduates.</p>
                    <a href="<?= BASE_URL ?>employer_post_job.php" class="btn btn-primary-ugpro rounded-pill px-4 mt-2" style="width: auto;">Post a New Job Vacancy</a>
                </div>
            <?php endif; ?>
        </div>

    <!-- Tab 2: Company Profile Settings -->
    <?php elseif ($activeTab === 'settings'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h4 class="fw-bold mb-4"><i class="bi bi-building-gear text-success me-2"></i>Update Company Profile</h4>
            <form method="POST" enctype="multipart/form-data" action="profile_employer.php?tab=settings">
                <input type="hidden" name="update_company_profile" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="companyName" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="companyName" name="companyName" value="<?= htmlspecialchars($employer['company_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email (Read Only)</label>
                        <input type="email" class="form-control bg-light" id="email" value="<?= htmlspecialchars($employer['email']) ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="website" class="form-label">Website URL</label>
                        <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($employer['website'] ?? '') ?>" placeholder="https://www.company.com">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($employer['phone'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="industry" class="form-label">Industry</label>
                        <select class="form-select" id="industry" name="industry">
                            <option value="Information Technology" <?= ($employer['industry'] ?? '') === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                            <option value="Banking & Financial Services" <?= ($employer['industry'] ?? '') === 'Banking & Financial Services' ? 'selected' : '' ?>>Banking & Financial Services</option>
                            <option value="Telecommunications" <?= ($employer['industry'] ?? '') === 'Telecommunications' ? 'selected' : '' ?>>Telecommunications</option>
                            <option value="E-Commerce & Digital" <?= ($employer['industry'] ?? '') === 'E-Commerce & Digital' ? 'selected' : '' ?>>E-Commerce & Digital</option>
                            <option value="Consulting & Business Services" <?= ($employer['industry'] ?? '') === 'Consulting & Business Services' ? 'selected' : '' ?>>Consulting & Business Services</option>
                            <option value="Other" <?= ($employer['industry'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Location / Headquarters</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($employer['location'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="about" class="form-label">About Company</label>
                        <textarea class="form-control" id="about" name="about" rows="3"><?= htmlspecialchars($employer['about'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="company_logo" class="form-label">Change Company Logo</label>
                        <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                        <span class="small text-muted">Leave empty to keep existing logo.</span>
                    </div>

                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password (Optional)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-ugpro py-3" style="max-width: 300px;">Save Company Profile</button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
