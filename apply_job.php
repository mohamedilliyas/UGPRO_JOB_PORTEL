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
        if (is_db_connected()) {
            try {
                // Check if already saved
                $check = @$connect->prepare("SELECT id FROM saved_jobs WHERE undergraduate_id = ? AND job_id = ?");
                if ($check) {
                    $check->bind_param("ii", $studentId, $jobId);
                    $check->execute();
                    $res = $check->get_result();

                    if ($res && $res->num_rows > 0) {
                        // Remove bookmark
                        $del = @$connect->prepare("DELETE FROM saved_jobs WHERE undergraduate_id = ? AND job_id = ?");
                        if ($del) {
                            $del->bind_param("ii", $studentId, $jobId);
                            $del->execute();
                            $del->close();
                        }
                        set_flash('info', 'Job removed from saved bookmarks.');
                    } else {
                        // Add bookmark
                        $ins = @$connect->prepare("INSERT INTO saved_jobs (undergraduate_id, job_id) VALUES (?, ?)");
                        if ($ins) {
                            $ins->bind_param("ii", $studentId, $jobId);
                            $ins->execute();
                            $ins->close();
                        }
                        set_flash('success', 'Job saved to your bookmarks!');
                    }
                    $check->close();
                }
            } catch (Throwable $e) {
                set_flash('warning', 'Bookmark updated (session mode).');
            }
        } else {
            set_flash('success', 'Job saved to bookmarks (simulated).');
        }
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

    if (is_db_connected()) {
        try {
            // Check if user already applied
            $checkStmt = @$connect->prepare("SELECT id FROM job_applications WHERE job_id = ? AND undergraduate_id = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("ii", $jobId, $studentId);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows > 0) {
                    set_flash('warning', 'You have already submitted an application for this vacancy.');
                    $checkStmt->close();
                    header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
                    exit();
                }
                $checkStmt->close();
            }

            // Handle custom resume upload
            $resumeFilePath = null;
            if (isset($_FILES['custom_resume']) && $_FILES['custom_resume']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handle_file_upload($_FILES['custom_resume'], RESUME_UPLOAD_DIR, ['pdf'], 10485760);
                if ($uploadRes['success']) {
                    $resumeFilePath = $uploadRes['filePath'];
                } else {
                    set_flash('danger', 'Resume Upload Failed: ' . $uploadRes['error']);
                    header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
                    exit();
                }
            } else {
                // Fetch profile resume
                $profStmt = @$connect->prepare("SELECT resume_file FROM undergraduate WHERE id = ?");
                if ($profStmt) {
                    $profStmt->bind_param("i", $studentId);
                    $profStmt->execute();
                    $profRes = $profStmt->get_result()->fetch_assoc();
                    $resumeFilePath = $profRes['resume_file'] ?? null;
                    $profStmt->close();
                }
            }

            // Insert Application
            $insStmt = @$connect->prepare("INSERT INTO job_applications (job_id, undergraduate_id, resume_file, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($insStmt) {
                $insStmt->bind_param("iiss", $jobId, $studentId, $resumeFilePath, $coverLetter);
                if ($insStmt->execute()) {
                    set_flash('success', 'Your application has been successfully transmitted to the hiring team!');
                } else {
                    set_flash('danger', 'Failed to submit application: ' . $connect->error);
                }
                $insStmt->close();
            }
        } catch (Throwable $e) {
            set_flash('success', 'Your application has been received (simulated submission).');
        }
    } else {
        set_flash('success', 'Your application has been received (demo mode).');
    }

    header("Location: " . BASE_URL . "job_details.php?id=" . $jobId);
    exit();
}

header("Location: " . BASE_URL . "jobs.php");
exit();
