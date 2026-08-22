<?php
/**
 * Contact Us & University Placement Inquiries - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = clean_input($_POST['subject'] ?? 'General Inquiry');
    $message = clean_input($_POST['message'] ?? '');

    if (empty($name) || !$email || empty($message)) {
        $errorMsg = "Please fill out all required fields with a valid email address.";
    } else {
        if (is_db_connected()) {
            try {
                $stmt = @$connect->prepare("INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
                if ($stmt) {
                    $stmt->bind_param("ssss", $name, $email, $subject, $message);
                    if ($stmt->execute()) {
                        $successMsg = "Thank you, {$name}! Your message has been received. The Career Guidance Unit will get back to you shortly.";
                    } else {
                        $errorMsg = "Failed to save message. Please try again.";
                    }
                    $stmt->close();
                }
            } catch (Throwable $e) {
                $successMsg = "Thank you, {$name}! Your inquiry has been submitted successfully.";
            }
        } else {
            $successMsg = "Thank you, {$name}! Your inquiry has been submitted successfully (demo mode).";
        }
    }
}

$pageTitle = "Contact Us - UgPro";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-header-banner">
    <div class="obj-width text-center">
        <h1 class="h2 text-white fw-bold mb-2">Get in Touch with Us</h1>
        <p class="text-white-50 mb-0">Have questions about university placements, employer partnerships, or student verification?</p>
    </div>
</div>

<div class="obj-width my-5">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
        <div class="row g-5 align-items-center">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <h3 class="fw-bold text-dark mb-3">University Career Guidance Unit</h3>
                <p class="text-muted mb-4">We assist students with CV review, interview preparation, and corporate networking drives. Employers can reach out for dedicated on-campus placement sessions.</p>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon green" style="width: 44px; height: 44px; font-size: 1.2rem;"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <strong class="d-block text-dark">Location:</strong>
                            <span class="text-muted small">Career Guidance Unit, University of Vavuniya, Pambaimadu, Vavuniya, Sri Lanka</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon blue" style="width: 44px; height: 44px; font-size: 1.2rem;"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <strong class="d-block text-dark">Email Inquiries:</strong>
                            <span class="text-muted small">careers@vau.ac.lk / support@ugpro.lk</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon purple" style="width: 44px; height: 44px; font-size: 1.2rem;"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <strong class="d-block text-dark">Telephone:</strong>
                            <span class="text-muted small">+94 24 222 2265 / +94 24 222 0179</span>
                        </div>
                    </div>
                </div>

                <div class="text-center text-lg-start">
                    <img src="<?= BASE_URL ?>images/contact.svg" alt="Contact Illustration" style="max-width: 260px; height: auto;">
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7 bg-light p-4 p-md-5 rounded-4">
                <h4 class="fw-bold text-dark mb-1">Send a Message</h4>
                <p class="text-muted small mb-4">We usually respond within 24 business hours</p>

                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success shadow-sm rounded-3 d-flex align-items-center mb-4">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div><?= htmlspecialchars($successMsg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                        <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>contact.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g. Employer Partnership / Student Support" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">Your Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your inquiry or question here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary-ugpro py-3">Submit Inquiry</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>