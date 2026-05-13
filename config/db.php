<?php
require_once __DIR__ . '/env.php';

$host = $_ENV['DB_HOST'] ?? "localhost";
$user = $_ENV['DB_USER'] ?? "root";
$pass = $_ENV['DB_PASS'] ?? "";
$db   = $_ENV['DB_NAME'] ?? "cybershield_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>