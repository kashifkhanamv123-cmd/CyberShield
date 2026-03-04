-- ============================================================
-- CyberShield - Admin Access & User Table Configuration
-- Use this script to set up roles and permissions
-- ============================================================

-- 1. Ensure columns exist (role, status, created_at)
-- This uses a safe way to add columns if they don't exist
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 2. Promote the specific administrator
UPDATE users 
SET role = 'admin', status = 'active'
WHERE email = 'kashifkhanamv123@gmail.com';

-- 3. Utility Queries for Management (Internal Reference)

-- PROMOTE a user to admin
-- UPDATE users SET role = 'admin' WHERE email = 'target@example.com';

-- DEMOTE admin back to user
-- UPDATE users SET role = 'user' WHERE email = 'target@example.com';

-- BLOCK a user account
-- UPDATE users SET status = 'blocked' WHERE email = 'target@example.com';

-- UNBLOCK a user account
-- UPDATE users SET status = 'active' WHERE email = 'target@example.com';

-- 4. Verification Queries
-- Check who is an admin
SELECT id, name, email, role, status FROM users WHERE role = 'admin';

-- Check for blocked users
SELECT id, name, email, role, status FROM users WHERE status = 'blocked';

-- Verify the specific admin
SELECT * FROM users WHERE email = 'kashifkhanamv123@gmail.com';
