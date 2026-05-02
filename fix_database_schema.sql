-- ============================================================
-- CyberShield - MASTER DATABASE SCHEMA FIX
-- This script ensures all tables and columns expected by the 
-- application are present and correctly structured.
-- ============================================================

USE cybershield_db;

-- 1. USERS TABLE
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS profile_type ENUM('none', 'preset', 'custom') DEFAULT 'none',
    ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL;

-- 2. PHISHING_CAMPAIGNS TABLE
CREATE TABLE IF NOT EXISTS phishing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_name VARCHAR(255) DEFAULT NULL,
    spoof_email VARCHAR(255) NOT NULL,
    sender_email VARCHAR(255) DEFAULT NULL, -- Backwards compatibility
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

-- Ensure missing columns exist if table was already created
ALTER TABLE phishing_campaigns
    ADD COLUMN IF NOT EXISTS sender_name VARCHAR(255) DEFAULT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS spoof_email VARCHAR(255) DEFAULT NULL AFTER sender_name,
    ADD COLUMN IF NOT EXISTS landing_image VARCHAR(255) DEFAULT NULL AFTER body,
    ADD COLUMN IF NOT EXISTS emails_sent INT DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS emails_opened INT DEFAULT 0 AFTER emails_sent,
    ADD COLUMN IF NOT EXISTS links_clicked INT DEFAULT 0 AFTER emails_opened;

-- 3. PHISHING_EVENTS TABLE
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

-- 4. BRUTEFORCE_LOGS TABLE
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

-- 5. DDOS_LOGS TABLE
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

-- 6. MALWARE_LOGS TABLE
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

-- 7. SOC_ALERTS TABLE
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

-- 8. USER_QUERIES TABLE (Help Desk)
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

-- 9. SECURITY_LOGS (Audit Log)
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
