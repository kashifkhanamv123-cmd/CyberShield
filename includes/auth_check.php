<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

// Global Maintenance Check
$m_res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
$m_mode = ($m_res && $m_res->num_rows > 0) ? $m_res->fetch_assoc()['setting_value'] : '0';

if ($m_mode === '1' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header("Location: ../maintenance.php");
    exit();
}
?>