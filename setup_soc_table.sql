DROP TABLE IF EXISTS soc_alerts;
CREATE TABLE IF NOT EXISTS soc_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    source_ip VARCHAR(45) NOT NULL,
    description TEXT,
    log_evidence TEXT,
    status ENUM('active', 'mitigated', 'dismissed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample Data for the SOC Experience
INSERT INTO soc_alerts (type, severity, source_ip, description, log_evidence, status) VALUES 
('SQL Injection', 'Critical', '192.168.1.105', 'Malicious SQL pattern detected in /api/users endpoint.', 'GET /api/users?id=1%20OR%201=1-- HTTP/1.1\nHost: internal-srv\nUser-Agent: sqlmap/1.4.12', 'active'),
('Brute Force', 'High', '10.0.0.52', 'Multiple failed login attempts detected for user "admin".', 'POST /auth/login HTTP/1.1\nUser: admin\nPass: 123456\nResult: 401 Unauthorized\n(Repeated 50 times in 10s)', 'active'),
('DDoS Attempt', 'Medium', '172.16.0.4', 'High volume of SYN packets from a single source.', 'TCP SYN Packet received: Seq=12345678\nTCP SYN Packet received: Seq=87654321\n... (5000 packets/sec)', 'active'),
('Unauthorized Access', 'Critical', '192.168.1.200', 'Attempted access to /root/secrets directory.', 'SSH Attempt: Login successful for user "guest"\nCommand: ls -la /root/secrets\nResult: Permission Denied', 'active'),
('Malware Beacon', 'High', '8.8.4.4', 'Observed communication with known C2 server.', 'POST /api/v1/beacon HTTP/1.1\nHost: malicious-c2.net\nPayload: 0x414141418923...', 'active');
