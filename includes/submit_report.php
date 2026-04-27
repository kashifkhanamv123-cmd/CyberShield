<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$subject = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

if (empty($subject) || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Subject and description are required.']);
    exit();
}

$allowed_priorities = ['low', 'medium', 'high', 'critical'];
if (!in_array($priority, $allowed_priorities)) {
    $priority = 'medium';
}

$stmt = $conn->prepare("INSERT INTO system_reports (user_id, subject, description, priority, ip_address) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $subject, $description, $priority, $ip_address);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit report. ' . $conn->error]);
}

$stmt->close();
$conn->close();
