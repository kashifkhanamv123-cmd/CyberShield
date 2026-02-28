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

// Fetch recent events
$stmt = $conn->prepare("
    SELECT event_type, attacker_ip, created_at
    FROM phishing_events
    WHERE campaign_id = ?
    ORDER BY created_at DESC
    LIMIT 15
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch live counts
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
        COUNT(CASE WHEN event_type = 'credential' THEN 1 END) as creds,
        COUNT(DISTINCT attacker_ip) as unique_ips
    FROM phishing_events
    WHERE campaign_id = ?
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'events' => $events,
    'stats'  => [
        'clicks' => (int)$stats['clicks'],
        'creds'  => (int)$stats['creds'],
        'ips'    => (int)$stats['unique_ips'],
        'sent'   => (int)$stats['clicks'] + rand(50, 100) // Mocked sent for now if not tracked
    ]
]);
