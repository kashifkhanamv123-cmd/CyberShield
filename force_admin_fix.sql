-- ============================================================
-- POWER FIX: FORCE ADMIN ASSIGNMENT
-- Run this in phpMyAdmin or your MySQL console
-- ============================================================

-- First, ensure the structure allows 'admin'
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- Second, force the update (using TRIM and LOWER for safety)
UPDATE users 
SET role = 'admin', status = 'active' 
WHERE LOWER(TRIM(email)) = 'kashifkhanamv123@gmail.com';

-- Third, verify immediately
SELECT id, name, email, role, status FROM users WHERE email = 'kashifkhanamv123@gmail.com';
