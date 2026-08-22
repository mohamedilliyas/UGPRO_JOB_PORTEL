<?php
/**
 * UgPro University Job & Career Portal - Home Page
 */
require_once __DIR__ . '/includes/auth.php';

// Fetch Live Statistics with micro-cache to minimize DB round-trips
$stats = null;
if (!empty($_SESSION['_ugpro_stats']) && !empty($_SESSION['_ugpro_stats_time']) && (time() - $_SESSION['_ugpro_stats_time'] < 120)) {
    $stats = $_SESSION['_ugpro_stats'];
}

if (!$stats) {
    $stats = get_fallback_stats();
    if (is_db_connected()) {
        $statsRes = @$connect->query("SELECT 
            (SELECT COUNT(*) FROM jobs WHERE status = 'active') AS total_jobs,
            (SELECT COUNT(*) FROM employer) AS total_employers,
            (SELECT COUNT(*) FROM undergraduate) AS total_students,
            (SELECT COUNT(*) FROM job_applications) AS total_applications");
        
        if ($statsRes && $row = $statsRes->fetch_assoc()) {
            $stats = [
                'total_jobs' => max(1, (int)($row['total_jobs'] ?? 12)),
                'total_employers' => max(1, (int)($row['total_employers'] ?? 8)),
                'total_students' => max(1, (int)($row['total_students'] ?? 150)),
                'total_applications' => max(1, (int)($row['total_applications'] ?? 45))
            ];
            $_SESSION['_ugpro_stats'] = $stats;
            $_SESSION['_ugpro_stats_time'] = time();
        }
    }
}

// Fetch Featured Jobs (single efficient query with limit)
$featuredJobs = [];
if (is_db_connected()) {
    $fjQuery = "SELECT j.id, j.title, j.job_type, j.workplace_type, j.location, j.salary_range, j.created_at,
                       e.company_name, e.company_logo, c.name AS category_name 
                FROM jobs j 
                JOIN employer e ON j.employer_id = e.id 
                LEFT JOIN job_categories c ON j.category_id = c.id 
                WHERE j.status = 'active' 
                ORDER BY j.id DESC 
                LIMIT 6";
    $fjRes = @$connect->query($fjQuery);
    if ($fjRes && $fjRes->num_rows > 0) {
        $featuredJobs = $fjRes->fetch_all(MYSQLI_ASSOC);
    }
}
if (empty($featuredJobs)) {
    $featuredJobs = get_fallback_jobs();
}

// Fetch Categories for Quick Browse (cached in session)
$categories = $_SESSION['_ugpro_categories'] ?? [];
if (empty($categories)) {
    if (is_db_connected()) {
        $catRes = @$connect->query("SELECT id, name, icon, slug FROM job_categories ORDER BY name ASC LIMIT 8");
        if ($catRes && $catRes->num_rows > 0) {
            $categories = $catRes->fetch_all(MYSQLI_ASSOC);
            $_SESSION['_ugpro_categories'] = $categories;
        }
    }
}
if (empty($categories)) {
    $categories = get_fallback_categories();
}

$pageTitle = "UgPro - University Career & Job Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-modern" id="home">
    <div class="obj-width">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="hero-badge">
                    <i class="bi bi-patch-check-fill text-success"></i> Official University of Vavuniya Career Network
                </div>
                <h1 class="hero-title">
                    Connecting <span>Undergraduates</span> with Leading Tech & Industry Employers
                </h1>
                <p class="hero-subtitle">
                    Discover verified internships, graduate associate roles, and project opportunities tailored specifically for university talent.
                </p>

                <!-- Search Box Form -->
                <form method="GET" action="<?= BASE_URL ?>jobs.php" class="hero-search-box mb-4">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Job title, technical skill, or company...">
                    <button type="submit" class="hero-search-btn">Search Jobs</button>
                </form>

                <!-- Dynamic Live Stats -->
                <div class="hero-stats-row">
                    <div class="hero-stat-item">
                        <h3><?= number_format($stats['total_jobs']) ?>+</h3>
                        <p>Active Vacancies</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3><?= number_format($stats['total_employers']) ?>+</h3>
                        <p>Partner Companies</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3><?= number_format($stats['total_students']) ?>+</h3>
                        <p>Registered Students</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-img-card">
                    <img src="<?= BASE_URL ?>images/hero1.PNG" alt="Student Career Portal" class="hero-img-main">
                    
                    <div class="hero-floating-badge">
                        <div class="bg-success text-white p-2 rounded-circle">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <strong class="d-block small">100% University Verified</strong>
                            <span class="text-muted" style="font-size: 0.75rem;">Authentication & Placement</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Job Categories Quick Browse -->
<section class="sec-space bg-white border-bottom">
    <div class="obj-width">
        <div class="section-header">
            <span class="section-tag">Explore Fields</span>
            <h2>Top Career Categories</h2>
            <p>Browse job vacancies across highest-demand industries</p>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="<?= BASE_URL ?>jobs.php?category=<?= htmlspecialchars($cat['slug']) ?>" class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 text-decoration-none hover-card" style="background: var(--light-bg); transition: var(--transition);">
                        <div class="feature-icon-wrap mb-2" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="bi <?= htmlspecialchars($cat['icon'] ?? 'bi-briefcase') ?>"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                        <span class="text-muted small">Explore Jobs &rarr;</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Jobs Section -->
<section class="sec-space" id="jobs">
    <div class="obj-width">
        <div class="section-header">
            <span class="section-tag">Opportunities</span>
            <h2>Jobs in Demand</h2>
            <p>Recently published vacancies from top technology partners</p>
        </div>

        <!-- Filter Pills -->
        <div class="category-pills">
            <a href="<?= BASE_URL ?>jobs.php" class="cat-pill active">Recent Jobs</a>
            <a href="<?= BASE_URL ?>jobs.php?type=Full+Time" class="cat-pill">Full Time</a>
            <a href="<?= BASE_URL ?>jobs.php?type=Internship" class="cat-pill">Internships</a>
            <a href="<?= BASE_URL ?>jobs.php?workplace=Remote" class="cat-pill">Remote Roles</a>
            <a href="<?= BASE_URL ?>jobs.php?type=Freelancer" class="cat-pill">Freelancer</a>
        </div>

        <!-- Jobs Grid -->
        <?php if (!empty($featuredJobs)): ?>
            <div class="row g-4 mb-5">
                <?php foreach ($featuredJobs as $job): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="job-card">
                            <div class="job-card-header">
                                <div class="company-logo-wrap">
                                    <img src="<?= BASE_URL ?><?= !empty($job['company_logo']) ? htmlspecialchars($job['company_logo']) : 'images/google.png' ?>" alt="Company Logo">
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
                                <a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>" class="btn-view-job">View & Apply</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No vacancies posted yet.</p>
        <?php endif; ?>

        <div class="text-center">
            <a href="<?= BASE_URL ?>jobs.php" class="btn btn-primary-ugpro rounded-pill px-5 py-3" style="width: auto;">
                <i class="bi bi-grid-fill me-2"></i> View All Available Job Vacancies
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="sec-space bg-white border-top border-bottom" id="whyChooseUs">
    <div class="obj-width">
        <div class="section-header">
            <span class="section-tag">Our Advantages</span>
            <h2>Why Choose UgPro?</h2>
            <p>Designed specifically to empower students and streamline employer hiring</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <h3>Verified Job Listings</h3>
                    <p>Every position is authenticated by the university career guidance team, preventing spam or counterfeit opportunities.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <h3>Skill-Based Matching</h3>
                    <p>Easily discover positions aligned with your academic courses, technical stack, projects, and career aspirations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <h3>University Support</h3>
                    <p>Access direct support from academic advisors, mock interviews, and career counseling throughout the placement cycle.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partner Companies Section -->
<section class="sec-space" id="ourPartnerCompanies">
    <div class="obj-width text-center">
        <div class="section-header">
            <span class="section-tag">Industry Network</span>
            <h2>Our Partner Companies</h2>
            <p>Top multinational enterprises & technology leaders hiring from our university</p>
        </div>

        <div class="partner-logos-wrap">
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/t1.png" alt="Partner 1"></div>
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/t2.png" alt="Partner 2"></div>
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/t3.png" alt="Partner 3"></div>
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/t5.png" alt="Partner 4"></div>
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/google.png" alt="Partner Google"></div>
            <div class="partner-logo-item"><img src="<?= BASE_URL ?>images/uber.png" alt="Partner Uber"></div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="sec-space bg-dark text-white text-center" style="background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%) !important;">
    <div class="obj-width" style="max-width: 800px;">
        <h2 class="text-white fw-bold mb-3">Ready to Launch Your Career Journey?</h2>
        <p class="text-white-50 mb-4 fs-5">Create your student profile today, showcase your portfolio, and apply for life-changing internships and graduate roles.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= BASE_URL ?>signup_undergraduate.php" class="btn btn-success btn-lg rounded-pill px-5">Join as Undergraduate</a>
            <a href="<?= BASE_URL ?>signup_employer.php" class="btn btn-outline-light btn-lg rounded-pill px-5">Register as Employer</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>