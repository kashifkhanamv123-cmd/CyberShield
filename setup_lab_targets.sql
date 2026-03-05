CREATE TABLE IF NOT EXISTS lab_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Easy',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO lab_targets (username, password, difficulty) VALUES 
('admin', 'admin@123', 'Easy'),
('root', 'R00tSecure!', 'Medium'),
('analyst', 'C!berShield2026', 'Hard'),
('guest', 'guest', 'Easy'),
('operator', '123456', 'Easy'),
('developer', 'dev-pass-2024', 'Medium'),
('manager', 'password123', 'Easy'),
('support', 'support@shield', 'Medium'),
('auditor', 'Audit#2026!', 'Hard'),
('security_ops', 'OpsSecure!99', 'Hard');
