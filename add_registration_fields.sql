-- ============================================================
-- SCHEMA UPDATE: ADD REGISTRATION FIELDS
-- ============================================================

USE cybershield_db;

ALTER TABLE users 
ADD COLUMN country VARCHAR(100) AFTER email,
ADD COLUMN organization VARCHAR(255) AFTER country,
ADD COLUMN program_level ENUM('Middle School', 'High School', 'Undergraduate', 'Graduate', 'Other') AFTER organization,
ADD COLUMN gender VARCHAR(50) AFTER program_level;

-- Verify changes
DESCRIBE users;
