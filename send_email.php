<?php
/**
 * Contact Submission Handler / Email Dispatcher - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = clean_input($_POST['subject'] ?? 'Website Inquiry');
    $message = clean_input($_POST['message'] ?? '');

    if (!empty($name) && $email && !empty($message)) {
        if (is_db_connected()) {
            try {
                $stmt = @$connect->prepare("INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
                if ($stmt) {
                    $stmt->bind_param("ssss", $name, $email, $subject, $message);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (Throwable $e) {
                // Continue
            }
        }

        // Try standard mail if configured
        $to = APP_EMAIL;
        $headers = "From: {$name} <{$email}>\r\nReply-To: {$email}\r\nContent-Type: text/plain; charset=utf-8\r\n";
        @mail($to, "UgPro Contact: " . $subject, $message, $headers);

        set_flash('success', "Thank you, {$name}! Your message has been sent successfully.");
        header("Location: " . BASE_URL . "contact.php");
        exit();
    } else {
        set_flash('danger', "Please complete all fields with a valid email.");
        header("Location: " . BASE_URL . "contact.php");
        exit();
    }
} else {
    header("Location: " . BASE_URL . "contact.php");
    exit();
}