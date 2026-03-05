ALTER TABLE bruteforce_logs
    CHANGE COLUMN target_system target_username VARCHAR(255) NOT NULL,
    CHANGE COLUMN username_tried attack_type VARCHAR(255) NOT NULL,
    ADD COLUMN time_taken FLOAT DEFAULT 0 AFTER success,
    DROP COLUMN ip_address;
