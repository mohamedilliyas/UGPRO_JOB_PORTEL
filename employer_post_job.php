<?php
/**
 * Post a New Job - UgPro
 */
require_once __DIR__ . '/includes/auth.php';
require_employer_auth();

$employerId = $_SESSION['user_id'];
$errors = [];

// Fetch job categories for dropdown
$categories = [];
if ($connect) {
    $catResult = $connect->query("SELECT * FROM job_categories ORDER BY name ASC");
    if ($catResult) {
        $categories = $catResult->fetch_all(MYSQLI_ASSOC);
    }
}

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
    $educationReq = clean_input($_POST['education_req'] ?? 'Bachelor\'s Degree in IT / Computer Science or related');
    $description = clean_input($_POST['description'] ?? '');
    $responsibilities = clean_input($_POST['responsibilities'] ?? '');
    $requirements = clean_input($_POST['requirements'] ?? '');
    $benefits = clean_input($_POST['benefits'] ?? '');
    $deadline = !empty($_POST['deadline']) ? clean_input($_POST['deadline']) : null;

    if (empty($title)) $errors[] = "Job title is required.";
    if (empty($description)) $errors[] = "Job description is required.";

    if (empty($errors)) {
        $stmt = $connect->prepare("INSERT INTO jobs (employer_id, category_id, title, job_type, workplace_type, location, salary_range, vacancy_count, working_hours, experience_level, education_req, description, responsibilities, requirements, benefits, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("iisssssissssssss", $employerId, $categoryId, $title, $jobType, $workplaceType, $location, $salaryRange, $vacancyCount, $workingHours, $experienceLevel, $educationReq, $description, $responsibilities, $requirements, $benefits, $deadline);

        if ($stmt->execute()) {
            $newJobId = $stmt->insert_id;
            set_flash('success', "Job posting '{$title}' has been successfully published!");
            header("Location: " . BASE_URL . "job_details.php?id=" . $newJobId);
            exit();
        } else {
            $errors[] = "Failed to post job: " . $connect->error;
        }
        $stmt->close();
    }
}

$pageTitle = "Post a New Job - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="obj-width my-5" style="max-width: 900px;">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-plus-circle text-success me-2"></i>Post a New Vacancy</h2>
                <p class="text-muted small mb-0">Reach hundreds of qualified university undergraduates and fresh graduates</p>
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

        <form method="POST" action="<?= BASE_URL ?>employer_post_job.php">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Job Title *</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Associate Software Engineer - Web" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Industry Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type">
                        <option value="Full Time">Full Time</option>
                        <option value="Internship">Internship</option>
                        <option value="Part Time">Part Time</option>
                        <option value="Freelancer">Freelancer</option>
                        <option value="Contract">Contract</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="workplace_type" class="form-label">Workplace Model</label>
                    <select class="form-select" id="workplace_type" name="workplace_type">
                        <option value="On-site">On-site</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="Remote">Remote</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="location" class="form-label">Job Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Colombo 03, Sri Lanka" value="<?= htmlspecialchars($_POST['location'] ?? 'Colombo, Sri Lanka') ?>">
                </div>

                <div class="col-md-4">
                    <label for="salary_range" class="form-label">Salary / Compensation</label>
                    <input type="text" class="form-control" id="salary_range" name="salary_range" placeholder="e.g. LKR 100,000 - 150,000 / month" value="<?= htmlspecialchars($_POST['salary_range'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="vacancy_count" class="form-label">Number of Vacancies</label>
                    <input type="number" class="form-control" id="vacancy_count" name="vacancy_count" min="1" max="100" value="<?= htmlspecialchars($_POST['vacancy_count'] ?? '1') ?>">
                </div>
                <div class="col-md-4">
                    <label for="deadline" class="form-label">Application Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label for="working_hours" class="form-label">Working Hours</label>
                    <input type="text" class="form-control" id="working_hours" name="working_hours" placeholder="e.g. 40h / week" value="<?= htmlspecialchars($_POST['working_hours'] ?? '40h / week') ?>">
                </div>
                <div class="col-md-6">
                    <label for="experience_level" class="form-label">Target Experience Level</label>
                    <input type="text" class="form-control" id="experience_level" name="experience_level" placeholder="e.g. Entry Level / Fresh Graduate" value="<?= htmlspecialchars($_POST['experience_level'] ?? 'Entry Level / Undergraduate') ?>">
                </div>

                <div class="col-12">
                    <label for="education_req" class="form-label">Education / Qualification Requirements</label>
                    <input type="text" class="form-control" id="education_req" name="education_req" placeholder="e.g. BSc in Computer Science, IT, Software Engineering or equivalent" value="<?= htmlspecialchars($_POST['education_req'] ?? 'Bachelor\'s Degree in Computer Science / IT or related field') ?>">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Job Overview & Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the role, the team, and what makes this position exciting..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label for="responsibilities" class="form-label">Key Responsibilities (One per line or bullet points)</label>
                    <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4" placeholder="• Develop clean, maintainable web components&#10;• Collaborate with agile product teams&#10;• Participate in code reviews..."><?= htmlspecialchars($_POST['responsibilities'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label for="requirements" class="form-label">Skills & Technical Requirements (One per line)</label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="4" placeholder="• Proficiency in PHP, MySQL, JavaScript&#10;• Understanding of REST APIs and MVC&#10;• Good problem solving and communication..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label for="benefits" class="form-label">Perks & Benefits</label>
                    <textarea class="form-control" id="benefits" name="benefits" rows="3" placeholder="• Comprehensive health insurance&#10;• Flexible hybrid working model&#10;• Mentorship and career growth..."><?= htmlspecialchars($_POST['benefits'] ?? '') ?></textarea>
                </div>

                <div class="col-12 mt-4 text-end">
                    <a href="<?= BASE_URL ?>profile_employer.php" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary-ugpro py-3 px-5 rounded-pill" style="width: auto;">Publish Job Vacancy</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
