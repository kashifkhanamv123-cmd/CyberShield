<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if (isset($_POST['launch'])) {

    $user_id = $_SESSION['user_id'];
    $sender_name  = $_POST['sender_name'];
    $spoof_email  = $_POST['spoof_email'];
    $subject      = $_POST['subject'];
    $body         = $_POST['body'];
    $landing_img  = $_POST['landing_image'] ?? '';

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
