-- ============================================================
-- ANALYTICS FIX: ADD MISSING PHISHING COLUMNS
-- Run this in your MySQL terminal or phpMyAdmin
-- ============================================================

USE cybershield_db;

-- Add the missing metric columns to phishing_campaigns
ALTER TABLE phishing_campaigns 
    ADD COLUMN emails_sent INT DEFAULT 0,
    ADD COLUMN emails_opened INT DEFAULT 0,
    ADD COLUMN links_clicked INT DEFAULT 0;

-- Verification
DESCRIBE phishing_campaigns;
