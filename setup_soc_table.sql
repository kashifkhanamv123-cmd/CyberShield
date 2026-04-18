DROP TABLE IF EXISTS soc_alerts;
CREATE TABLE IF NOT EXISTS soc_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    canonical_type VARCHAR(50) NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    source_ip VARCHAR(45) NOT NULL,
    description TEXT,
    log_evidence TEXT,
    phase_order INT DEFAULT 0,
    status ENUM('active', 'mitigated', 'dismissed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 'Operation Shadow Bridge' Scenario Data (Anonymized)
INSERT INTO soc_alerts (type, canonical_type, severity, source_ip, description, log_evidence, phase_order, status) VALUES 
('THREAT_SIG_0X1_RECON', 'Protocol Probe', 'Low', '203.0.113.45', '[PHASE 1] Initial signature anomaly detected in external gateway logs.', 'SYN_SCAN: 203.0.113.45 -> PORT [21,22,23,80,443,3306]\nFLAGS: [S]\nRESULT: 80/TCP OPEN, 443/TCP OPEN', 1, 'active'),
('THREAT_SIG_0X2_PROBE', 'Payload Delivery', 'Medium', '203.0.113.45', '[PHASE 2] Intelligence suggests an automated vulnerability scan attempt.', 'GET /wp-content/plugins/revslider/temp/update_extract/revslider/ps.php?0=system&1=id HTTP/1.1\nUser-Agent: Mozila/5.0 (Nikto/2.1.5)', 2, 'active'),
('THREAT_SIG_0X3_INJECT', 'Data Infiltration', 'High', '203.0.113.45', '[PHASE 3] Suspicious HTTP payload detected from identified hostile source.', 'POST /login.php HTTP/1.1\nuser=admin&pass='' OR 1=1--\nStatus: 302 Found\nSet-Cookie: PHPSESSID=session_admin_001', 3, 'active'),
('THREAT_SIG_0X4_ESCAL', 'System Takeover', 'Critical', 'Internal-WS-09', '[PHASE 4] Critical system event anomaly detected on production cluster.', 'SU-EXEC: user="apache" -> cmd="sudo -u root /usr/bin/find / -exec sh -i ;"\nRESULT: Elevated SH session established', 4, 'active'),
('THREAT_SIG_0X5_EXFIL', 'Exfiltration Trace', 'Critical', '203.0.113.45', '[PHASE 5] High-volume outbound data stream detected. Investigating impact.', 'DATA_DUMP: Local:/var/lib/mysql/orders.sql -> Remote:203.0.113.45:21\nSIZE: 1.4 GB\nPROTOCOL: FTP-SSL', 5, 'active');
