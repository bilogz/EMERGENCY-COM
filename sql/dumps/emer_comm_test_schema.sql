-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
-- Host: localhost:3306
-- Generation Time: Jan 07, 2026 at 07:04 AM
-- Server version: 8.0.44-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Database: `emer_comm_test`

-- --------------------------------------------------------

-- Table structure for table `admin_activity_logs`

CREATE TABLE `admin_activity_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `admin_login_logs`

CREATE TABLE `admin_login_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `logout_at` datetime DEFAULT NULL,
  `session_duration` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `admin_user`

CREATE TABLE `admin_user` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Full name of the admin',
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Username for login',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address (unique)',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hashed password',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'admin' COMMENT 'super_admin, admin, staff',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending_approval' COMMENT 'active, inactive, suspended, pending_approval',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phone number',
  `created_by` int DEFAULT NULL COMMENT 'ID of admin who created this account',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `ai_warning_settings`

CREATE TABLE `ai_warning_settings` (
  `id` int NOT NULL,
  `ai_enabled` tinyint(1) DEFAULT '0',
  `ai_check_interval` int DEFAULT '30',
  `wind_threshold` decimal(5,2) DEFAULT '60.00',
  `rain_threshold` decimal(5,2) DEFAULT '20.00',
  `earthquake_threshold` decimal(3,1) DEFAULT '5.0',
  `warning_types` text,
  `monitored_areas` text,
  `ai_channels` text,
  `weather_analysis_auto_send` tinyint(1) DEFAULT '0',
  `weather_analysis_interval` int DEFAULT '60',
  `weather_analysis_verification_key` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------

-- Table structure for table `alerts`

CREATE TABLE `alerts` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `content` text,
  `source` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `alert_acknowledgments`

CREATE TABLE `alert_acknowledgments` (
  `id` int NOT NULL,
  `alert_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'received' COMMENT 'received, safe, need_help',
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Location when acknowledged',
  `longitude` decimal(11,8) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `alert_categories`

CREATE TABLE `alert_categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-exclamation-triangle',
  `description` text,
  `color` varchar(7) DEFAULT '#4c8a89',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------

-- Table structure for table `alert_translations`

CREATE TABLE `alert_translations` (
  `id` int NOT NULL,
  `alert_id` int NOT NULL,
  `target_language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'en, tl, ceb, etc.',
  `translated_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `translated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `translated_by_admin_id` int DEFAULT NULL COMMENT 'Admin who created/updated this translation',
  `translation_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manual' COMMENT 'manual, ai, hybrid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `audit_log`

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `automated_warnings`

CREATE TABLE `automated_warnings` (
  `id` int NOT NULL,
  `source` varchar(50) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `severity` varchar(20) DEFAULT 'medium',
  `status` varchar(20) DEFAULT 'pending',
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `chat_messages`

CREATE TABLE `chat_messages` (
  `message_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `sender_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_type` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address when message was sent',
  `device_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Device info when message was sent',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `chat_queue`

CREATE TABLE `chat_queue` (
  `queue_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_concern` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT '1',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `assigned_to` int DEFAULT NULL COMMENT 'Admin user ID',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `emergency_contacts`

CREATE TABLE `emergency_contacts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `priority` int DEFAULT '1',
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `evacuation_centers`

CREATE TABLE `evacuation_centers` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `capacity` int DEFAULT '0',
  `current_occupancy` int DEFAULT '0',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, full, closed, inactive',
  `contact_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amenities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'wifi, water, food, medical',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `incident_reports`

CREATE TABLE `incident_reports` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `report_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'fire, flood, injury, crime, other',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, verified, resolved, false_alarm',
  `media_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to uploaded image/video',
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Internal notes by responders',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `integration_settings`

CREATE TABLE `integration_settings` (
  `id` int NOT NULL,
  `source` varchar(50) NOT NULL,
  `enabled` tinyint(1) DEFAULT '0',
  `api_key` varchar(255) DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------

-- Table structure for table `messages`

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `content` text NOT NULL,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `nonce` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `notification_logs`

CREATE TABLE `notification_logs` (
  `id` int NOT NULL,
  `channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sms, email, pa',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipients` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated list of recipients',
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, success, failed',
  `sent_at` datetime NOT NULL,
  `sent_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `otp_verifications`

CREATE TABLE `otp_verifications` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address for OTP',
  `otp_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '6-digit OTP code',
  `purpose` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'admin_login, admin_create, password_reset, etc.',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, verified, expired, failed',
  `expires_at` datetime NOT NULL COMMENT 'OTP expiration time',
  `verified_at` datetime DEFAULT NULL COMMENT 'When OTP was verified',
  `attempts` int DEFAULT '0' COMMENT 'Number of verification attempts',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address of requester',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `password_reset_tokens`

CREATE TABLE `password_reset_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Reset token',
  `purpose` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'password_reset' COMMENT 'password_reset, email_verification',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, used, expired',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `rate_limits`

CREATE TABLE `rate_limits` (
  `id` int NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int DEFAULT '1',
  `first_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `safety_guides`

CREATE TABLE `safety_guides` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL COMMENT 'Links to alert_categories',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `subscriptions`

CREATE TABLE `subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `categories` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: weather,earthquake,bomb,fire,general',
  `channels` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: sms,email,push',
  `preferred_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, inactive, suspended',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `supported_languages`

CREATE TABLE `supported_languages` (
  `id` int NOT NULL,
  `language_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 639-1 or custom code',
  `language_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Display name',
  `native_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Native name',
  `flag_emoji` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Flag emoji',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Whether active',
  `is_ai_supported` tinyint(1) DEFAULT '1' COMMENT 'AI translation available',
  `priority` int DEFAULT '0' COMMENT 'Display priority',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `translation_activity_logs`

CREATE TABLE `translation_activity_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL COMMENT 'Admin who performed action',
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Action type',
  `alert_id` int DEFAULT NULL COMMENT 'Related alert ID',
  `translation_id` int DEFAULT NULL COMMENT 'Related translation ID',
  `source_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translation_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'manual, ai, hybrid',
  `success` tinyint(1) DEFAULT '1' COMMENT 'Success status',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Error if failed',
  `metadata` json DEFAULT NULL COMMENT 'Additional data',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `translation_cache`

CREATE TABLE `translation_cache` (
  `id` int NOT NULL,
  `cache_key` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'MD5 hash of text+source+target',
  `source_text` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Original text',
  `source_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Source language code',
  `target_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Target language code',
  `translated_text` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Translated text',
  `translation_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'api' COMMENT 'Translation method used',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `users`

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Full name of the user',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email address (optional)',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile phone number',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, inactive, suspended, pending_approval',
  `user_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'citizen' COMMENT 'citizen, guest',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Google OAuth user ID',
  `barangay` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Barangay name',
  `house_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'House number',
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Street name',
  `district` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'District name',
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nationality',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Full address',
  `email_verified` tinyint(1) DEFAULT '0' COMMENT 'Email verification status',
  `verification_date` datetime DEFAULT NULL COMMENT 'Email verification date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `user_activity_logs`

CREATE TABLE `user_activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL COMMENT 'NULL for guest users',
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'login, logout, profile_update, password_change, etc',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Activity description',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'success' COMMENT 'success, failed, blocked',
  `metadata` json DEFAULT NULL COMMENT 'Additional activity data',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

-- Table structure for table `user_devices`

CREATE TABLE `user_devices` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `device_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique device identifier',
  `device_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ios, android, web',
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Device model/name',
  `push_token` text COLLATE utf8mb4_unicode_ci COMMENT 'Push notification token',
  `fcm_token` text COLLATE utf8mb4_unicode_ci COMMENT 'Firebase Cloud Messaging token',
  `apns_token` text COLLATE utf8mb4_unicode_ci COMMENT 'Apple Push Notification token',
  `is_active` tinyint(1) DEFAULT '1',
  `last_active` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_locations`

CREATE TABLE `user_locations` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci COMMENT 'Resolved address from coordinates',
  `accuracy` decimal(10,2) DEFAULT NULL COMMENT 'Location accuracy in meters',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'gps' COMMENT 'gps, network, manual',
  `is_current` tinyint(1) DEFAULT '0' COMMENT 'Current location flag',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_preferences`

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `sms_notifications` tinyint(1) DEFAULT '1' COMMENT 'Enable SMS notifications',
  `email_notifications` tinyint(1) DEFAULT '1' COMMENT 'Enable email notifications',
  `push_notifications` tinyint(1) DEFAULT '1' COMMENT 'Enable push notifications',
  `alert_categories` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: weather,earthquake,fire,etc',
  `preferred_language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en' COMMENT 'en, tl, ceb, etc',
  `alert_priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'all' COMMENT 'all, high, critical',
  `theme` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'light' COMMENT 'light, dark, auto',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Asia/Manila',
  `profile_visibility` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'private' COMMENT 'public, private, friends',
  `share_location` tinyint(1) DEFAULT '0' COMMENT 'Allow location sharing',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_sessions`

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique session token',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT 'Browser/device information',
  `device_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'mobile, desktop, tablet',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Geographic location if available',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, expired, revoked',
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL COMMENT 'Session expiration time',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_subscriptions`

CREATE TABLE `user_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `category_id` int DEFAULT NULL COMMENT 'Alert category ID',
  `subscription_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'all' COMMENT 'all, category, custom',
  `channels` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: sms,email,push',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `warning_settings`

CREATE TABLE `warning_settings` (
  `id` int NOT NULL,
  `sync_interval` int DEFAULT '15' COMMENT 'Minutes',
  `auto_publish` tinyint(1) DEFAULT '0',
  `notification_channels` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: sms,email,pa',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Indexes for dumped tables

-- Indexes for table `admin_activity_logs`
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `admin_login_logs`
ALTER TABLE `admin_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_login_status` (`login_status`),
  ADD KEY `idx_login_at` (`login_at`);

-- Indexes for table `admin_user`
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `ai_warning_settings`
ALTER TABLE `ai_warning_settings`
  ADD PRIMARY KEY (`id`);

-- Indexes for table `alerts`
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `alert_acknowledgments`
ALTER TABLE `alert_acknowledgments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_user` (`alert_id`,`user_id`);

-- Indexes for table `alert_categories`
ALTER TABLE `alert_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

-- Indexes for table `alert_translations`
ALTER TABLE `alert_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alert_language` (`alert_id`,`target_language`),
  ADD KEY `idx_language` (`target_language`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_translated_by` (`translated_by_admin_id`),
  ADD KEY `idx_translation_method` (`translation_method`);

-- Indexes for table `audit_log`
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `automated_warnings`
ALTER TABLE `automated_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_received_at` (`received_at`);

-- Indexes for table `chat_messages`
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_sender_type` (`sender_type`);

-- Indexes for table `chat_queue`
ALTER TABLE `chat_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD UNIQUE KEY `idx_conversation_unique` (`conversation_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `emergency_contacts`
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_primary` (`is_primary`);

-- Indexes for table `evacuation_centers`
ALTER TABLE `evacuation_centers`
  ADD PRIMARY KEY (`id`);

-- Indexes for table `incident_reports`
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `integration_settings`
ALTER TABLE `integration_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source` (`source`),
  ADD KEY `idx_source` (`source`);

-- Indexes for table `messages`
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nonce` (`nonce`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

-- Indexes for table `notification_logs`
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

-- Indexes for table `otp_verifications`
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_email_purpose` (`email`,`purpose`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

-- Indexes for table `password_reset_tokens`
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

-- Indexes for table `rate_limits`
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_action` (`ip_address`,`action`),
  ADD KEY `idx_last_attempt` (`last_attempt`);

-- Indexes for table `safety_guides`
ALTER TABLE `safety_guides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`);

-- Indexes for table `subscriptions`
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `supported_languages`
ALTER TABLE `supported_languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language_code` (`language_code`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`);

-- Indexes for table `translation_activity_logs`
ALTER TABLE `translation_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_alert_id` (`alert_id`),
  ADD KEY `idx_translation_id` (`translation_id`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `translation_cache`
ALTER TABLE `translation_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`),
  ADD KEY `idx_cache_key` (`cache_key`),
  ADD KEY `idx_langs` (`source_lang`,`target_lang`),
  ADD KEY `idx_created` (`created_at`);

-- Indexes for table `users`
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `user_activity_logs`
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

-- Indexes for table `user_devices`
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_device` (`user_id`,`device_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_device_type` (`device_type`),
  ADD KEY `idx_is_active` (`is_active`);

-- Indexes for table `user_locations`
ALTER TABLE `user_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_current` (`is_current`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_location` (`latitude`,`longitude`);

-- Indexes for table `user_preferences`
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

-- Indexes for table `user_sessions`
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_last_activity` (`last_activity`);

-- Indexes for table `user_subscriptions`
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_is_active` (`is_active`);

-- Indexes for table `warning_settings`
ALTER TABLE `warning_settings`
  ADD PRIMARY KEY (`id`);

-- AUTO_INCREMENT for dumped tables

-- AUTO_INCREMENT for table `admin_activity_logs`
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- AUTO_INCREMENT for table `admin_login_logs`
ALTER TABLE `admin_login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- AUTO_INCREMENT for table `admin_user`
ALTER TABLE `admin_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

-- AUTO_INCREMENT for table `ai_warning_settings`
ALTER TABLE `ai_warning_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- AUTO_INCREMENT for table `alerts`
ALTER TABLE `alerts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `alert_acknowledgments`
ALTER TABLE `alert_acknowledgments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `alert_categories`
ALTER TABLE `alert_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- AUTO_INCREMENT for table `alert_translations`
ALTER TABLE `alert_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `audit_log`
ALTER TABLE `audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- AUTO_INCREMENT for table `automated_warnings`
ALTER TABLE `automated_warnings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `chat_messages`
ALTER TABLE `chat_messages`
  MODIFY `message_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

-- AUTO_INCREMENT for table `chat_queue`
ALTER TABLE `chat_queue`
  MODIFY `queue_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

-- AUTO_INCREMENT for table `emergency_contacts`
ALTER TABLE `emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `evacuation_centers`
ALTER TABLE `evacuation_centers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- AUTO_INCREMENT for table `incident_reports`
ALTER TABLE `incident_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `integration_settings`
ALTER TABLE `integration_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- AUTO_INCREMENT for table `messages`
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `notification_logs`
ALTER TABLE `notification_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `otp_verifications`
ALTER TABLE `otp_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- AUTO_INCREMENT for table `password_reset_tokens`
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `rate_limits`
ALTER TABLE `rate_limits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `safety_guides`
ALTER TABLE `safety_guides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `subscriptions`
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `supported_languages`
ALTER TABLE `supported_languages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

-- AUTO_INCREMENT for table `translation_activity_logs`
ALTER TABLE `translation_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `translation_cache`
ALTER TABLE `translation_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=984;

-- AUTO_INCREMENT for table `users`
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- AUTO_INCREMENT for table `user_activity_logs`
ALTER TABLE `user_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- AUTO_INCREMENT for table `user_devices`
ALTER TABLE `user_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `user_locations`
ALTER TABLE `user_locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `user_preferences`
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `user_sessions`
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `user_subscriptions`
ALTER TABLE `user_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `warning_settings`
ALTER TABLE `warning_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Constraints for dumped tables

-- Constraints for table `admin_user`
ALTER TABLE `admin_user`
  ADD CONSTRAINT `admin_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `admin_user_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_user` (`id`) ON DELETE SET NULL;

-- Constraints for table `chat_messages`
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE;

-- Constraints for table `chat_queue`
ALTER TABLE `chat_queue`
  ADD CONSTRAINT `fk_queue_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE;

-- Constraints for table `user_activity_logs`
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `user_devices`
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Constraints for table `user_locations`
ALTER TABLE `user_locations`
  ADD CONSTRAINT `user_locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Constraints for table `user_preferences`
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Constraints for table `user_sessions`
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Constraints for table `user_subscriptions`
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `alert_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
