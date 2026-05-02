<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$alert_id = (int)($_GET['id'] ?? 0);

// --- SOC ALERTS INITIALIZATION ---
// Migration is now handled by the master fix script. 
// We just ensure alerts exist for the user.

function seedAlerts(mysqli $conn, int $userId) {
    try {
        $check = $conn->prepare("SELECT id FROM soc_alerts WHERE user_id = ? LIMIT 1");
        if (!$check) throw new Exception($conn->error);
        
        $check->bind_param("i", $userId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) return;

        $stmt = $conn->prepare("INSERT INTO soc_alerts (user_id, type, canonical_type, severity, source_ip, description, log_evidence, phase_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        if (!$stmt) throw new Exception($conn->error);

        $alerts = [
            ['THREAT_SIG_0X1_RECON', 'Protocol Probe', 'Low', '203.0.113.45', '[PHASE 1] Initial signature anomaly detected in external gateway logs.', "SYN_SCAN: 203.0.113.45 -> PORT [21,22,23,80,443,3306]\nFLAGS: [S]\nRESULT: 80/TCP OPEN, 443/TCP OPEN", 1],
            ['THREAT_SIG_0X2_PROBE', 'Payload Delivery', 'Medium', '203.0.113.45', '[PHASE 2] Intelligence suggests an automated vulnerability scan attempt.', "GET /wp-content/plugins/revslider/temp/update_extract/revslider/ps.php?0=system&1=id HTTP/1.1\nUser-Agent: Mozila/5.0 (Nikto/2.1.5)", 2],
            ['THREAT_SIG_0X3_INJECT', 'Data Infiltration', 'High', '203.0.113.45', '[PHASE 3] Suspicious HTTP payload detected from identified hostile source.', "POST /login.php HTTP/1.1\nuser=admin&pass='' OR 1=1--\nStatus: 302 Found\nSet-Cookie: PHPSESSID=session_admin_001", 3],
            ['THREAT_SIG_0X4_ESCAL', 'System Takeover', 'Critical', 'Internal-WS-09', '[PHASE 4] Critical system event anomaly detected on production cluster.', "SU-EXEC: user=\"apache\" -> cmd=\"sudo -u root /usr/bin/find / -exec sh -i ;\"\nRESULT: Elevated SH session established", 4],
            ['THREAT_SIG_0X5_EXFIL', 'Exfiltration Trace', 'Critical', '203.0.113.45', '[PHASE 5] High-volume outbound data stream detected. Investigating impact.', "DATA_DUMP: Local:/var/lib/mysql/orders.sql -> Remote:203.0.113.45:21\nSIZE: 1.4 GB\nPROTOCOL: FTP-SSL", 5]
        ];
        foreach ($alerts as $a) {
            $stmt->bind_param("issssssi", $userId, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Log error internally or handle if needed
        return $e->getMessage();
    }
    return null;
}

if ($action === 'restart') {
    $stmt = $conn->prepare("DELETE FROM soc_alerts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    seedAlerts($conn, $user_id);
    echo json_encode(['success' => true, 'message' => 'Operations reset successfully']);
    exit();
}

if ($action === 'mitigate' && $alert_id > 0) {
    $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'mitigated' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $alert_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Threat mitigated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mitigate threat']);
    }
    $stmt->close();
} elseif ($action === 'dismiss' && $alert_id > 0) {
    $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'dismissed' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $alert_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Alert dismissed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to dismiss alert']);
    }
    $stmt->close();
} elseif ($action === 'verify_investigation' && $alert_id > 0) {
    $answer = $_GET['answer'] ?? '';
    $stmt = $conn->prepare("SELECT canonical_type FROM soc_alerts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $alert_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $correct_type = $res->fetch_row()[0];
        if ($answer === $correct_type) {
            $stmt = $conn->prepare("UPDATE soc_alerts SET status = 'mitigated' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $alert_id, $user_id);
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
    seedAlerts($conn, $user_id);
    
    $alerts = [];
    $error = null;

    try {
        // Find current active phase
        $phase_stmt = $conn->prepare("SELECT MIN(phase_order) FROM soc_alerts WHERE status = 'active' AND phase_order > 0 AND user_id = ?");
        if (!$phase_stmt) throw new Exception($conn->error);
        
        $phase_stmt->bind_param("i", $user_id);
        $phase_stmt->execute();
        $current_phase = $phase_stmt->get_result()->fetch_row()[0] ?? 0;
        
        if ($current_phase > 0) {
            $stmt = $conn->prepare("SELECT * FROM soc_alerts WHERE phase_order = ? AND status = 'active' AND user_id = ?");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("ii", $current_phase, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $alerts[] = $row;
        } else {
            $stmt = $conn->prepare("SELECT * FROM soc_alerts WHERE user_id = ? ORDER BY phase_order ASC");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $alerts[] = $row;
        }
        
        $total_res = $conn->prepare("SELECT COUNT(*) FROM soc_alerts WHERE phase_order > 0 AND user_id = ?");
        $total_res->bind_param("i", $user_id);
        $total_res->execute();
        $total_phases = $total_res->get_result()->fetch_row()[0];

        $completed_res = $conn->prepare("SELECT COUNT(*) FROM soc_alerts WHERE phase_order > 0 AND status = 'mitigated' AND user_id = ?");
        $completed_res->bind_param("i", $user_id);
        $completed_res->execute();
        $completed_phases = $completed_res->get_result()->fetch_row()[0];

        $progress = [
            'current' => (int)$completed_phases + ($completed_phases < $total_phases ? 1 : 0),
            'total' => (int)$total_phases,
            'is_complete' => ($completed_phases >= $total_phases && $total_phases > 0)
        ];
    } catch (Exception $e) {
        $error = $e->getMessage();
        $progress = ['current' => 0, 'total' => 0, 'is_complete' => false];
    }

    header("Content-Type: application/json");
    echo json_encode([
        'success' => true, 
        'alerts' => $alerts, 
        'progress' => $progress,
        'debug_error' => $error
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
