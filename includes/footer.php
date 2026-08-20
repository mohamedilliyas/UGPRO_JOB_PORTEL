<?php
/**
 * Global Footer Component - UgPro
 */
?>

<!-- Footer Section -->
<footer class="footer-modern">
    <div class="obj-width">
        <div class="footer-top row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <div class="d-flex align-items-center mb-3">
                        <img class="footer-logo me-2" src="<?= BASE_URL ?>images/logo.png" alt="UgPro Logo">
                        <span class="footer-brand-text">Ug<span>Pro</span></span>
                    </div>
                    <p class="footer-desc">University-powered job and internship portal connecting emerging undergraduates with leading industry employers and global career opportunities.</p>
                    <div class="footer-social-links">
                        <a href="https://linkedin.com" target="_blank" title="LinkedIn"><i class='bx bxl-linkedin'></i></a>
                        <a href="https://github.com" target="_blank" title="GitHub"><i class='bx bxl-github'></i></a>
                        <a href="https://twitter.com" target="_blank" title="Twitter"><i class='bx bxl-twitter'></i></a>
                        <a href="https://facebook.com" target="_blank" title="Facebook"><i class='bx bxl-facebook'></i></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h4 class="footer-heading">For Students</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>jobs.php">Browse All Jobs</a></li>
                    <li><a href="<?= BASE_URL ?>jobs.php?type=Internship">Internships</a></li>
                    <li><a href="<?= BASE_URL ?>jobs.php?workplace=Remote">Remote Jobs</a></li>
                    <li><a href="<?= BASE_URL ?>signin_undergraduate.php">Student Login</a></li>
                    <li><a href="<?= BASE_URL ?>signup_undergraduate.php">Create Profile</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-6">
                <h4 class="footer-heading">For Employers</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>signup_employer.php">Register Company</a></li>
                    <li><a href="<?= BASE_URL ?>employer_post_job.php">Post a Vacancy</a></li>
                    <li><a href="<?= BASE_URL ?>browse_candidates.php">Search Candidates</a></li>
                    <li><a href="<?= BASE_URL ?>signin_employer.php">Employer Sign In</a></li>
                    <li><a href="<?= BASE_URL ?>contact.php">Hiring Partnerships</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h4 class="footer-heading">University Support</h4>
                <p class="small text-white-50">University of Vavuniya Career Guidance Unit & Placement Cell.</p>
                <div class="contact-mini-info">
                    <p class="mb-1"><i class="bi bi-geo-alt me-2 text-success"></i>Pambaimadu, Vavuniya, Sri Lanka</p>
                    <p class="mb-1"><i class="bi bi-envelope me-2 text-success"></i>careers@vau.ac.lk</p>
                    <p class="mb-0"><i class="bi bi-telephone me-2 text-success"></i>+94 24 222 2265</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-white-50 small">&copy; <?= date('Y') ?> UgPro University Job Portal. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <a href="<?= BASE_URL ?>contact.php" class="text-white-50 small me-3 text-decoration-none">Support</a>
                    <a href="<?= BASE_URL ?>admin/login.php" class="text-white-50 small text-decoration-none"><i class="bi bi-shield-lock me-1"></i>Admin Portal</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Modal & Navigation Scripts -->
<script>
function showRegisterOptions() {
    const modal = document.getElementById('registerModal');
    if (modal) modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('registerModal');
    if (modal) modal.style.display = 'none';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('registerModal');
    if (event.target === modal) {
        closeModal();
    }
});

// Mobile menu toggle
const bar = document.getElementById("bar");
const menu = document.getElementById("menu");
if (bar && menu) {
    bar.addEventListener("click", () => {
        menu.classList.toggle("active");
    });
}
</script>

</body>
</html>
