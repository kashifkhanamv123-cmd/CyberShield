<?php
require_once __DIR__ . '/../config/db.php';

echo "<h1>Database Connection Diagnostic</h1>";
echo "<b>Database Name:</b> " . $db . "<br>";
echo "<b>Connection Status:</b> Success!<br><br>";

echo "<h3>Available Tables in '$db':</h3>";
$stmt = $conn->prepare("SHOW TABLES");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'><b>NO TABLES FOUND!</b></p>";
}
$stmt->close();

echo "<hr>";
echo "<h3>Checking for specific required tables:</h3>";
$required = ['users', 'phishing_campaigns', 'bruteforce_logs', 'malware_samples', 'ddos_simulations', 'security_logs'];

echo "<ul>";
foreach ($required as $table) {
    $check_stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $check_stmt->bind_param("s", $table);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    if ($check->num_rows > 0) {
        echo "<li style='color:green;'>[OK] $table exists.</li>";
    } else {
        echo "<li style='color:red;'>[MISSING] $table DOES NOT EXIST!</li>";
    }
    $check_stmt->close();
}
echo "</ul>";
