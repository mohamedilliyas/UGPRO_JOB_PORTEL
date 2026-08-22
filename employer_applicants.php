<?php
/**
 * Review & Manage Job Applicants - UgPro
 */
require_once __DIR__ . '/includes/auth.php';
require_employer_auth();

$employerId = $_SESSION['user_id'];
$filterJobId = intval($_GET['job_id'] ?? 0);

// Handle Status & Notes Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_status']) && is_db_connected()) {
    $appId = intval($_POST['application_id'] ?? 0);
    $newStatus = clean_input($_POST['status'] ?? 'pending');
    $notes = clean_input($_POST['employer_notes'] ?? '');

    // Verify application belongs to one of this employer's jobs
    try {
        $verifyStmt = @$connect->prepare("SELECT a.id FROM job_applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = ? AND j.employer_id = ?");
        if ($verifyStmt) {
            $verifyStmt->bind_param("ii", $appId, $employerId);
            $verifyStmt->execute();
            if ($verifyStmt->get_result()->num_rows === 1) {
                $upStmt = @$connect->prepare("UPDATE job_applications SET status = ?, employer_notes = ? WHERE id = ?");
                if ($upStmt) {
                    $upStmt->bind_param("ssi", $newStatus, $notes, $appId);
                    if ($upStmt->execute()) {
                        set_flash('success', 'Candidate application status and notes updated.');
                    }
                    $upStmt->close();
                }
            }
            $verifyStmt->close();
        }
    } catch (Throwable $e) {
        // Continue
    }
    header("Location: " . BASE_URL . "employer_applicants.php" . ($filterJobId > 0 ? "?job_id=" . $filterJobId : ""));
    exit();
}

$employerJobs = [];
$applicants = [];

if (is_db_connected()) {
    try {
        // Fetch employer's jobs list for filter dropdown
        $empJobsStmt = @$connect->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY title ASC");
        if ($empJobsStmt) {
            $empJobsStmt->bind_param("i", $employerId);
            $empJobsStmt->execute();
            $employerJobs = $empJobsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $empJobsStmt->close();
        }

        // Query applicants
        $query = "SELECT a.*, u.full_name, u.email AS student_email, u.phone AS student_phone, u.course, u.faculty, u.reg_no, u.graduation_year, u.skills, u.bio, u.github, u.linkedin, u.portfolio_url, u.profile_image, u.resume_file AS user_resume, j.title AS job_title, j.id AS job_id 
                  FROM job_applications a 
                  JOIN jobs j ON a.job_id = j.id 
                  JOIN undergraduate u ON a.undergraduate_id = u.id 
                  WHERE j.employer_id = ?";

        if ($filterJobId > 0) {
            $query .= " AND j.id = " . $filterJobId;
        }

        $query .= " ORDER BY a.applied_at DESC";

        $stmt = @$connect->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $employerId);
            $stmt->execute();
            $applicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } catch (Throwable $e) {
        $applicants = [];
    }
}

if (empty($employerJobs)) {
    $fallbackJobs = get_fallback_jobs();
    foreach ($fallbackJobs as $fj) {
        $employerJobs[] = ['id' => $fj['id'], 'title' => $fj['title']];
    }
}

$pageTitle = "Applicant Tracking - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-header-banner">
    <div class="obj-width">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h2 text-white fw-bold mb-1"><i class="bi bi-people me-2"></i>Applicant Tracking & Review</h1>
                <p class="text-white-50 mb-0">Evaluate student applications, inspect resumes, and update recruitment status</p>
            </div>
            <a href="<?= BASE_URL ?>profile_employer.php" class="btn btn-outline-light rounded-pill btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
    </div>
</div>

<div class="obj-width my-5">
    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fw-semibold small">Filter by Job:</span>
                <select class="form-select form-select-sm rounded-pill" style="min-width: 250px;" onchange="location.href='employer_applicants.php' + (this.value ? '?job_id=' + this.value : '')">
                    <option value="">All Job Postings (<?= count($applicants) ?> applicants)</option>
                    <?php foreach ($employerJobs as $ej): ?>
                        <option value="<?= $ej['id'] ?>" <?= $filterJobId == $ej['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ej['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?= count($applicants) ?> Candidates Found</span>
            </div>
        </div>
    </div>

    <!-- Applicants List -->
    <?php if (!empty($applicants)): ?>
        <div class="row g-4">
            <?php foreach ($applicants as $app): 
                $appResume = !empty($app['resume_path']) ? $app['resume_path'] : $app['user_resume'];
                $skillArr = array_filter(array_map('trim', explode(',', $app['skills'] ?? '')));
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <div class="row g-4">
                            <!-- Candidate Avatar & Info -->
                            <div class="col-lg-3 text-center text-lg-start border-end-lg">
                                <img src="<?= BASE_URL ?><?= !empty($app['profile_image']) ? htmlspecialchars($app['profile_image']) : 'images/fl-3.png' ?>" class="rounded-circle shadow-sm mb-3" width="90" height="90" style="object-fit: cover; border: 3px solid var(--secondary-light);" alt="Avatar">
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($app['full_name']) ?></h5>
                                <p class="text-muted small mb-2"><i class="bi bi-mortarboard me-1"></i> <?= htmlspecialchars($app['course']) ?> (<?= htmlspecialchars($app['graduation_year'] ?? '2025') ?>)</p>
                                <p class="small text-muted mb-2"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($app['student_email']) ?></p>
                                <?php if (!empty($app['student_phone'])): ?>
                                    <p class="small text-muted mb-3"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($app['student_phone']) ?></p>
                                <?php endif; ?>

                                <div class="d-flex justify-content-center justify-content-lg-start gap-2 mb-3">
                                    <?php if (!empty($app['github'])): ?>
                                        <a href="<?= htmlspecialchars($app['github']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-circle" title="GitHub"><i class="bi bi-github"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['linkedin'])): ?>
                                        <a href="<?= htmlspecialchars($app['linkedin']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-circle" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['portfolio_url'])): ?>
                                        <a href="<?= htmlspecialchars($app['portfolio_url']) ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-circle" title="Portfolio"><i class="bi bi-globe"></i></a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($appResume)): ?>
                                    <a href="<?= BASE_URL ?><?= htmlspecialchars($appResume) ?>" target="_blank" class="btn btn-success btn-sm rounded-pill w-100"><i class="bi bi-file-earmark-pdf me-1"></i> View Resume PDF</a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border w-100 py-2">No CV Attached</span>
                                <?php endif; ?>
                            </div>

                            <!-- Application Details & Cover Note -->
                            <div class="col-lg-5">
                                <div class="mb-3">
                                    <span class="small text-muted">Applied for position:</span>
                                    <h6 class="fw-bold text-success mb-1"><?= htmlspecialchars($app['job_title']) ?></h6>
                                    <span class="small text-muted">Submitted on <?= date('M d, Y - h:i A', strtotime($app['applied_at'])) ?></span>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Cover Letter / Note:</label>
                                    <div class="bg-light p-3 rounded-3 small text-muted">
                                        <?= !empty($app['cover_letter']) ? nl2br(htmlspecialchars($app['cover_letter'])) : 'No cover letter provided.' ?>
                                    </div>
                                </div>

                                <?php if (!empty($skillArr)): ?>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-muted">Candidate Skills:</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach (array_slice($skillArr, 0, 8) as $sk): ?>
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small"><?= htmlspecialchars($sk) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Recruitment Status Updater -->
                            <div class="col-lg-4 bg-light p-3 rounded-4">
                                <h6 class="fw-bold mb-3"><i class="bi bi-sliders text-success me-1"></i> Update Candidate Status</h6>
                                <form method="POST" action="employer_applicants.php<?= $filterJobId > 0 ? '?job_id=' . $filterJobId : '' ?>">
                                    <input type="hidden" name="update_application_status" value="1">
                                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Current Status:</label>
                                        <select class="form-select form-select-sm" name="status">
                                            <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending Review</option>
                                            <option value="reviewing" <?= $app['status'] === 'reviewing' ? 'selected' : '' ?>>🔍 Under Review</option>
                                            <option value="shortlisted" <?= $app['status'] === 'shortlisted' ? 'selected' : '' ?>>⭐ Shortlisted</option>
                                            <option value="interviewed" <?= $app['status'] === 'interviewed' ? 'selected' : '' ?>>📅 Interview Scheduled</option>
                                            <option value="accepted" <?= $app['status'] === 'accepted' ? 'selected' : '' ?>>✅ Job Offered / Accepted</option>
                                            <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>❌ Application Rejected</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Feedback / Interview Notes:</label>
                                        <textarea class="form-control form-control-sm" name="employer_notes" rows="2" placeholder="Visible to student (e.g. 'Interview scheduled for Friday 10 AM on Zoom')"><?= htmlspecialchars($app['employer_notes'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary-ugpro btn-sm py-2 w-100 rounded-pill">Save Status Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-people text-muted fs-1 mb-3"></i>
            <h5>No applicants found</h5>
            <p class="text-muted small">There are no applications submitted for the selected criteria.</p>
            <a href="<?= BASE_URL ?>profile_employer.php" class="btn btn-outline-secondary rounded-pill btn-sm px-4 mx-auto" style="width: auto;">Return to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
