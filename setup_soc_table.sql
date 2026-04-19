DROP TABLE IF EXISTS soc_alerts;
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
);

-- Template Seed (Handled in PHP for existing users)
-- INSERT INTO soc_alerts (user_id, ...) VALUES (...)
