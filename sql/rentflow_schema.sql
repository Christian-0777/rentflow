-- ============================================================================
-- phpMyAdmin SQL Dump
-- RentFlow Database Schema (Full)
-- Includes Application Database User
-- Generated: 2026-01-15
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

-- ============================================================================
-- SECTION 1: DATABASE CREATION
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `rentflow`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `rentflow`;

-- ============================================================================
-- SECTION 2: APPLICATION DATABASE USER
-- ============================================================================
-- User: rentflow_team
-- Password: rentflow_3006
-- Purpose: Used by RentFlow application (NOT root)

CREATE USER IF NOT EXISTS 'rentflow_team'@'localhost'
IDENTIFIED BY 'rentflow_3006';

-- Ensure clean permission state
REVOKE ALL PRIVILEGES ON `rentflow`.* FROM 'rentflow_team'@'localhost';

-- Grant required permissions
GRANT
  SELECT, INSERT, UPDATE, DELETE,
  CREATE, DROP, INDEX, ALTER,
  EXECUTE
ON `rentflow`.*
TO 'rentflow_team'@'localhost';

FLUSH PRIVILEGES;

-- ============================================================================
-- SECTION 3: TABLE STRUCTURES
-- ============================================================================

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(4) DEFAULT NULL,
  `role` enum('tenant','admin','treasury') NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive','lease_ended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `confirmed` tinyint(1) DEFAULT 0,
  `cover_photo` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `remember_device_enabled` tinyint(1) DEFAULT 0,
  `password_reset_otp` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `password_reset_requested_at` datetime DEFAULT NULL,
  `notif_email` tinyint(1) DEFAULT 1,
  `notif_sms` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `tenant_id` (`tenant_id`),
  KEY `idx_password_reset_otp` (`password_reset_otp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- trusted_devices
-- --------------------------------------------------------
CREATE TABLE `trusted_devices` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_fingerprint` varchar(255) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `device_token` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_fingerprint` (`device_fingerprint`),
  UNIQUE KEY `device_token` (`device_token`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `trusted_devices_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- password_resets
-- --------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id_idx` (`user_id`),
  KEY `token_idx` (`token`),
  CONSTRAINT `password_resets_ibfk_1`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- stalls
-- --------------------------------------------------------
CREATE TABLE `stalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stall_no` varchar(32) NOT NULL,
  `type` enum('wet','dry','apparel') NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `created_at` datetime DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `picture_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stall_no` (`stall_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- leases
-- --------------------------------------------------------
CREATE TABLE `leases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `stall_id` int(11) NOT NULL,
  `lease_start` date NOT NULL,
  `lease_end` date DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `stall_id` (`stall_id`),
  CONSTRAINT `leases_ibfk_1`
    FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`),
  CONSTRAINT `leases_ibfk_2`
    FOREIGN KEY (`stall_id`) REFERENCES `stalls` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- payments
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lease_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('cash','gcash','bank','card') NOT NULL,
  `transaction_id` varchar(64) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `lease_id` (`lease_id`),
  CONSTRAINT `payments_ibfk_1`
    FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `dues`
-- --------------------------------------------------------
CREATE TABLE `dues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lease_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `paid` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `lease_id` (`lease_id`),
  CONSTRAINT `dues_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `arrears`
-- --------------------------------------------------------
CREATE TABLE `arrears` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lease_id` int(11) NOT NULL,
  `total_arrears` decimal(10,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lease_id` (`lease_id`),
  CONSTRAINT `arrears_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `penalties`
-- --------------------------------------------------------
CREATE TABLE `penalties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lease_id` int(11) NOT NULL,
  `due_id` int(11) NOT NULL,
  `penalty_amount` decimal(10,2) NOT NULL,
  `applied_on` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lease_id` (`lease_id`),
  KEY `due_id` (`due_id`),
  CONSTRAINT `penalties_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`),
  CONSTRAINT `penalties_ibfk_2` FOREIGN KEY (`due_id`) REFERENCES `dues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `type` enum('system','chat') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `auth_codes`
-- --------------------------------------------------------
CREATE TABLE `auth_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('admin','treasury') NOT NULL,
  `code1` varchar(16) NOT NULL,
  `code2` varchar(16) NOT NULL,
  `code3` varchar(16) NOT NULL,
  `valid_until` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `stall_applications`
-- --------------------------------------------------------
CREATE TABLE `stall_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `type` enum('wet','dry','apparel') NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_description` text NOT NULL,
  `business_permit_path` varchar(255) DEFAULT NULL,
  `valid_id_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `stall_applications_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
