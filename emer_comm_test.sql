-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 28, 2025 at 10:58 AM
-- Server version: 8.0.44-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `emer_comm_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'Admin logged in successfully', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 16:50:06'),
(2, 1, 'login', 'Admin logged in successfully', '103.186.138.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 04:11:53'),
(3, 1, 'login', 'Admin logged in successfully', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 05:15:19'),
(4, 1, 'login', 'Admin logged in successfully', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:38:43'),
(5, 1, 'login', 'Admin logged in successfully', '136.158.2.227', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:20:52'),
(6, 1, 'login_failed', 'Failed login attempt - invalid password', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:31:53'),
(7, 1, 'login', 'Admin logged in successfully', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:35:53'),
(8, 1, 'logout', 'Admin logged out', '103.186.138.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 09:57:54'),
(9, 1, 'login', 'Admin logged in successfully', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:34:56'),
(10, 1, 'logout', 'Admin logged out', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:47:27');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_logs`
--

CREATE TABLE `admin_login_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `logout_at` datetime DEFAULT NULL,
  `session_duration` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_logs`
--

INSERT INTO `admin_login_logs` (`id`, `admin_id`, `email`, `login_status`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `session_duration`) VALUES
(1, 0, 'yssaci@gmail.com', 'failed', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 16:48:15', NULL, NULL),
(2, 1, 'joecelgarcia1@gmail.com', 'success', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 16:50:06', NULL, NULL),
(3, 1, 'joecelgarcia1@gmail.com', 'success', '103.186.138.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 04:11:53', NULL, NULL),
(4, 1, 'joecelgarcia1@gmail.com', 'success', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 05:15:19', NULL, NULL),
(5, 1, 'joecelgarcia1@gmail.com', 'success', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:38:43', '2025-12-28 09:57:54', 8351),
(6, 1, 'joecelgarcia1@gmail.com', 'success', '136.158.2.227', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:20:52', NULL, NULL),
(7, 1, 'joecelgarcia1@gmail.com', 'failed', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:31:53', NULL, NULL),
(8, 1, 'joecelgarcia1@gmail.com', 'success', '112.203.47.228', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:35:53', NULL, NULL),
(9, 1, 'joecelgarcia1@gmail.com', 'success', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:34:56', '2025-12-28 10:47:27', 751),
(10, 0, 'joecelgarcia1@gmail.com', 'failed', '43.240.55.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:47:47', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'Reference to users table',
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

--
-- Table structure for table `ai_warning_settings`
--

CREATE TABLE `ai_warning_settings` (
  `id` int NOT NULL,
  `gemini_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_enabled` tinyint(1) DEFAULT '0',
  `ai_check_interval` int DEFAULT '30',
  `wind_threshold` decimal(5,2) DEFAULT '60.00',
  `rain_threshold` decimal(5,2) DEFAULT '20.00',
  `earthquake_threshold` decimal(3,1) DEFAULT '5.0',
  `warning_types` text COLLATE utf8mb4_unicode_ci,
  `monitored_areas` text COLLATE utf8mb4_unicode_ci,
  `ai_channels` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_warning_settings`
--

INSERT INTO `ai_warning_settings` (`id`, `gemini_api_key`, `ai_enabled`, `ai_check_interval`, `wind_threshold`, `rain_threshold`, `earthquake_threshold`, `warning_types`, `monitored_areas`, `ai_channels`, `updated_at`) VALUES
(1, '', 0, 30, 60.00, 20.00, 5.0, '', '', '', '2025-12-28 09:24:25');

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alert_categories`
--

CREATE TABLE `alert_categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-exclamation-triangle',
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#4c8a89',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alert_categories`
--

INSERT INTO `alert_categories` (`id`, `name`, `icon`, `description`, `color`, `created_at`, `updated_at`) VALUES
(1, 'Weather', 'fa-cloud-rain', 'Weather-related alerts including storms, floods, and typhoons', '#3498db', '2025-12-27 16:08:12', '2025-12-27 16:08:12'),
(2, 'Earthquake', 'fa-mountain', 'Seismic activity and earthquake warnings', '#e74c3c', '2025-12-27 16:08:12', '2025-12-27 16:08:12'),
(3, 'Bomb Threat', 'fa-bomb', 'Security threats and bomb alerts', '#c0392b', '2025-12-27 16:08:12', '2025-12-27 16:08:12'),
(4, 'Fire', 'fa-fire', 'Fire emergencies and fire safety alerts', '#e67e22', '2025-12-27 16:08:12', '2025-12-27 16:08:12'),
(5, 'General', 'fa-exclamation-triangle', 'General emergency alerts and announcements', '#95a5a6', '2025-12-27 16:08:12', '2025-12-27 16:08:12');

-- --------------------------------------------------------

--
-- Table structure for table `alert_translations`
--

CREATE TABLE `alert_translations` (
  `id` int NOT NULL,
  `alert_id` int NOT NULL,
  `target_language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'en, tl, ceb, etc.',
  `translated_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `translated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `action`, `user_id`, `performed_by`, `ip_address`, `details`, `created_at`) VALUES
(1, 'admin_account_created', 1, NULL, '43.240.55.66', '{\"action\":\"admin_account_created\",\"timestamp\":\"2025-12-28 10:34:09\"}', '2025-12-28 10:34:09');

-- --------------------------------------------------------

--
-- Table structure for table `automated_warnings`
--

CREATE TABLE `automated_warnings` (
  `id` int NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pagasa, phivolcs',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, published, archived',
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, closed, archived',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `integration_settings`
--

CREATE TABLE `integration_settings` (
  `id` int NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pagasa, phivolcs',
  `enabled` tinyint(1) DEFAULT '0',
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `integration_settings`
--

INSERT INTO `integration_settings` (`id`, `source`, `enabled`, `api_key`, `api_url`, `last_sync`, `updated_at`) VALUES
(1, 'pagasa', 1, 'f35609a701ba47952fba4fd4604c12c7', NULL, NULL, '2025-12-28 09:03:12'),
(2, 'phivolcs', 1, NULL, NULL, NULL, '2025-12-28 09:03:13'),
(6, 'gemini', 0, '[API_KEY_REMOVED_FOR_SECURITY]', 'https://generativelanguage.googleapis.com/v1beta/', NULL, '2025-12-28 04:29:25');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'admin, citizen, system',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sms, email, pa',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipients` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated list of recipients',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, success, failed',
  `sent_at` datetime NOT NULL,
  `sent_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response` text COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address for OTP',
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '6-digit OTP code',
  `purpose` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'admin_login, admin_create, password_reset, etc.',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending, verified, expired, failed',
  `expires_at` datetime NOT NULL COMMENT 'OTP expiration time',
  `verified_at` datetime DEFAULT NULL COMMENT 'When OTP was verified',
  `attempts` int DEFAULT '0' COMMENT 'Number of verification attempts',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address of requester',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `email`, `otp_code`, `purpose`, `status`, `expires_at`, `verified_at`, `attempts`, `ip_address`, `created_at`) VALUES
(1, 'joecelgarcia1@gmail.com', '721498', 'admin_login', 'verified', '2025-12-27 16:59:26', '2025-12-27 16:50:06', 0, '112.203.47.228', '2025-12-27 16:49:26'),
(2, 'joecelgarcia1@gmail.com', '162055', 'admin_login', 'verified', '2025-12-28 04:21:33', '2025-12-28 04:11:53', 0, '43.240.55.66', '2025-12-28 04:11:33'),
(3, 'joecelgarcia1@gmail.com', '830857', 'admin_login', 'verified', '2025-12-28 05:24:56', '2025-12-28 05:15:19', 0, '43.240.55.66', '2025-12-28 05:14:56'),
(4, 'joecelgarcia1@gmail.com', '745812', 'admin_login', 'verified', '2025-12-28 07:48:21', '2025-12-28 07:38:43', 0, '103.186.138.49', '2025-12-28 07:38:21'),
(5, 'joecelgarcia1@gmail.com', '494126', 'admin_login', 'verified', '2025-12-28 08:30:24', '2025-12-28 08:20:52', 0, '136.158.2.227', '2025-12-28 08:20:24'),
(6, 'joecelgarcia1@gmail.com', '799319', 'admin_login', 'verified', '2025-12-28 08:42:54', '2025-12-28 08:35:53', 0, '112.203.47.228', '2025-12-28 08:32:54'),
(10, 'joecelgarcia1@gmail.com', '580245', 'admin_login', 'verified', '2025-12-28 10:44:33', '2025-12-28 10:34:56', 0, '43.240.55.66', '2025-12-28 10:34:33');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int DEFAULT '1',
  `first_attempt` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_attempt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `ip_address`, `action`, `attempts`, `first_attempt`, `last_attempt`) VALUES
(1, '43.240.55.66', 'create_admin', 2, '2025-12-28 10:34:09', '2025-12-28 10:57:49');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

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

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Full name of the user',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email address (optional)',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile phone number',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, inactive, suspended, pending_approval',
  `user_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'citizen' COMMENT 'citizen, guest',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warning_settings`
--

CREATE TABLE `warning_settings` (
  `id` int NOT NULL,
  `sync_interval` int DEFAULT '15' COMMENT 'Minutes',
  `auto_publish` tinyint(1) DEFAULT '0',
  `notification_channels` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated: sms,email,pa',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warning_settings`
--

INSERT INTO `warning_settings` (`id`, `sync_interval`, `auto_publish`, `notification_channels`, `updated_at`) VALUES
(1, 15, 0, 'sms,email', '2025-12-27 16:08:13'),
(2, 15, 0, 'sms,email', '2025-12-27 16:20:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_login_status` (`login_status`),
  ADD KEY `idx_login_at` (`login_at`);

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `ai_warning_settings`
--
ALTER TABLE `ai_warning_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `alert_categories`
--
ALTER TABLE `alert_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `alert_translations`
--
ALTER TABLE `alert_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alert_language` (`alert_id`,`target_language`),
  ADD KEY `idx_language` (`target_language`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `automated_warnings`
--
ALTER TABLE `automated_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source` (`source`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_received_at` (`received_at`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `integration_settings`
--
ALTER TABLE `integration_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source` (`source`),
  ADD KEY `idx_source` (`source`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_email_purpose` (`email`,`purpose`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_action` (`ip_address`,`action`),
  ADD KEY `idx_last_attempt` (`last_attempt`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `warning_settings`
--
ALTER TABLE `warning_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_warning_settings`
--
ALTER TABLE `ai_warning_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alert_categories`
--
ALTER TABLE `alert_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `alert_translations`
--
ALTER TABLE `alert_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `automated_warnings`
--
ALTER TABLE `automated_warnings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `integration_settings`
--
ALTER TABLE `integration_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `warning_settings`
--
ALTER TABLE `warning_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD CONSTRAINT `admin_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_user_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `alert_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `alert_translations`
--
ALTER TABLE `alert_translations`
  ADD CONSTRAINT `alert_translations_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
