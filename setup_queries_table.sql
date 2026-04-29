-- ============================================================
-- CyberShield - Help Desk Query System Setup
-- ============================================================

USE cybershield_db;

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
