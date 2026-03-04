<?php

/**
 * CyberShield Admin Auth Guard
 * Include this at the TOP of every admin page.
 * Verifies the user is logged in AND has role = 'admin'.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Must have admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Log unauthorized access attempt
    $uid = intval($_SESSION['user_id']);
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $page = basename($_SERVER['PHP_SELF']);
    $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, description, ip_address) VALUES (?, 'admin_action', ?, ?)");
    $desc = "Unauthorized admin access attempt on $page";
    $stmt->bind_param("iss", $uid, $desc, $ip);
    $stmt->execute();
    $stmt->close();

    // Redirect to user dashboard
    header("Location: ../dashboard/dashboard.php");
    exit();
}

// Helper: Log an admin action
function logAdminAction(mysqli $conn, int $userId, string $eventType, string $description): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $eventType, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

// Store admin info
$adminId   = intval($_SESSION['user_id']);
$adminName = $_SESSION['user_name'] ?? 'Admin';
