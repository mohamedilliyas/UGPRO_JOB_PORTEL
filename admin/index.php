<?php
/**
 * Administrator Control Panel - UgPro
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();

$activeTab = $_GET['tab'] ?? 'overview';

// Handle Admin Actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);

    // 1. Toggle Job Status
    if ($action === 'toggle_job' && $id > 0) {
        $connect->query("UPDATE jobs SET status = IF(status = 'active', 'closed', 'active') WHERE id = {$id}");
        set_flash('success', 'Job status updated.');
        header("Location: index.php?tab=jobs");
        exit();
    }

    // 2. Delete Job
    if ($action === 'delete_job' && $id > 0) {
        $connect->query("DELETE FROM jobs WHERE id = {$id}");
        set_flash('success', 'Job posting deleted.');
        header("Location: index.php?tab=jobs");
        exit();
    }

    // 3. Toggle Student Status (active / banned)
    if ($action === 'toggle_student' && $id > 0) {
        $connect->query("UPDATE undergraduate SET status = IF(status = 'active', 'banned', 'active') WHERE id = {$id}");
        set_flash('success', 'Student account status updated.');
        header("Location: index.php?tab=students");
        exit();
    }

    // 4. Delete Student
    if ($action === 'delete_student' && $id > 0) {
        $connect->query("DELETE FROM undergraduate WHERE id = {$id}");
        set_flash('success', 'Student record deleted.');
        header("Location: index.php?tab=students");
        exit();
    }

    // 5. Toggle Employer Status (active / suspended)
    if ($action === 'toggle_employer' && $id > 0) {
        $connect->query("UPDATE employer SET status = IF(status = 'active', 'suspended', 'active') WHERE id = {$id}");
        set_flash('success', 'Employer account status updated.');
        header("Location: index.php?tab=employers");
        exit();
    }

    // 6. Delete Employer
    if ($action === 'delete_employer' && $id > 0) {
        $connect->query("DELETE FROM employer WHERE id = {$id}");
        set_flash('success', 'Employer company deleted.');
        header("Location: index.php?tab=employers");
        exit();
    }

    // 7. Mark Message Read / Delete
    if ($action === 'mark_read' && $id > 0) {
        $connect->query("UPDATE contact_messages SET status = 'read' WHERE id = {$id}");
        header("Location: index.php?tab=messages");
        exit();
    }

    if ($action === 'delete_msg' && $id > 0) {
        $connect->query("DELETE FROM contact_messages WHERE id = {$id}");
        set_flash('success', 'Message deleted.');
        header("Location: index.php?tab=messages");
        exit();
    }
}

// Handle Add Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean_input($_POST['cat_name'] ?? '');
    $icon = clean_input($_POST['cat_icon'] ?? 'bi-briefcase');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!empty($name)) {
        $stmt = $connect->prepare("INSERT INTO job_categories (name, slug, icon) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $slug, $icon);
        if ($stmt->execute()) {
            set_flash('success', "Category '{$name}' created successfully.");
        } else {
            set_flash('danger', 'Failed to add category: ' . $connect->error);
        }
        $stmt->close();
        header("Location: index.php?tab=categories");
        exit();
    }
}

// Fetch Metrics Counts
$totalStudents = $connect->query("SELECT COUNT(*) AS c FROM undergraduate")->fetch_assoc()['c'] ?? 0;
$totalEmployers = $connect->query("SELECT COUNT(*) AS c FROM employer")->fetch_assoc()['c'] ?? 0;
$totalJobs = $connect->query("SELECT COUNT(*) AS c FROM jobs")->fetch_assoc()['c'] ?? 0;
$totalApps = $connect->query("SELECT COUNT(*) AS c FROM job_applications")->fetch_assoc()['c'] ?? 0;
$unreadMessages = $connect->query("SELECT COUNT(*) AS c FROM contact_messages WHERE status = 'unread'")->fetch_assoc()['c'] ?? 0;

// Fetch Data for Tables
$allJobs = $connect->query("SELECT j.*, e.company_name, (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) AS applicants_count FROM jobs j JOIN employer e ON j.employer_id = e.id ORDER BY j.id DESC")->fetch_all(MYSQLI_ASSOC);
$allStudents = $connect->query("SELECT u.*, (SELECT COUNT(*) FROM job_applications WHERE undergraduate_id = u.id) AS applications_count FROM undergraduate u ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);
$allEmployers = $connect->query("SELECT e.*, (SELECT COUNT(*) FROM jobs WHERE employer_id = e.id) AS jobs_count FROM employer e ORDER BY e.id DESC")->fetch_all(MYSQLI_ASSOC);
$allCategories = $connect->query("SELECT c.*, (SELECT COUNT(*) FROM jobs WHERE category_id = c.id) AS jobs_count FROM job_categories c ORDER BY c.name ASC")->fetch_all(MYSQLI_ASSOC);
$allMessages = $connect->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = "Administrator Dashboard - UgPro";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header Banner -->
<div class="dashboard-header-banner bg-dark" style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;">
    <div class="obj-width">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h2 text-white fw-bold mb-1"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Administrator Control Center</h1>
                <p class="text-white-50 mb-0">System metrics, job moderation, user governance, and placement monitoring</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>index.php" class="btn btn-outline-light rounded-pill btn-sm"><i class="bi bi-globe me-1"></i> Public Site</a>
                <a href="<?= BASE_URL ?>logout.php" class="btn btn-danger rounded-pill btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="obj-width my-5">
    <!-- Stat Counters -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon green"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $totalStudents ?></h4>
                    <p>Undergraduates</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon blue"><i class="bi bi-building-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $totalEmployers ?></h4>
                    <p>Employers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon purple"><i class="bi bi-briefcase-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $totalJobs ?></h4>
                    <p>Total Vacancies</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-counter-card">
                <div class="stat-icon amber"><i class="bi bi-envelope-fill"></i></div>
                <div class="stat-info">
                    <h4><?= $unreadMessages ?></h4>
                    <p>Unread Inquiries</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs nav-tabs-modern">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" href="?tab=overview"><i class="bi bi-speedometer2 me-2"></i>Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'jobs' ? 'active' : '' ?>" href="?tab=jobs">
                <i class="bi bi-briefcase me-2"></i>Manage Jobs <span class="badge bg-secondary ms-1"><?= count($allJobs) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'students' ? 'active' : '' ?>" href="?tab=students">
                <i class="bi bi-mortarboard me-2"></i>Students <span class="badge bg-secondary ms-1"><?= count($allStudents) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'employers' ? 'active' : '' ?>" href="?tab=employers">
                <i class="bi bi-building me-2"></i>Employers <span class="badge bg-secondary ms-1"><?= count($allEmployers) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'categories' ? 'active' : '' ?>" href="?tab=categories">
                <i class="bi bi-tags me-2"></i>Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'messages' ? 'active' : '' ?>" href="?tab=messages">
                <i class="bi bi-envelope me-2"></i>Inquiries <?php if ($unreadMessages > 0): ?><span class="badge bg-danger ms-1"><?= $unreadMessages ?></span><?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- Tab 1: Overview Quick Cards -->
    <?php if ($activeTab === 'overview'): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-success me-2"></i>Recent Job Postings</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Applicants</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($allJobs, 0, 5) as $j): ?>
                                    <tr>
                                        <td><strong><a href="<?= BASE_URL ?>job_details.php?id=<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></a></strong></td>
                                        <td><?= htmlspecialchars($j['company_name']) ?></td>
                                        <td><span class="badge-type"><?= htmlspecialchars($j['job_type']) ?></span></td>
                                        <td><?= $j['applicants_count'] ?></td>
                                        <td><span class="status-badge <?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person-plus text-success me-2"></i>Newest Students</h5>
                    <ul class="list-unstyled mb-0">
                        <?php foreach (array_slice($allStudents, 0, 5) as $s): ?>
                            <li class="d-flex align-items-center gap-3 py-2 border-bottom">
                                <img src="<?= BASE_URL ?><?= !empty($s['profile_image']) ? htmlspecialchars($s['profile_image']) : 'images/fl-3.png' ?>" class="rounded-circle" width="36" height="36" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <strong class="d-block small text-dark"><?= htmlspecialchars($s['full_name']) ?></strong>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($s['course'] ?? 'Undergraduate') ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    <!-- Tab 2: Manage Jobs -->
    <?php elseif ($activeTab === 'jobs'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">All Registered Job Vacancies</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Type / Model</th>
                            <th>Applicants</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allJobs as $job): ?>
                            <tr>
                                <td><strong><a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a></strong></td>
                                <td><?= htmlspecialchars($job['company_name']) ?></td>
                                <td><span class="badge-type"><?= htmlspecialchars($job['job_type']) ?></span> (<?= htmlspecialchars($job['workplace_type']) ?>)</td>
                                <td><span class="badge bg-light text-dark border"><?= $job['applicants_count'] ?></span></td>
                                <td>
                                    <a href="?tab=jobs&action=toggle_job&id=<?= $job['id'] ?>" class="status-badge <?= $job['status'] === 'active' ? 'active' : 'closed' ?> text-decoration-none">
                                        <?= ucfirst($job['status']) ?>
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>job_details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="?tab=jobs&action=delete_job&id=<?= $job['id'] ?>" onclick="return confirm('Delete this job?')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 3: Manage Students -->
    <?php elseif ($activeTab === 'students'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">Registered Undergraduate Profiles</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Faculty & Course</th>
                            <th>Index / Reg No</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStudents as $st): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= BASE_URL ?><?= !empty($st['profile_image']) ? htmlspecialchars($st['profile_image']) : 'images/fl-3.png' ?>" class="rounded-circle" width="36" height="36" style="object-fit: cover;">
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($st['full_name']) ?></strong>
                                            <span class="text-muted small"><?= htmlspecialchars($st['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($st['course'] ?? '') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($st['faculty'] ?? '') ?></div>
                                </td>
                                <td><code><?= htmlspecialchars($st['reg_no'] ?? 'N/A') ?></code></td>
                                <td><span class="badge bg-light text-dark border"><?= $st['applications_count'] ?></span></td>
                                <td>
                                    <a href="?tab=students&action=toggle_student&id=<?= $st['id'] ?>" class="status-badge <?= $st['status'] === 'active' ? 'active' : 'rejected' ?> text-decoration-none">
                                        <?= ucfirst($st['status'] ?? 'active') ?>
                                    </a>
                                </td>
                                <td class="text-end">
                                    <a href="?tab=students&action=delete_student&id=<?= $st['id'] ?>" onclick="return confirm('Delete this student account?')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 4: Manage Employers -->
    <?php elseif ($activeTab === 'employers'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">Partner Employer Organizations</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>Industry</th>
                            <th>Location</th>
                            <th>Jobs Posted</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allEmployers as $emp): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= BASE_URL ?><?= !empty($emp['company_logo']) ? htmlspecialchars($emp['company_logo']) : 'images/google.png' ?>" class="rounded p-1 border" width="36" height="36" style="object-fit: contain;">
                                        <div>
                                            <strong class="d-block text-dark"><?= htmlspecialchars($emp['company_name']) ?></strong>
                                            <span class="text-muted small"><?= htmlspecialchars($emp['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($emp['industry'] ?? '') ?></td>
                                <td><small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($emp['location'] ?? '') ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= $emp['jobs_count'] ?></span></td>
                                <td>
                                    <a href="?tab=employers&action=toggle_employer&id=<?= $emp['id'] ?>" class="status-badge <?= ($emp['status'] ?? 'active') === 'active' ? 'active' : 'closed' ?> text-decoration-none">
                                        <?= ucfirst($emp['status'] ?? 'active') ?>
                                    </a>
                                </td>
                                <td class="text-end">
                                    <a href="?tab=employers&action=delete_employer&id=<?= $emp['id'] ?>" onclick="return confirm('Delete this employer account?')" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 5: Categories Manager -->
    <?php elseif ($activeTab === 'categories'): ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success me-2"></i>Add New Category</h5>
                    <form method="POST" action="index.php?tab=categories">
                        <input type="hidden" name="add_category" value="1">
                        <div class="mb-3">
                            <label for="cat_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="cat_name" name="cat_name" placeholder="e.g. Cloud Computing" required>
                        </div>
                        <div class="mb-3">
                            <label for="cat_icon" class="form-label">Bootstrap Icon Class</label>
                            <input type="text" class="form-control" id="cat_icon" name="cat_icon" value="bi-briefcase" placeholder="e.g. bi-cloud-arrow-up">
                            <span class="small text-muted">Use any <a href="https://icons.getbootstrap.com" target="_blank">Bootstrap icon</a> name.</span>
                        </div>
                        <button type="submit" class="btn btn-primary-ugpro py-2 rounded-pill">Create Category</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">Existing Categories</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Icon</th>
                                    <th>Category Name</th>
                                    <th>Slug</th>
                                    <th>Jobs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allCategories as $c): ?>
                                    <tr>
                                        <td><i class="bi <?= htmlspecialchars($c['icon']) ?> fs-5 text-success"></i></td>
                                        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
                                        <td><span class="badge bg-light text-dark border"><?= $c['jobs_count'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Tab 6: Contact Messages -->
    <?php elseif ($activeTab === 'messages'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h4 class="fw-bold mb-4">Contact Inquiries & Placement Queries</h4>
            <?php if (!empty($allMessages)): ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($allMessages as $msg): ?>
                        <div class="p-3 border rounded-3 <?= $msg['status'] === 'unread' ? 'bg-light border-success' : 'bg-white' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong class="text-dark"><?= htmlspecialchars($msg['name']) ?></strong>
                                    <span class="text-muted small">&lt;<?= htmlspecialchars($msg['email']) ?>&gt;</span>
                                    <?php if ($msg['status'] === 'unread'): ?>
                                        <span class="badge bg-danger rounded-pill ms-2">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small"><?= date('M d, Y - h:i A', strtotime($msg['created_at'])) ?></span>
                                    <?php if ($msg['status'] === 'unread'): ?>
                                        <a href="?tab=messages&action=mark_read&id=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill">Mark Read</a>
                                    <?php endif; ?>
                                    <a href="?tab=messages&action=delete_msg&id=<?= $msg['id'] ?>" onclick="return confirm('Delete message?')" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                            <h6 class="fw-bold text-success mb-1"><?= htmlspecialchars($msg['subject']) ?></h6>
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No contact messages yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
