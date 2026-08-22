<?php
/**
 * Edit Job Posting - UgPro
 */
require_once __DIR__ . '/includes/auth.php';
require_employer_auth();

$employerId = $_SESSION['user_id'];
$jobId = intval($_GET['id'] ?? 0);
$errors = [];

if ($jobId <= 0) {
    header("Location: " . BASE_URL . "profile_employer.php");
    exit();
}

// Fetch existing job
$job = null;
if (is_db_connected()) {
    try {
        $stmt = @$connect->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $jobId, $employerId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $job = $result->fetch_assoc();
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        $job = null;
    }
}

if (!$job) {
    $fallbackJobs = get_fallback_jobs();
    foreach ($fallbackJobs as $fj) {
        if ($fj['id'] == $jobId) {
            $job = $fj;
            break;
        }
    }
    if (!$job && !empty($fallbackJobs)) {
        $job = $fallbackJobs[0];
    }
}

if (!$job) {
    set_flash('danger', 'Job posting not found or unauthorized access.');
    header("Location: " . BASE_URL . "profile_employer.php");
    exit();
}

// Fetch categories
$categories = [];
if (is_db_connected()) {
    $catResult = @$connect->query("SELECT * FROM job_categories ORDER BY name ASC");
    if ($catResult) {
        $categories = $catResult->fetch_all(MYSQLI_ASSOC);
    }
}
if (empty($categories)) {
    $categories = get_fallback_categories();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $jobType = clean_input($_POST['job_type'] ?? 'Full Time');
    $workplaceType = clean_input($_POST['workplace_type'] ?? 'On-site');
    $location = clean_input($_POST['location'] ?? 'Colombo, Sri Lanka');
    $salaryRange = clean_input($_POST['salary_range'] ?? 'Negotiable');
    $vacancyCount = intval($_POST['vacancy_count'] ?? 1);
    $workingHours = clean_input($_POST['working_hours'] ?? '40h / week');
    $experienceLevel = clean_input($_POST['experience_level'] ?? 'Entry Level / Undergraduate');
    $educationReq = clean_input($_POST['education_req'] ?? 'Bachelor\'s Degree');
    $description = clean_input($_POST['description'] ?? '');
    $responsibilities = clean_input($_POST['responsibilities'] ?? '');
    $requirements = clean_input($_POST['requirements'] ?? '');
    $benefits = clean_input($_POST['benefits'] ?? '');
    $deadline = !empty($_POST['deadline']) ? clean_input($_POST['deadline']) : null;
    $status = clean_input($_POST['status'] ?? 'active');

    if (empty($title)) $errors[] = "Job title is required.";
    if (empty($description)) $errors[] = "Job description is required.";

    if (empty($errors)) {
        if (is_db_connected()) {
            try {
                $updateStmt = @$connect->prepare("UPDATE jobs SET category_id = ?, title = ?, job_type = ?, workplace_type = ?, location = ?, salary_range = ?, vacancy_count = ?, working_hours = ?, experience_level = ?, education_req = ?, description = ?, responsibilities = ?, requirements = ?, benefits = ?, deadline = ?, status = ? WHERE id = ? AND employer_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("isssssisssssssssii", $categoryId, $title, $jobType, $workplaceType, $location, $salaryRange, $vacancyCount, $workingHours, $experienceLevel, $educationReq, $description, $responsibilities, $requirements, $benefits, $deadline, $status, $jobId, $employerId);
                    if ($updateStmt->execute()) {
                        set_flash('success', "Job posting '{$title}' has been successfully updated!");
                        header("Location: " . BASE_URL . "profile_employer.php");
                        exit();
                    } else {
                        $errors[] = "Failed to update job: " . $connect->error;
                    }
                    $updateStmt->close();
                }
            } catch (Throwable $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        } else {
            set_flash('success', "Demo Mode: Job posting '{$title}' updated (simulated).");
            header("Location: " . BASE_URL . "profile_employer.php");
            exit();
        }
    }
}

$pageTitle = "Edit Job - " . htmlspecialchars($job['title']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="obj-width my-5" style="max-width: 900px;">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-pencil-square text-success me-2"></i>Edit Job Vacancy</h2>
                <p class="text-muted small mb-0">Modify specifications and details for this job posting</p>
            </div>
            <a href="<?= BASE_URL ?>profile_employer.php" class="btn btn-outline-secondary rounded-pill btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="employer_edit_job.php?id=<?= $jobId ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Job Title *</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Industry Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $job['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type">
                        <option value="Full Time" <?= $job['job_type'] === 'Full Time' ? 'selected' : '' ?>>Full Time</option>
                        <option value="Internship" <?= $job['job_type'] === 'Internship' ? 'selected' : '' ?>>Internship</option>
                        <option value="Part Time" <?= $job['job_type'] === 'Part Time' ? 'selected' : '' ?>>Part Time</option>
                        <option value="Freelancer" <?= $job['job_type'] === 'Freelancer' ? 'selected' : '' ?>>Freelancer</option>
                        <option value="Contract" <?= $job['job_type'] === 'Contract' ? 'selected' : '' ?>>Contract</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="workplace_type" class="form-label">Workplace Model</label>
                    <select class="form-select" id="workplace_type" name="workplace_type">
                        <option value="On-site" <?= $job['workplace_type'] === 'On-site' ? 'selected' : '' ?>>On-site</option>
                        <option value="Hybrid" <?= $job['workplace_type'] === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                        <option value="Remote" <?= $job['workplace_type'] === 'Remote' ? 'selected' : '' ?>>Remote</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($job['location']) ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Publish Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $job['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed (Hidden)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="salary_range" class="form-label">Salary / Compensation</label>
                    <input type="text" class="form-control" id="salary_range" name="salary_range" value="<?= htmlspecialchars($job['salary_range']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="vacancy_count" class="form-label">Number of Vacancies</label>
                    <input type="number" class="form-control" id="vacancy_count" name="vacancy_count" min="1" max="100" value="<?= htmlspecialchars($job['vacancy_count']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="deadline" class="form-label">Application Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= htmlspecialchars($job['deadline'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label for="working_hours" class="form-label">Working Hours</label>
                    <input type="text" class="form-control" id="working_hours" name="working_hours" value="<?= htmlspecialchars($job['working_hours'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="experience_level" class="form-label">Experience Level</label>
                    <input type="text" class="form-control" id="experience_level" name="experience_level" value="<?= htmlspecialchars($job['experience_level'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label for="education_req" class="form-label">Education Requirements</label>
                    <input type="text" class="form-control" id="education_req" name="education_req" value="<?= htmlspecialchars($job['education_req'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Job Overview & Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($job['description']) ?></textarea>
                </div>

                <div class="col-12">
                    <label for="responsibilities" class="form-label">Key Responsibilities</label>
                    <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4"><?= htmlspecialchars($job['responsibilities'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label for="requirements" class="form-label">Requirements</label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="4"><?= htmlspecialchars($job['requirements'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label for="benefits" class="form-label">Perks & Benefits</label>
                    <textarea class="form-control" id="benefits" name="benefits" rows="3"><?= htmlspecialchars($job['benefits'] ?? '') ?></textarea>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="<?= BASE_URL ?>profile_employer.php" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary-ugpro py-3 px-5 rounded-pill" style="width: auto;">Update Job Vacancy</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
