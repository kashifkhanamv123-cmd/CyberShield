<?php
require_once __DIR__ . "/../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 0;
    $email       = isset($_POST['email']) ? $_POST['email'] : 'UNKNOWN';
    $ip          = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $event_type  = 'credential';

    if ($campaign_id > 0) {
        $stmt = $conn->prepare("INSERT INTO phishing_events (campaign_id, event_type, target_email, attacker_ip) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $campaign_id, $event_type, $email, $ip);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to a realistic finishing page (e.g., real login or help page)
    // To make it look "broken" or "redirected", we can send them to a generic access denied or IT help page.
    // For this lab, let's just go to a fake "Security Training" page or redirect back to analytics if dev mode
    header("Location: analytics.php?id=" . $campaign_id . "&captured=1");
    exit();
} else {
    header("Location: index.php");
    exit();
}
