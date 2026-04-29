<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query_text = trim($_POST['query_text'] ?? '');

    if (empty($query_text)) {
        echo json_encode(['success' => false, 'message' => 'Query cannot be empty.']);
        exit();
    }

    if (strlen($query_text) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Query too long (max 2000 characters).']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO user_queries (user_id, query_text) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $query_text);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Query transmitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch user's query history
    $stmt = $conn->prepare("SELECT id, query_text, solution_text, status, created_at, updated_at FROM user_queries WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $queries = [];
    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'queries' => $queries]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
