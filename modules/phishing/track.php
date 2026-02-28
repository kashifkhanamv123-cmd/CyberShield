<?php
require_once __DIR__ . "/../../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid tracking ID.");
}

$campaign_id = (int) $_GET['id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Check if campaign exists first
$check = $conn->prepare("SELECT id FROM phishing_campaigns WHERE id = ?");
$check->bind_param("i", $campaign_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    die("Campaign not found.");
}
$check->close();

// Insert event safely
$stmt = $conn->prepare("INSERT INTO phishing_events 
    (campaign_id, event_type, target_email, attacker_ip) 
    VALUES (?, ?, ?, ?)");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$event_type = "click";
$email = "employee@test.com";

$stmt->bind_param("isss", $campaign_id, $event_type, $email, $ip);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$stmt->close();

// Redirect directly to analytics (REAL SOC flow)
header("Location: analytics.php?id=" . $campaign_id);
exit();