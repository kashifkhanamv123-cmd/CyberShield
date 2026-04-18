<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$alert_id = (int)($_GET['id'] ?? 0);

if ($action === 'mitigate' && $alert_id > 0) {
    $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'mitigated' WHERE id = ?");
    $stmt->bind_param("i", $alert_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Threat mitigated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mitigate threat']);
    }
    $stmt->close();
} elseif ($action === 'dismiss' && $alert_id > 0) {
    $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'dismissed' WHERE id = ?");
    $stmt->bind_param("i", $alert_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Alert dismissed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to dismiss alert']);
    }
    $stmt->close();
} elseif ($action === 'verify_investigation' && $alert_id > 0) {
    $answer = $_GET['answer'] ?? '';
    
    // Fetch the correct type
    $stmt = $conn->prepare("SELECT type FROM soc_alerts WHERE id = ?");
    $stmt->bind_param("i", $alert_id);
    $stmt->execute();
    $correct_type = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    
    if ($answer === $correct_type) {
        $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'mitigated' WHERE id = ?");
        $stmt->bind_param("i", $alert_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Investigation successful. Threat mitigated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect identification. Re-analyze the logs.']);
    }
} elseif ($action === 'get_alerts') {
    $result = $conn->query("SELECT * FROM soc_alerts WHERE status = 'active' ORDER BY created_at DESC");
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    header("Content-Type: application/json");
    echo json_encode(['success' => true, 'alerts' => $alerts]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
