<?php
/**
 * Single Job Detail Page - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

$jobId = intval($_GET['id'] ?? 0);

if ($jobId <= 0) {
    header("Location: jobs.php");
    exit();
}

// Increment views count
if ($connect) {
    @$connect->query("UPDATE jobs SET views_count = views_count + 1 WHERE id = {$jobId}");
}

// Fetch Job with Employer Details
$stmt = $connect->prepare("SELECT j.*, e.company_name, e.company_logo, e.website AS company_website, e.location AS company_location, e.industry, e.about AS company_about, c.name AS category_name 
                           FROM jobs j 
                           JOIN employer e ON j.employer_id = e.id 
                           LEFT JOIN job_categories c ON j.category_id = c.id 
                           WHERE j.id = ?");
$stmt->bind_param("i", $jobId);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) {
    set_flash('danger', 'The requested job posting was not found.');
    header("Location: jobs.php");
    exit();
}

// Check if currently logged in student already applied
$hasApplied = false;
$applicationData = null;
$isSaved = false;

if (is_student()) {
    $studentId = $_SESSION['user_id'];
    
    // Check application
    $appCheck = $connect->prepare("SELECT * FROM job_applications WHERE job_id = ? AND undergraduate_id = ?");
    $appCheck->bind_param("ii", $jobId, $studentId);
    $appCheck->execute();
    $appRes = $appCheck->get_result();
    if ($appRes->num_rows > 0) {
        $hasApplied = true;
        $applicationData = $appRes->fetch_assoc();
    }
    $appCheck->close();

    // Check bookmark
    $saveCheck = $connect->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND undergraduate_id = ?");
    $saveCheck->bind_param("ii", $jobId, $studentId);
    $saveCheck->execute();
    if ($saveCheck->get_result()->num_rows > 0) {
        $isSaved = true;
    }
    $saveCheck->close();

    // Fetch student's profile resume
    $stuResumeStmt = $connect->prepare("SELECT resume_file FROM undergraduate WHERE id = ?");
    $stuResumeStmt->bind_param("i", $studentId);
    $stuResumeStmt->execute();
    $studentResume = $stuResumeStmt->get_result()->fetch_assoc()['resume_file'] ?? null;
    $stuResumeStmt->close();
}

// Fetch Similar Jobs
$simQuery = "SELECT j.*, e.company_name, e.company_logo 
             FROM jobs j 
             JOIN employer e ON j.employer_id = e.id 
             WHERE j.id != ? AND (j.category_id = ? OR j.job_type = ?) AND j.status = 'active' 
             LIMIT 3";
$simStmt = $connect->prepare($simQuery);
$catIdParam = $job['category_id'] ?? 0;
$simStmt->bind_param("iis", $jobId, $catIdParam, $job['job_type']);
$simStmt->execute();
$similarJobs = $simStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$simStmt->close();

$pageTitle = htmlspecialchars($job['title']) . " - " . htmlspecialchars($job['company_name']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Job Header Card -->
<div class="obj-width my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>jobs.php">Jobs</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($job['title']) ?></li>
        </ol>
    </nav>

    <div class="job-detail-header-card">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="d-flex align-items-center gap-4">
                <div class="company-logo-wrap" style="width: 80px; height: 80px;">
                    <img src="<?= BASE_URL ?><?= !empty($job['company_logo']) ? htmlspecialchars($job['company_logo']) : 'images/google.png' ?>" alt="Logo">
                </div>
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1"><?= htmlspecialchars($job['title']) ?></h1>
                    <p class="text-muted mb-2">
                        <strong class="text-dark"><?= htmlspecialchars($job['company_name']) ?></strong> &bull; 
                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($job['location']) ?> &bull; 
                        <span class="text-success"><i class="bi bi-tag me-1"></i> <?= htmlspecialchars($job['category_name'] ?? 'General') ?></span>
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge-type"><?= htmlspecialchars($job['job_type']) ?></span>
                        <span class="badge-workplace"><?= htmlspecialchars($job['workplace_type']) ?></span>
                        <span class="badge-location"><i class="bi bi-clock me-1"></i> Posted <?= time_ago($job['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if (is_student()): ?>
                    <a href="<?= BASE_URL ?>apply_job.php?action=bookmark&job_id=<?= $job['id'] ?>" class="btn btn-outline-secondary rounded-pill px-3" title="<?= $isSaved ? 'Remove Bookmark' : 'Save Job' ?>">
                        <i class="bi <?= $isSaved ? 'bi-bookmark-fill text-success' : 'bi-bookmark' ?> me-1"></i> <?= $isSaved ? 'Saved' : 'Save' ?>
                    </a>
                    
                    <?php if ($hasApplied): ?>
                        <div class="text-end">
                            <span class="status-badge <?= htmlspecialchars($applicationData['status']) ?> px-4 py-2 fs-6">
                                <i class="bi bi-check-circle-fill me-1"></i> Applied (<?= ucfirst(htmlspecialchars($applicationData['status'])) ?>)
                            </span>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary-ugpro py-2 px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#applyModal" style="width: auto;">
                            <i class="bi bi-send-fill me-1"></i> Apply for Job
                        </button>
                    <?php endif; ?>
                <?php elseif (is_employer()): ?>
                    <span class="badge bg-light text-dark border p-2">Employer View</span>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>signin_undergraduate.php?redirect=<?= urlencode('job_details.php?id=' . $job['id']) ?>" class="btn btn-primary-ugpro py-2 px-4 rounded-pill" style="width: auto;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Apply
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Specs Grid -->
        <div class="job-spec-grid">
            <div class="job-spec-item">
                <div class="job-spec-icon"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <label>Salary / Compensation</label>
                    <span><?= htmlspecialchars($job['salary_range']) ?></span>
                </div>
            </div>
            <div class="job-spec-item">
                <div class="job-spec-icon"><i class="bi bi-people"></i></div>
                <div>
                    <label>Vacancies</label>
                    <span><?= htmlspecialchars($job['vacancy_count']) ?> Position(s)</span>
                </div>
            </div>
            <div class="job-spec-item">
                <div class="job-spec-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <label>Working Hours</label>
                    <span><?= htmlspecialchars($job['working_hours']) ?></span>
                </div>
            </div>
            <div class="job-spec-item">
                <div class="job-spec-icon"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <label>Target Level</label>
                    <span><?= htmlspecialchars($job['experience_level']) ?></span>
                </div>
            </div>
            <div class="job-spec-item">
                <div class="job-spec-icon"><i class="bi bi-calendar-event"></i></div>
                <div>
                    <label>Application Deadline</label>
                    <span><?= !empty($job['deadline']) ? date('M d, Y', strtotime($job['deadline'])) : 'Open until filled' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Content Layout -->
    <div class="row g-4">
        <!-- Left Detailed Specifications -->
        <div class="col-lg-8">
            <!-- Job Description -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-file-text text-success me-2"></i>Job Description & Overview</h4>
                <div class="text-muted leading-relaxed mb-4">
                    <?= nl2br(htmlspecialchars($job['description'])) ?>
                </div>

                <?php if (!empty($job['responsibilities'])): ?>
                    <h5 class="fw-bold mb-3 mt-4"><i class="bi bi-list-check text-success me-2"></i>Key Responsibilities</h5>
                    <div class="text-muted leading-relaxed mb-4">
                        <?= nl2br(htmlspecialchars($job['responsibilities'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($job['requirements'])): ?>
                    <h5 class="fw-bold mb-3 mt-4"><i class="bi bi-check2-circle text-success me-2"></i>Candidate Qualifications & Skills</h5>
                    <div class="text-muted leading-relaxed mb-4">
                        <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($job['benefits'])): ?>
                    <h5 class="fw-bold mb-3 mt-4"><i class="bi bi-gift text-success me-2"></i>Benefits & What We Offer</h5>
                    <div class="text-muted leading-relaxed mb-0">
                        <?= nl2br(htmlspecialchars($job['benefits'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar Info -->
        <div class="col-lg-4">
            <!-- Company Overview Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3">About the Employer</h5>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= BASE_URL ?><?= !empty($job['company_logo']) ? htmlspecialchars($job['company_logo']) : 'images/google.png' ?>" class="rounded-3 border p-1" width="50" height="50" alt="Logo">
                    <div>
                        <strong class="d-block text-dark"><?= htmlspecialchars($job['company_name']) ?></strong>
                        <span class="text-muted small"><?= htmlspecialchars($job['industry'] ?? 'Information Technology') ?></span>
                    </div>
                </div>

                <?php if (!empty($job['company_about'])): ?>
                    <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($job['company_about'])) ?></p>
                <?php endif; ?>

                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-success"></i><?= htmlspecialchars($job['company_location'] ?? 'Colombo, Sri Lanka') ?></li>
                    <?php if (!empty($job['company_website'])): ?>
                        <li class="mb-0"><i class="bi bi-globe me-2 text-success"></i><a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank" class="text-success text-decoration-none">Visit Official Website</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Similar Jobs Sidebar -->
            <?php if (!empty($similarJobs)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">Similar Job Vacancies</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($similarJobs as $sj): ?>
                            <div class="d-flex align-items-center gap-3 pb-3 border-bottom">
                                <img src="<?= BASE_URL ?><?= !empty($sj['company_logo']) ? htmlspecialchars($sj['company_logo']) : 'images/google.png' ?>" class="rounded-3 border p-1" width="40" height="40" alt="Logo">
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark small"><a href="<?= BASE_URL ?>job_details.php?id=<?= $sj['id'] ?>"><?= htmlspecialchars($sj['title']) ?></a></strong>
                                    <span class="text-muted small"><?= htmlspecialchars($sj['company_name']) ?> &bull; <?= htmlspecialchars($sj['job_type']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Apply Modal for Undergraduates -->
<?php if (is_student() && !$hasApplied): ?>
<div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="applyModalLabel">Apply for <?= htmlspecialchars($job['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="<?= BASE_URL ?>apply_job.php">
                <div class="modal-body py-4">
                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Candidate Profile:</label>
                        <div class="bg-light p-2 rounded-3 small">
                            <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> (<?= htmlspecialchars($_SESSION['user_email']) ?>)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cover_letter" class="form-label small fw-bold">Cover Letter / Note to Employer:</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="4" placeholder="Highlight your relevant university projects, technical competencies, and passion for this opportunity..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Resume / CV Attachment:</label>
                        <?php if (!empty($studentResume)): ?>
                            <div class="alert alert-success py-2 small mb-2 d-flex align-items-center">
                                <i class="bi bi-file-earmark-check me-2 fs-5"></i>
                                <div>Using your current profile CV (<code><?= basename($studentResume) ?></code>)</div>
                            </div>
                        <?php endif; ?>
                        <label for="resume_file" class="form-label small text-muted">Upload customized CV (Optional PDF, max 10MB):</label>
                        <input type="file" class="form-control form-control-sm" id="resume_file" name="resume_file" accept="application/pdf">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-ugpro py-2 px-4 rounded-pill" style="width: auto;">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
