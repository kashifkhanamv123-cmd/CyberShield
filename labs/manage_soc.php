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
    $stmt = $conn->prepare("SELECT canonical_type FROM soc_alerts WHERE id = ?");
    $stmt->bind_param("i", $alert_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $correct_type = $res->fetch_row()[0];
        if ($answer === $correct_type) {
            $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'mitigated' WHERE id = ?");
            $stmt->bind_param("i", $alert_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Phase complete. Intelligence gathered for next phase.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'False identification. Re-analyze the forensics data.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Alert not found']);
    }
    $stmt->close();
} elseif ($action === 'get_alerts') {
    // Phase logic: Find the current phase
    $phase_res = $conn->query("SELECT MIN(phase_order) FROM soc_alerts WHERE status = 'active' AND phase_order > 0");
    $current_phase = $phase_res->fetch_row()[0] ?? 0;
    
    if ($current_phase > 0) {
        $stmt = $conn->prepare("SELECT * FROM soc_alerts WHERE phase_order = ? AND status = 'active'");
        $stmt->bind_param("i", $current_phase);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // No active phased alerts, maybe some non-phased ones
        $result = $conn->query("SELECT * FROM soc_alerts WHERE status = 'active' AND phase_order = 0 ORDER BY created_at DESC");
    }
    
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    // Also send progress info
    $total_res = $conn->query("SELECT COUNT(*) FROM soc_alerts WHERE phase_order > 0");
    $total_phases = $total_res->fetch_row()[0];
    $completed_res = $conn->query("SELECT COUNT(*) FROM soc_alerts WHERE phase_order > 0 AND status = 'mitigated'");
    $completed_phases = $completed_res->fetch_row()[0];

    header("Content-Type: application/json");
    echo json_encode([
        'success' => true, 
        'alerts' => $alerts, 
        'progress' => [
            'current' => (int)$completed_phases + 1,
            'total' => (int)$total_phases,
            'is_complete' => ($completed_phases >= $total_phases)
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
