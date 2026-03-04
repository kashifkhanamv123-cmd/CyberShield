-- ============================================================
-- SQL SYNTAX FIX: Standard ALTER commands
-- =【Note】Your MySQL version doesn't support 'IF NOT EXISTS' for columns.
-- ============================================================

USE cybershield_db;

-- Run these one by one:

ALTER TABLE users ADD COLUMN status ENUM('active', 'blocked') NOT NULL DEFAULT 'active';

ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'admin';

ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Verification
DESCRIBE users;
