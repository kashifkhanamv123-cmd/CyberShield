<?php

/**
 * CyberShield Admin API - Real-time Stats
 * Returns system-wide statistics in JSON format.
 */
require_once __DIR__ . '/admin-auth.php';

header('Content-Type: application/json');

// ── Generic Stats ──────────────────────────────────────────────
$stats = [
    'counts' => [
        'users'    => ($q = $conn->query("SELECT COUNT(*) FROM users")) ? (int)$q->fetch_row()[0] : 0,
        'phishing' => ($q = $conn->query("SELECT COUNT(*) FROM phishing_campaigns")) ? (int)$q->fetch_row()[0] : 0,
        'bruteforce' => ($q = $conn->query("SELECT COUNT(*) FROM bruteforce_logs")) ? (int)$q->fetch_row()[0] : 0,
        'malware'  => ($q = $conn->query("SELECT COUNT(*) FROM malware_samples")) ? (int)$q->fetch_row()[0] : 0,
        'ddos'     => ($q = $conn->query("SELECT COUNT(*) FROM ddos_simulations")) ? (int)$q->fetch_row()[0] : 0,
        'blocked'  => ($q = $conn->query("SELECT COUNT(*) FROM users WHERE status='blocked'")) ? (int)$q->fetch_row()[0] : 0,
    ],
    'performance' => [
        'avg_ddos'     => ($q = $conn->query("SELECT AVG(duration_sec) FROM ddos_simulations")) ? round((float)$q->fetch_row()[0], 1) : 0,
        'total_emails' => ($q = $conn->query("SELECT SUM(emails_sent) FROM phishing_campaigns")) ? (int)$q->fetch_row()[0] : 0,
        'bf_wins'      => ($q = $conn->query("SELECT SUM(success) FROM bruteforce_logs")) ? (int)$q->fetch_row()[0] : 0,
    ]
];

// ── Weekly Registration Data ─────────────────────────────────────
$reg_labels = [];
$reg_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $reg_labels[] = date('D', strtotime($date));
    $q = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)='$date'");
    $reg_counts[] = ($q) ? (int)$q->fetch_row()[0] : 0;
}
$stats['charts']['registrations'] = ['labels' => $reg_labels, 'data' => $reg_counts];

// ── Daily Activity Trend (Last 14 Days) ─────────────────────────
$trend_labels = [];
$trend_counts = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = date('M d', strtotime($date));
    $q = $conn->query("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at)='$date'");
    $trend_counts[] = ($q) ? (int)$q->fetch_row()[0] : 0;
}
$stats['charts']['trends'] = ['labels' => $trend_labels, 'data' => $trend_counts];

// ── User Growth Monthly ─────────────────────────────────────────
$growth_labels = [];
$growth_counts = [];
$growthRes = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
");
while ($row = $growthRes->fetch_assoc()) {
    $growth_labels[] = $row['month'];
    $growth_counts[] = (int)$row['count'];
}
$stats['charts']['growth'] = ['labels' => $growth_labels, 'data' => $growth_counts];

// ── Recent Activity Logs ───────────────────────────────────────
$logs = [];
$recent_logs_res = $conn->query("
    SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.name
    FROM security_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.created_at DESC
    LIMIT 8
");
while ($row = $recent_logs_res->fetch_assoc()) {
    $row['time_ago'] = date('H:i:s', strtotime($row['created_at']));
    $logs[] = $row;
}
$stats['recent_logs'] = $logs;

echo json_encode($stats);
