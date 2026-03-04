-- ============================================================
-- EMERGENCY DATABASE FIX - RUN THIS IN MYSQL TERMINAL
-- ============================================================

-- 1. Switch to the correct database
USE cybershield_db;

-- 2. Create the missing tables
CREATE TABLE IF NOT EXISTS phishing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('draft','active','completed') DEFAULT 'draft',
    emails_sent INT DEFAULT 0,
    emails_opened INT DEFAULT 0,
    links_clicked INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bruteforce_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_system VARCHAR(255) NOT NULL,
    username_tried VARCHAR(255) NOT NULL,
    attempts INT DEFAULT 0,
    success TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS malware_samples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_hash_md5 VARCHAR(32),
    file_hash_sha256 VARCHAR(64),
    file_type VARCHAR(100),
    file_size BIGINT DEFAULT 0,
    analysis_result ENUM('clean','suspicious','malicious','pending') DEFAULT 'pending',
    analysis_notes TEXT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ddos_simulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_server VARCHAR(255) NOT NULL,
    attack_type VARCHAR(100) DEFAULT 'UDP Flood',
    duration_sec INT DEFAULT 0,
    requests_sent BIGINT DEFAULT 0,
    status ENUM('running','completed','failed','aborted') DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Verify
SHOW TABLES;
