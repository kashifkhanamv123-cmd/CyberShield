<?php
require_once __DIR__ . "/../../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid tracking ID.");
}

$campaign_id = (int) $_GET['id'];
$ip = $_SERVER['REMOTE_ADDR'];

$stmt = $conn->prepare("INSERT INTO phishing_events 
    (campaign_id, event_type, target_email, attacker_ip) 
    VALUES (?, 'click', 'employee@test.com', ?)");

$stmt->bind_param("is", $campaign_id, $ip);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html class="dark">
<head>
<title>Simulation Notice</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white flex items-center justify-center h-screen">

<div class="bg-gray-900 p-10 rounded-xl text-center border border-green-500">
<h1 class="text-2xl font-bold text-green-400 mb-4">
⚠️ Phishing Simulation Completed
</h1>

<p class="text-gray-300 mb-6">
This was part of a cybersecurity awareness training program.
Your interaction has been logged securely.
</p>

<a href="analytics.php?id=<?php echo $campaign_id; ?>"
class="bg-green-400 text-black px-6 py-3 rounded-lg font-bold">
View Intelligence Dashboard
</a>

</div>

</body>
</html>