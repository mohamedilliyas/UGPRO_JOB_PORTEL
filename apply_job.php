<?php
/**
 * Job Application & Bookmark Handler - UgPro
 */
require_once __DIR__ . '/includes/auth.php';

// Handle Bookmark Toggle Action
if (isset($_GET['action']) && $_GET['action'] === 'bookmark') {
    require_student_auth();
    $studentId = $_SESSION['user_id'];
    $jobId = intval($_GET['job_id'] ?? 0);

    if ($jobId > 0) {
        // Check if already saved
        $check = $connect->prepare("SELECT id FROM saved_jobs WHERE undergraduate_id = ? AND job_id = ?");
        $check->bind_param("ii", $studentId, $jobId);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            // Remove bookmark
            $del = $connect->prepare("DELETE FROM saved_jobs WHERE undergraduate_id = ? AND job_id = ?");
            $del->bind_param("ii", $studentId, $jobId);
            $del->execute();
            $del->close();
            set_flash('info', 'Job removed from saved bookmarks.');
        } else {
            // Add bookmark
            $ins = $connect->prepare("INSERT INTO saved_jobs (undergraduate_id, job_id) VALUES (?, ?)");
            $ins->bind_param("ii", $studentId, $jobId);
            $ins->execute();
            $ins->close();
            set_flash('success', 'Job saved to your bookmarks!');
        }
        $check->close();
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'jobs.php');
    header("Location: " . $referer);
    exit();
}

// Handle Job Application POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_student_auth();
    $studentId = $_SESSION['user_id'];
    $jobId = intval($_POST['job_id'] ?? 0);
    $coverLetter = clean_input($_POST['cover_letter'] ?? '');

    if ($jobId <= 0) {
        set_flash('danger', 'Invalid job selection.');
        header("Location: " . BASE_URL . "jobs.php");
        exit();
    }

    // Check if user already applied
    $checkStmt = $connect->prepare("SELECT id FROM job_applications WHERE job_id = ? AND undergraduate_id = ?");
    $checkStmt->bind_param("ii", $jobId, $studentId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        set_flash('warning', 'You have already submitted an application for this position.');
        $checkStmt->close();
        header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
        exit();
    }
    $checkStmt->close();

    // Determine resume path: use newly uploaded resume or fall back to student's profile resume
    $resumePath = null;
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
        $upload = handle_file_upload($_FILES['resume_file'], RESUME_UPLOAD_DIR, ['pdf'], 10485760);
        if ($upload['success']) {
            $resumePath = $upload['filePath'];
        } else {
            set_flash('danger', "Resume upload error: " . $upload['error']);
            header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
            exit();
        }
    } else {
        // Fetch existing profile resume
        $stuStmt = $connect->prepare("SELECT resume_file FROM undergraduate WHERE id = ?");
        $stuStmt->bind_param("i", $studentId);
        $stuStmt->execute();
        $stuRow = $stuStmt->get_result()->fetch_assoc();
        $resumePath = $stuRow['resume_file'] ?? null;
        $stuStmt->close();
    }

    // Insert Application
    $appStmt = $connect->prepare("INSERT INTO job_applications (job_id, undergraduate_id, cover_letter, resume_path, status) VALUES (?, ?, ?, ?, 'pending')");
    $appStmt->bind_param("iiss", $jobId, $studentId, $coverLetter, $resumePath);

    if ($appStmt->execute()) {
        set_flash('success', '🎉 Application submitted successfully! The employer has been notified.');
        header("Location: " . BASE_URL . "profile_undergraduate.php?tab=applications");
        exit();
    } else {
        set_flash('danger', 'Failed to submit application: ' . $connect->error);
        header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
        exit();
    }
    $appStmt->close();
}

header("Location: " . BASE_URL . "jobs.php");
exit();
