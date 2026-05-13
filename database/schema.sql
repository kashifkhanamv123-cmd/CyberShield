-- ============================================================
-- CyberShield - CONSOLIDATED DATABASE SCHEMA
-- This file contains the complete structure for the CyberShield platform.
-- ============================================================

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    country VARCHAR(100) DEFAULT NULL,
    gender VARCHAR(50) DEFAULT NULL,
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    profile_type ENUM('none', 'preset', 'custom') DEFAULT 'none',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. SYSTEM SETTINGS TABLE
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Initial Settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES 
('registration_enabled', '1', 'Enable or disable new user registration'),
('maintenance_mode', '0', 'Put the site in maintenance mode'),
('site_name', 'CyberShield', 'The name of the platform');

-- 3. PHISHING_CAMPAIGNS TABLE
CREATE TABLE IF NOT EXISTS phishing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_name VARCHAR(255) DEFAULT NULL,
    spoof_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    landing_image VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'active', 'completed') DEFAULT 'active',
    emails_sent INT DEFAULT 0,
    emails_opened INT DEFAULT 0,
    links_clicked INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. PHISHING_EVENTS TABLE
CREATE TABLE IF NOT EXISTS phishing_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    event_type ENUM('click', 'credential', 'open') NOT NULL,
    target_email VARCHAR(255) DEFAULT NULL,
    attacker_ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES phishing_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. BRUTEFORCE_LOGS TABLE
CREATE TABLE IF NOT EXISTS bruteforce_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_system VARCHAR(255) DEFAULT 'SSH_SERVER',
    username_tried VARCHAR(255) DEFAULT NULL,
    attempts INT DEFAULT 0,
    success TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. DDOS_LOGS TABLE
CREATE TABLE IF NOT EXISTS ddos_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attack_type VARCHAR(50) DEFAULT 'SYN Flood',
    intensity VARCHAR(20) DEFAULT 'Medium',
    mitigated TINYINT(1) DEFAULT 0,
    time_taken FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. MALWARE_LOGS TABLE
CREATE TABLE IF NOT EXISTS malware_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sample_type VARCHAR(50) DEFAULT 'Ransomware',
    verdict VARCHAR(20) DEFAULT 'Malware',
    correct TINYINT(1) DEFAULT 0,
    time_taken FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. SOC_ALERTS TABLE
CREATE TABLE IF NOT EXISTS soc_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    canonical_type VARCHAR(50) NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    source_ip VARCHAR(45) NOT NULL,
    description TEXT,
    log_evidence TEXT,
    phase_order INT DEFAULT 0,
    status ENUM('active', 'mitigated', 'dismissed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. USER_QUERIES TABLE
CREATE TABLE IF NOT EXISTS user_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query_text TEXT NOT NULL,
    solution_text TEXT DEFAULT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. SECURITY_LOGS
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. SYSTEM_REPORTS
CREATE TABLE IF NOT EXISTS system_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    ip_address VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 12. LAB_TARGETS
CREATE TABLE IF NOT EXISTS lab_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Easy',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Initial Lab Targets
INSERT IGNORE INTO lab_targets (username, password, difficulty) VALUES 
('admin', 'admin@123', 'Easy'),
('root', 'R00tSecure!', 'Medium'),
('analyst', 'C!berShield2026', 'Hard'),
('guest', 'guest', 'Easy'),
('operator', '123456', 'Easy');
