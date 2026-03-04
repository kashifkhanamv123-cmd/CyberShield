-- ============================================================
-- CyberShield Admin Panel - Database Setup
-- Run this file in your MySQL/phpMyAdmin
-- ============================================================

-- Make sure we're using the right database
USE cybershield_db;

-- ============================================================
-- Update USERS table to include status column (if missing)
-- ============================================================
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status ENUM('active','blocked') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS role ENUM('user','admin') NOT NULL DEFAULT 'user';

-- ============================================================
-- PHISHING CAMPAIGNS TABLE (admin can create + users see)
-- ============================================================
CREATE TABLE IF NOT EXISTS phishing_campaigns (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    body         TEXT NOT NULL,
    status       ENUM('draft','active','completed') DEFAULT 'draft',
    emails_sent  INT DEFAULT 0,
    emails_opened INT DEFAULT 0,
    links_clicked INT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- BRUTE FORCE LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS bruteforce_logs (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    target_system    VARCHAR(255) NOT NULL,
    username_tried   VARCHAR(255) NOT NULL,
    attempts         INT DEFAULT 0,
    success          TINYINT(1) DEFAULT 0,
    ip_address       VARCHAR(45),
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MALWARE SAMPLES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS malware_samples (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_hash_md5   VARCHAR(32),
    file_hash_sha256 VARCHAR(64),
    file_type       VARCHAR(100),
    file_size       BIGINT DEFAULT 0,
    analysis_result ENUM('clean','suspicious','malicious','pending') DEFAULT 'pending',
    analysis_notes  TEXT,
    upload_date     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DDOS SIMULATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS ddos_simulations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    target_server   VARCHAR(255) NOT NULL,
    attack_type     ENUM('UDP Flood','TCP SYN','HTTP Flood','ICMP Ping Flood','Amplification') DEFAULT 'UDP Flood',
    duration_sec    INT DEFAULT 0,
    requests_sent   BIGINT DEFAULT 0,
    status          ENUM('running','completed','failed','aborted') DEFAULT 'completed',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SECURITY AUDIT LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS security_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    event_type  ENUM(
        'login_success',
        'login_failed',
        'logout',
        'register',
        'password_reset',
        'password_change',
        'phishing_lab',
        'bruteforce_lab',
        'malware_lab',
        'ddos_lab',
        'admin_action',
        'user_blocked',
        'user_deleted',
        'role_changed'
    ) NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA - Demo records so the admin panel has content
-- ============================================================

-- Insert sample brute force logs
INSERT IGNORE INTO bruteforce_logs (user_id, target_system, username_tried, attempts, success, ip_address, created_at)
SELECT id, 'SSH Server (192.168.1.1)', 'admin', 254, 0, '10.0.0.5', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO bruteforce_logs (user_id, target_system, username_tried, attempts, success, ip_address, created_at)
SELECT id, 'FTP Server (192.168.1.50)', 'root', 112, 1, '10.0.0.8', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO bruteforce_logs (user_id, target_system, username_tried, attempts, success, ip_address, created_at)
SELECT id, 'Web Admin Panel', 'administrator', 78, 0, '10.0.0.12', NOW()
FROM users LIMIT 1;

-- Insert sample malware samples
INSERT IGNORE INTO malware_samples (user_id, file_name, file_hash_md5, file_hash_sha256, file_type, file_size, analysis_result, analysis_notes, upload_date)
SELECT id, 'backdoor.exe', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', 'sha256hashexample001sha256hashexample001sha256hashexample001example01', 'application/x-executable', 204800, 'malicious', 'Trojan backdoor detected. C2 callbacks to 185.220.101.x', DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO malware_samples (user_id, file_name, file_hash_md5, file_hash_sha256, file_type, file_size, analysis_result, analysis_notes, upload_date)
SELECT id, 'document.pdf.js', 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5', 'sha256hashexample002sha256hashexample002sha256hashexample002example02', 'application/javascript', 8192, 'suspicious', 'Obfuscated JS payload embedded in PDF dropper', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO malware_samples (user_id, file_name, file_hash_md5, file_hash_sha256, file_type, file_size, analysis_result, analysis_notes, upload_date)
SELECT id, 'update_patch.zip', 'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6', 'sha256hashexample003sha256hashexample003sha256hashexample003example03', 'application/zip', 512000, 'clean', 'No threats detected', NOW()
FROM users LIMIT 1;

-- Insert sample DDoS simulations
INSERT IGNORE INTO ddos_simulations (user_id, target_server, attack_type, duration_sec, requests_sent, status, created_at)
SELECT id, '192.168.10.5:80', 'HTTP Flood', 120, 458200, 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO ddos_simulations (user_id, target_server, attack_type, duration_sec, requests_sent, status, created_at)
SELECT id, '10.0.0.1:443', 'TCP SYN', 60, 120500, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users LIMIT 1;

INSERT IGNORE INTO ddos_simulations (user_id, target_server, attack_type, duration_sec, requests_sent, status, created_at)
SELECT id, '172.16.0.10:53', 'UDP Flood', 300, 2100000, 'aborted', NOW()
FROM users LIMIT 1;

-- Insert sample security logs
INSERT IGNORE INTO security_logs (user_id, event_type, description, ip_address, created_at)
SELECT id, 'login_success', CONCAT('Successful login for user: ', name), '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 HOUR) FROM users LIMIT 1;

INSERT IGNORE INTO security_logs (user_id, event_type, description, ip_address, created_at)
SELECT id, 'login_failed', 'Failed login attempt - wrong password', '192.168.1.45', DATE_SUB(NOW(), INTERVAL 2 HOUR) FROM users LIMIT 1;

INSERT IGNORE INTO security_logs (user_id, event_type, description, ip_address, created_at)
SELECT id, 'phishing_lab', 'Phishing campaign created', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 3 HOUR) FROM users LIMIT 1;
