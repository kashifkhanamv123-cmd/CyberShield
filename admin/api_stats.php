<?php

/**
 * CyberShield Admin API - Real-time Stats
 * Returns system-wide statistics in JSON format.
 */
require_once __DIR__ . '/admin-auth.php';

header('Content-Type: application/json');

// ── Generic Stats ──────────────────────────────────────────────
// ── Generic Stats (Prepared Statements) ──────────────────────────
function get_api_count($conn, $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $res;
}

$stats = [
    'counts' => [
        'users'      => (int)get_api_count($conn, "SELECT COUNT(*) FROM users"),
        'phishing'   => (int)get_api_count($conn, "SELECT COUNT(*) FROM phishing_campaigns"),
        'bruteforce' => (int)get_api_count($conn, "SELECT COUNT(*) FROM bruteforce_logs"),
        'malware'    => (int)get_api_count($conn, "SELECT COUNT(*) FROM malware_samples"),
        'ddos'       => (int)get_api_count($conn, "SELECT COUNT(*) FROM ddos_simulations"),
        'blocked'    => (int)get_api_count($conn, "SELECT COUNT(*) FROM users WHERE status='blocked'"),
        'reports'    => (int)get_api_count($conn, "SELECT COUNT(*) FROM system_reports WHERE status='pending'"),
    ],
    'performance' => [
        'avg_ddos'     => round((float)get_api_count($conn, "SELECT AVG(duration_sec) FROM ddos_simulations"), 1),
        'total_emails' => (int)get_api_count($conn, "SELECT SUM(emails_sent) FROM phishing_campaigns"),
        'bf_wins'      => (int)get_api_count($conn, "SELECT SUM(success) FROM bruteforce_logs"),
    ]
];

// ── Weekly Registration Data ─────────────────────────────────────
$reg_labels = [];
$reg_counts = [];
$reg_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $reg_labels[] = date('D', strtotime($date));
    $reg_stmt->bind_param("s", $date);
    $reg_stmt->execute();
    $q = $reg_stmt->get_result();
    $reg_counts[] = ($q) ? (int)$q->fetch_row()[0] : 0;
}
$reg_stmt->close();
$stats['charts']['registrations'] = ['labels' => $reg_labels, 'data' => $reg_counts];

// ── Daily Activity Trend (Last 14 Days) ─────────────────────────
$trend_labels = [];
$trend_counts = [];
$trend_stmt = $conn->prepare("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = ?");
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = date('M d', strtotime($date));
    $trend_stmt->bind_param("s", $date);
    $trend_stmt->execute();
    $q = $trend_stmt->get_result();
    $trend_counts[] = ($q) ? (int)$q->fetch_row()[0] : 0;
}
$trend_stmt->close();
$stats['charts']['trends'] = ['labels' => $trend_labels, 'data' => $trend_counts];

// ── User Growth Monthly ─────────────────────────────────────────
$growth_labels = [];
$growth_counts = [];
// ── User Growth Monthly (Prepared Statement) ─────────────────────
$growth_labels = [];
$growth_counts = [];
$growth_stmt = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
");
$growth_stmt->execute();
$growthRes = $growth_stmt->get_result();
while ($row = $growthRes->fetch_assoc()) {
    $growth_labels[] = $row['month'];
    $growth_counts[] = (int)$row['count'];
}
$growth_stmt->close();
$stats['charts']['growth'] = ['labels' => $growth_labels, 'data' => $growth_counts];

// ── Recent Activity Logs ───────────────────────────────────────
$logs = [];
// ── Recent Activity Logs (Prepared Statement) ───────────────────
$recent_logs_stmt = $conn->prepare("
    SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.name
    FROM security_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.created_at DESC
    LIMIT 8
");
$recent_logs_stmt->execute();
$recent_logs_res = $recent_logs_stmt->get_result();
while ($row = $recent_logs_res->fetch_assoc()) {
    $row['time_ago'] = date('H:i:s', strtotime($row['created_at']));
    $logs[] = $row;
}
$recent_logs_stmt->close();
$stats['recent_logs'] = $logs;

echo json_encode($stats);
