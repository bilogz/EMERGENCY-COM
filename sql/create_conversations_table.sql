-- Create conversations table for chat system
-- This table stores conversation metadata and user information

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `conversations` (
  `conversation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(255) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_email` VARCHAR(255) DEFAULT NULL,
  `user_phone` VARCHAR(50) DEFAULT NULL,
  `user_location` VARCHAR(255) DEFAULT NULL,
  `user_concern` VARCHAR(100) DEFAULT NULL,
  `is_guest` TINYINT(1) DEFAULT 1,
  `status` ENUM('active', 'closed', 'archived') DEFAULT 'active',
  `last_message` TEXT DEFAULT NULL,
  `last_message_time` DATETIME DEFAULT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `device_info` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_updated_at` (`updated_at`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
