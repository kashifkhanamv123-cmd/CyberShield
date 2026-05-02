<?php
require_once __DIR__ . '/../config/db.php';

$tables = ['users', 'phishing_campaigns', 'phishing_events', 'bruteforce_logs', 'ddos_logs', 'malware_logs', 'soc_alerts', 'user_queries'];

foreach ($tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    echo "Table '$table': " . ($res->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
}
?>
