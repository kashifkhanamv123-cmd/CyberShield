<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if (isset($_POST['launch'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $user_id = $_SESSION['user_id'];
    $sender_name  = $_POST['sender_name'];
    $spoof_email  = $_POST['spoof_email'];
    $subject      = $_POST['subject'];
    $body         = $_POST['body'];
    $landing_img  = $_POST['landing_image'] ?? '';

    // DEBUG LOG
    file_put_contents(__DIR__ . '/../../upload_debug.log', "--- " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/../../upload_debug.log', "POST landing_image: " . $landing_img . "\n", FILE_APPEND);
    if (!empty($_FILES)) {
        file_put_contents(__DIR__ . '/../../upload_debug.log', "FILES custom_landing: " . print_r($_FILES['custom_landing'] ?? 'EMPTY', true) . "\n", FILE_APPEND);
    }

    // Handle Custom Upload if provided
    if (isset($_FILES['custom_landing']) && $_FILES['custom_landing']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['custom_landing']['tmp_name'];
        $file_name = $_FILES['custom_landing']['name'];
        $file_size = $_FILES['custom_landing']['size'];

        // Robust MIME type check
        $file_type = '';
        if (function_exists('mime_content_type')) {
            $file_type = mime_content_type($file_tmp);
        } else {
            // Fallback for systems without fileinfo
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if ($ext === 'png') $file_type = 'image/png';
            if (in_array($ext, ['jpg', 'jpeg'])) $file_type = 'image/jpeg';
        }

        $allowed_types = ['image/png', 'image/jpeg'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            $upload_dir = __DIR__ . "/../../uploads/landing_pages/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Secure Rename: timestamp + random hash
            $ext_suffix = ($file_type === 'image/png') ? '.png' : '.jpg';
            $new_name = time() . "_" . bin2hex(random_bytes(8)) . $ext_suffix;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                // Store project-root relative path (starts with uploads/)
                $landing_img = "uploads/landing_pages/" . $new_name;
            }
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO phishing_campaigns (user_id, sender_name, spoof_email, subject, body, landing_image) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $user_id, $sender_name, $spoof_email, $subject, $body, $landing_img);
    $stmt->execute();

    $campaign_id = $stmt->insert_id;
    $stmt->close();

    // ── 2. Simulate realistic events (click + credential capture) ─────────
    $attacker_ip = $_SERVER['REMOTE_ADDR'];
    $sim_count = rand(10, 20);
    $domains = ['corp-mail.net', 'internal-security.info', 'employee-portal.co'];
    $names = ['sarah', 'john', 'mike', 'lisa', 'kevin', 'emma', 'david', 'ryan'];

    $click_stmt = $conn->prepare("INSERT INTO phishing_events (campaign_id, event_type, target_email, attacker_ip, created_at) VALUES (?, ?, ?, ?, ?)");

    for ($i = 0; $i < $sim_count; $i++) {
        $victim = $names[array_rand($names)] . "@" . $domains[array_rand($domains)];
        // Spread events over the last 2 minutes for a "live" feel
        $timestamp = date('Y-m-d H:i:s', strtotime("-" . rand(5, 120) . " seconds"));

        $etype = 'click';
        $click_stmt->bind_param("issss", $campaign_id, $etype, $victim, $attacker_ip, $timestamp);
        $click_stmt->execute();

        if (rand(1, 100) <= 50) {
            $etype = 'credential';
            $timestamp_cred = date('Y-m-d H:i:s', strtotime($timestamp . " + " . rand(2, 10) . " seconds"));
            $click_stmt->bind_param("issss", $campaign_id, $etype, $victim, $attacker_ip, $timestamp_cred);
            $click_stmt->execute();
        }
    }
    $click_stmt->close();

    header("Location: index.php?success=1&campaign_id=$campaign_id");
    exit();
}
