-- Chat System Database Tables
-- Run this SQL script to create the necessary tables for the chat system

-- Drop foreign key constraints first (if they exist)
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (in reverse order due to foreign keys)
DROP TABLE IF EXISTS `chat_queue`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `messages`; -- In case old table exists
DROP TABLE IF EXISTS `conversations`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Conversations table (must be created first)
CREATE TABLE `conversations` (
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
  `assigned_to` INT(11) DEFAULT NULL COMMENT 'Admin user ID',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table (created after conversations)
CREATE TABLE `chat_messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` INT(11) NOT NULL,
  `sender_id` VARCHAR(255) NOT NULL,
  `sender_name` VARCHAR(255) NOT NULL,
  `sender_type` ENUM('user', 'admin') NOT NULL,
  `message_text` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_sender_type` (`sender_type`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat queue table (for admin notifications)
CREATE TABLE `chat_queue` (
  `queue_id` INT(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` INT(11) NOT NULL,
  `user_id` VARCHAR(255) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_email` VARCHAR(255) DEFAULT NULL,
  `user_phone` VARCHAR(50) DEFAULT NULL,
  `user_location` VARCHAR(255) DEFAULT NULL,
  `user_concern` VARCHAR(100) DEFAULT NULL,
  `is_guest` TINYINT(1) DEFAULT 1,
  `message` TEXT NOT NULL,
  `status` ENUM('pending', 'accepted', 'closed') DEFAULT 'pending',
  `assigned_to` INT(11) DEFAULT NULL COMMENT 'Admin user ID',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`queue_id`),
  UNIQUE KEY `idx_conversation_unique` (`conversation_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_queue_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

