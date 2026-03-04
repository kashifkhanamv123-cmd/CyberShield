-- ============================================================
-- SCHEMA FIX: ADD MISSING COLUMNS TO USERS TABLE
-- Run this in your MySQL terminal or phpMyAdmin
-- ============================================================

USE cybershield_db;

-- 1. Add 'status' column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'blocked') NOT NULL DEFAULT 'active';

-- 2. Add 'role' column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- 3. Add 'created_at' column if it doesn't exist (used for charts)
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. Final verification
DESCRIBE users;
