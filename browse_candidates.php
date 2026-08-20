<?php
/**
 * Talent Pool & Candidate Directory - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

$search = clean_input($_GET['q'] ?? '');
$faculty = clean_input($_GET['faculty'] ?? '');

$query = "SELECT * FROM undergraduate WHERE status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR skills LIKE ? OR course LIKE ? OR projects LIKE ?)";
    $term = "%" . $search . "%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "ssss";
}

if (!empty($faculty)) {
    $query .= " AND faculty = ?";
    $params[] = $faculty;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = $connect->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = "Undergraduate Talent Pool - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-header-banner">
    <div class="obj-width text-center">
        <h1 class="h2 text-white fw-bold mb-2">Explore University Talent Pool</h1>
        <p class="text-white-50 mb-4">Discover bright, driven undergraduates and fresh graduates across various disciplines</p>
        
        <!-- Search Bar -->
        <div class="mx-auto" style="max-width: 650px;">
            <form method="GET" action="browse_candidates.php" class="bg-white p-2 rounded-pill shadow-lg d-flex align-items-center">
                <i class="bi bi-search text-muted ms-3 fs-5"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none px-3" placeholder="Search by name, skill (e.g. React, Python), or course..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary-ugpro rounded-pill px-4" style="width: auto;">Search Talent</button>
            </form>
        </div>
    </div>
</div>

<div class="obj-width my-5">
    <!-- Faculty Filter Pills -->
    <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
        <a href="browse_candidates.php<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="cat-pill <?= empty($faculty) ? 'active' : '' ?>">All Faculties</a>
        <a href="browse_candidates.php?faculty=<?= urlencode('Faculty of Applied Science') ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="cat-pill <?= $faculty === 'Faculty of Applied Science' ? 'active' : '' ?>">Applied Science</a>
        <a href="browse_candidates.php?faculty=<?= urlencode('Faculty of Business Studies') ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="cat-pill <?= $faculty === 'Faculty of Business Studies' ? 'active' : '' ?>">Business Studies</a>
        <a href="browse_candidates.php?faculty=<?= urlencode('Faculty of Technological Studies') ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="cat-pill <?= $faculty === 'Faculty of Technological Studies' ? 'active' : '' ?>">Technological Studies</a>
    </div>

    <!-- Candidate Cards Grid -->
    <?php if (!empty($candidates)): ?>
        <div class="row g-4">
            <?php foreach ($candidates as $cand): 
                $candSkills = array_filter(array_map('trim', explode(',', $cand['skills'] ?? '')));
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 d-flex flex-column justify-content-between hover-card">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= BASE_URL ?><?= !empty($cand['profile_image']) ? htmlspecialchars($cand['profile_image']) : 'images/fl-3.png' ?>" class="rounded-circle shadow-sm" width="65" height="65" style="object-fit: cover; border: 2px solid var(--secondary);" alt="Avatar">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cand['full_name']) ?></h5>
                                    <span class="text-muted small d-block"><?= htmlspecialchars($cand['course']) ?></span>
                                    <span class="text-muted small"><?= htmlspecialchars($cand['faculty'] ?? '') ?></span>
                                </div>
                            </div>

                            <?php if (!empty($cand['bio'])): ?>
                                <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($cand['bio']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Skills -->
                            <?php if (!empty($candSkills)): ?>
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    <?php foreach (array_slice($candSkills, 0, 5) as $sk): ?>
                                        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small"><?= htmlspecialchars($sk) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($candSkills) > 5): ?>
                                        <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small">+<?= count($candSkills) - 5 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <div class="d-flex gap-2">
                                <?php if (!empty($cand['github'])): ?>
                                    <a href="<?= htmlspecialchars($cand['github']) ?>" target="_blank" class="text-dark fs-5" title="GitHub"><i class="bi bi-github"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($cand['linkedin'])): ?>
                                    <a href="<?= htmlspecialchars($cand['linkedin']) ?>" target="_blank" class="text-primary fs-5" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($cand['portfolio_url'])): ?>
                                    <a href="<?= htmlspecialchars($cand['portfolio_url']) ?>" target="_blank" class="text-success fs-5" title="Portfolio"><i class="bi bi-globe"></i></a>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($cand['resume_file'])): ?>
                                <a href="<?= BASE_URL ?><?= htmlspecialchars($cand['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-pdf me-1"></i> CV</a>
                            <?php else: ?>
                                <a href="mailto:<?= htmlspecialchars($cand['email']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-envelope me-1"></i> Contact</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-person-x text-muted fs-1 mb-3"></i>
            <h5>No candidates found</h5>
            <p class="text-muted small">Try searching with different keywords or clearing faculty filters.</p>
            <a href="browse_candidates.php" class="btn btn-primary-ugpro rounded-pill btn-sm px-4 mx-auto" style="width: auto;">View All Talent</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
