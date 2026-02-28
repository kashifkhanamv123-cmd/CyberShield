<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    exit();
}

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($campaign_id <= 0) {
    exit();
}

$stmt = $conn->prepare("
    SELECT event_type, attacker_ip, created_at
    FROM phishing_events
    WHERE campaign_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);