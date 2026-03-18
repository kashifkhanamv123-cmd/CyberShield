-- ============================================================
-- SCHEMA UPDATE: ADD PROFILE FIELDS
-- ============================================================

USE cybershield_db;

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_type ENUM('none', 'preset', 'custom') DEFAULT 'none' AFTER gender,
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER profile_type;

-- Verify changes
DESCRIBE users;
