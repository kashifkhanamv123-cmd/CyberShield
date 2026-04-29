<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query_id     = (int)($_POST['query_id'] ?? 0);
    $solution     = trim($_POST['solution_text'] ?? '');

    if (!$query_id || empty($solution)) {
        echo json_encode(['success' => false, 'message' => 'Query ID and solution are required.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE user_queries SET solution_text = ?, status = 'resolved', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $solution, $query_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Solution transmitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update query.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
