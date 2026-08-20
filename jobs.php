<?php
/**
 * Browse & Search Jobs - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Filter parameters
$search = clean_input($_GET['q'] ?? '');
$categorySlug = clean_input($_GET['category'] ?? '');
$jobType = clean_input($_GET['type'] ?? '');
$workplace = clean_input($_GET['workplace'] ?? '');
$location = clean_input($_GET['location'] ?? '');

// Fetch Categories
$categories = [];
if ($connect) {
    $catRes = $connect->query("SELECT * FROM job_categories ORDER BY name ASC");
    if ($catRes) $categories = $catRes->fetch_all(MYSQLI_ASSOC);
}

// Build query
$query = "SELECT j.*, e.company_name, e.company_logo, c.name AS category_name, c.slug AS category_slug 
          FROM jobs j 
          JOIN employer e ON j.employer_id = e.id 
          LEFT JOIN job_categories c ON j.category_id = c.id 
          WHERE j.status = 'active'";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR e.company_name LIKE ?)";
    $term = "%" . $search . "%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "ssss";
}

if (!empty($categorySlug)) {
    $query .= " AND c.slug = ?";
    $params[] = $categorySlug;
    $types .= "s";
}

if (!empty($jobType)) {
    $query .= " AND j.job_type = ?";
    $params[] = $jobType;
    $types .= "s";
}

if (!empty($workplace)) {
    $query .= " AND j.workplace_type = ?";
    $params[] = $workplace;
    $types .= "s";
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%" . $location . "%";
    $types .= "s";
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $connect->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = "Browse Jobs & Internships - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Header Banner -->
<div class="dashboard-header-banner">
    <div class="obj-width text-center">
        <h1 class="h2 text-white fw-bold mb-2">Explore Verified Career Opportunities</h1>
        <p class="text-white-50 mb-4">Connecting Vavuniya University students with top tech firms, enterprises & startups</p>
        
        <!-- Search Bar -->
        <div class="mx-auto" style="max-width: 650px;">
            <form method="GET" action="jobs.php" class="bg-white p-2 rounded-pill shadow-lg d-flex align-items-center">
                <i class="bi bi-search text-muted ms-3 fs-5"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none px-3" placeholder="Job title, keywords, or company name..." value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($categorySlug)): ?><input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>"><?php endif; ?>
                <button type="submit" class="btn btn-primary-ugpro rounded-pill px-4" style="width: auto;">Find Jobs</button>
            </form>
        </div>
    </div>
</div>

<div class="obj-width my-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-funnel text-success me-2"></i>Filters</h5>
                    <a href="jobs.php" class="small text-muted text-decoration-none">Clear All</a>
                </div>

                <form method="GET" action="jobs.php">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>

                    <!-- Category Filter -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Category</label>
                        <select class="form-select form-select-sm" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Job Type -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Employment Type</label>
                        <?php $typesList = ['Full Time', 'Internship', 'Part Time', 'Freelancer', 'Contract']; ?>
                        <?php foreach ($typesList as $t): ?>
                            <div class="form-check small mb-2">
                                <input class="form-check-input" type="radio" name="type" id="type_<?= strtolower(str_replace(' ', '_', $t)) ?>" value="<?= $t ?>" <?= $jobType === $t ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label" for="type_<?= strtolower(str_replace(' ', '_', $t)) ?>"><?= $t ?></label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!empty($jobType)): ?>
                            <a href="jobs.php?<?= http_build_query(array_merge($_GET, ['type' => ''])) ?>" class="small text-danger d-block mt-1">Clear Type</a>
                        <?php endif; ?>
                    </div>

                    <!-- Workplace Model -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Workplace Model</label>
                        <?php $workplaces = ['On-site', 'Hybrid', 'Remote']; ?>
                        <?php foreach ($workplaces as $wp): ?>
                            <div class="form-check small mb-2">
                                <input class="form-check-input" type="radio" name="workplace" id="wp_<?= strtolower($wp) ?>" value="<?= $wp ?>" <?= $workplace === $wp ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label" for="wp_<?= strtolower($wp) ?>"><?= $wp ?></label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!empty($workplace)): ?>
                            <a href="jobs.php?<?= http_build_query(array_merge($_GET, ['workplace' => ''])) ?>" class="small text-danger d-block mt-1">Clear Model</a>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">Apply Filter Changes</button>
                </form>
            </div>
        </div>

        <!-- Job Cards Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-muted fw-semibold"><?= count($jobs) ?> Active Job Listings Available</span>
                <?php if (!empty($search) || !empty($categorySlug) || !empty($jobType) || !empty($workplace)): ?>
                    <span class="badge bg-success rounded-pill px-3 py-2">Filtered Results</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($jobs)): ?>
                <div class="row g-4">
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-md-6">
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div class="company-logo-wrap">
                                        <img src="<?= BASE_URL ?><?= !empty($job['company_logo']) ? htmlspecialchars($job['company_logo']) : 'images/google.png' ?>" alt="Logo">
                                    </div>
                                    <div class="job-title-wrap">
                                        <h3 class="job-card-title">
                                            <a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a>
                                        </h3>
                                        <span class="company-name-text"><?= htmlspecialchars($job['company_name']) ?></span>
                                    </div>
                                </div>

                                <div class="job-card-badges">
                                    <span class="badge-type"><?= htmlspecialchars($job['job_type']) ?></span>
                                    <span class="badge-workplace"><?= htmlspecialchars($job['workplace_type']) ?></span>
                                    <span class="badge-location"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                </div>

                                <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($job['description']) ?>
                                </p>

                                <div class="job-card-salary"><?= htmlspecialchars($job['salary_range']) ?></div>

                                <div class="job-card-footer">
                                    <span class="job-card-time"><?= time_ago($job['created_at']) ?></span>
                                    <div class="d-flex gap-2 align-items-center">
                                        <?php if (is_student()): ?>
                                            <a href="<?= BASE_URL ?>apply_job.php?action=bookmark&job_id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-circle" title="Bookmark Job"><i class="bi bi-bookmark"></i></a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>" class="btn-view-job">View & Apply</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="bi bi-search text-muted fs-1 mb-3"></i>
                    <h5>No jobs matched your criteria</h5>
                    <p class="text-muted small">Try broadening your search term or clearing filter selections.</p>
                    <a href="jobs.php" class="btn btn-primary-ugpro rounded-pill btn-sm px-4 mx-auto" style="width: auto;">View All Jobs</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
