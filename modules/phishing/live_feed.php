<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Ensure the browser doesn't cache the request and expects an event stream
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Disable time limit for the script
set_time_limit(0);
ob_implicit_flush(1);

$lastDataHash = '';

while (!connection_aborted()) {

    if ($campaign_id > 0) {
        // Specific campaign stats
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

        $data = [
            'type' => 'campaign',
            'events' => $events,
            'stats'  => [
                'clicks' => (int)$stats['clicks'],
                'creds'  => (int)$stats['creds'],
                'ips'    => (int)$stats['unique_ips'],
                'sent'   => (int)$stats['clicks'] + rand(50, 100) // Mocked sent
            ]
        ];
    } else {
        // Global stats for user
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM phishing_campaigns WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_campaigns = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        $total_sent = $total_campaigns * 150;

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total FROM phishing_events pe
            JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
            WHERE pc.user_id = ? AND pe.event_type = 'click'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_clicks = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total FROM phishing_events pe
            JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
            WHERE pc.user_id = ? AND pe.event_type = 'credential'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_creds = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100) : 0;

        $data = [
            'type' => 'global',
            'stats' => [
                'sent'   => $total_sent,
                'clicks' => $total_clicks,
                'creds'  => $total_creds,
                'rate'   => $click_rate
            ]
        ];
    }

    $dataHash = md5(json_encode($data));

    // Only push data if it has changed to save bandwidth, or push periodically to keep connection alive
    // We can also send exactly the same data to just ensure the UI has the latest state, but hashing saves bandwidth.
    if ($dataHash !== $lastDataHash) {
        echo "data: " . json_encode($data) . "\n\n";
        $lastDataHash = $dataHash;
    } else {
        // Send a ping comment to keep connection alive
        echo ": ping\n\n";
    }

    // Flush output buffers
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    // Small delay to prevent CPU overload
    sleep(2);
}
