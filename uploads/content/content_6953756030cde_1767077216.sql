-- AI Content Moderation System Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.2+
-- This version is safe to run multiple times

-- Create moderation_logs table
CREATE TABLE IF NOT EXISTS moderation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL COMMENT 'listing, marketplace_listing, forum_post, forum_thread, profile, story',
    content_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'error') DEFAULT 'low',
    violations JSON,
    reason TEXT,
    confidence DECIMAL(3,2),
    suggested_action ENUM('approve', 'flag', 'reject') DEFAULT 'approve',
    reviewed_at DATETIME NULL,
    reviewed_by INT NULL,
    admin_action ENUM('approved', 'rejected', 'banned_user', 'no_action') NULL,
    admin_notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_content (content_type, content_id),
    INDEX idx_user (user_id),
    INDEX idx_risk (risk_level),
    INDEX idx_review_status (reviewed_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create moderation_settings table
CREATE TABLE IF NOT EXISTS moderation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO moderation_settings (setting_key, setting_value) VALUES
('auto_moderate_enabled', 'true'),
('auto_flag_high_risk', 'true'),
('auto_reject_threshold', '0.9'),
('moderate_listings', 'true'),
('moderate_marketplace', 'true'),
('moderate_forum', 'true'),
('moderate_profiles', 'true'),
('notify_admin_on_flag', 'true'),
('admin_email', 'admin@basehit.io')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Create banned_content table
CREATE TABLE IF NOT EXISTS banned_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pattern VARCHAR(255) NOT NULL,
    type ENUM('word', 'phrase', 'regex') DEFAULT 'word',
    severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add moderation columns to listings table (if not exists)
DELIMITER $$
CREATE PROCEDURE add_listings_moderation_columns()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'listings' 
                   AND COLUMN_NAME = 'moderation_status') THEN
        ALTER TABLE listings ADD COLUMN moderation_status ENUM('pending', 'approved', 'flagged', 'rejected') DEFAULT 'approved';
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'listings' 
                   AND COLUMN_NAME = 'moderation_checked_at') THEN
        ALTER TABLE listings ADD COLUMN moderation_checked_at DATETIME NULL;
    END IF;
END$$
DELIMITER ;
CALL add_listings_moderation_columns();
DROP PROCEDURE add_listings_moderation_columns;

-- Add moderation columns to creator_listings table (if not exists)
DELIMITER $$
CREATE PROCEDURE add_creator_listings_moderation_columns()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'creator_listings' 
                   AND COLUMN_NAME = 'moderation_status') THEN
        ALTER TABLE creator_listings ADD COLUMN moderation_status ENUM('pending', 'approved', 'flagged', 'rejected') DEFAULT 'approved';
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'creator_listings' 
                   AND COLUMN_NAME = 'moderation_checked_at') THEN
        ALTER TABLE creator_listings ADD COLUMN moderation_checked_at DATETIME NULL;
    END IF;
END$$
DELIMITER ;
CALL add_creator_listings_moderation_columns();
DROP PROCEDURE add_creator_listings_moderation_columns;

-- Add moderation columns to forum_threads table (if not exists)
DELIMITER $$
CREATE PROCEDURE add_forum_threads_moderation_columns()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'forum_threads' 
                   AND COLUMN_NAME = 'moderation_status') THEN
        ALTER TABLE forum_threads ADD COLUMN moderation_status ENUM('pending', 'approved', 'flagged', 'rejected') DEFAULT 'approved';
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'forum_threads' 
                   AND COLUMN_NAME = 'moderation_checked_at') THEN
        ALTER TABLE forum_threads ADD COLUMN moderation_checked_at DATETIME NULL;
    END IF;
END$$
DELIMITER ;
CALL add_forum_threads_moderation_columns();
DROP PROCEDURE add_forum_threads_moderation_columns;

-- Add moderation columns to forum_posts table (if not exists)
DELIMITER $$
CREATE PROCEDURE add_forum_posts_moderation_columns()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'forum_posts' 
                   AND COLUMN_NAME = 'moderation_status') THEN
        ALTER TABLE forum_posts ADD COLUMN moderation_status ENUM('pending', 'approved', 'flagged', 'rejected') DEFAULT 'approved';
    END IF;

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'forum_posts' 
                   AND COLUMN_NAME = 'moderation_checked_at') THEN
        ALTER TABLE forum_posts ADD COLUMN moderation_checked_at DATETIME NULL;
    END IF;
END$$
DELIMITER ;
CALL add_forum_posts_moderation_columns();
DROP PROCEDURE add_forum_posts_moderation_columns;

-- Verify installation
SELECT 'AI Content Moderation System installed successfully!' as Status;
SELECT COUNT(*) as moderation_logs_count FROM moderation_logs;
SELECT COUNT(*) as settings_count FROM moderation_settings;
