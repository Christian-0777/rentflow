SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- DATABASE + USER SETUP
-- ============================================================

CREATE DATABASE IF NOT EXISTS rentflow
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rentflow;

-- ============================================================
-- USERS
-- ============================================================

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id VARCHAR(4) UNIQUE DEFAULT NULL,
  role ENUM('tenant','admin') NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','lease_ended') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  confirmed TINYINT(1) DEFAULT 0,
  cover_photo VARCHAR(255) DEFAULT NULL,
  profile_photo VARCHAR(255) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  business_name VARCHAR(255) DEFAULT NULL,
  two_factor_enabled TINYINT(1) DEFAULT 0,
  remember_device_enabled TINYINT(1) DEFAULT 0,
  password_reset_otp VARCHAR(255) DEFAULT NULL,
  password_reset_expires DATETIME DEFAULT NULL,
  password_reset_requested_at DATETIME DEFAULT NULL,
  notif_email TINYINT(1) DEFAULT 1,
  notif_sms TINYINT(1) DEFAULT 0,
  INDEX idx_password_reset_otp (password_reset_otp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STALLS
-- ============================================================

CREATE TABLE stalls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stall_no VARCHAR(32) NOT NULL UNIQUE,
  type ENUM('wet','dry','apparel') NOT NULL,
  location VARCHAR(255) NOT NULL,
  status ENUM('available','occupied','maintenance') DEFAULT 'available',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  image_path VARCHAR(255) DEFAULT NULL,
  picture_path VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- LEASES
-- ============================================================

CREATE TABLE leases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  stall_id INT NOT NULL,
  lease_start DATE NOT NULL,
  lease_end DATE DEFAULT NULL,
  monthly_rent DECIMAL(10,2) NOT NULL,
  INDEX (tenant_id),
  INDEX (stall_id),
  FOREIGN KEY (tenant_id) REFERENCES users(id),
  FOREIGN KEY (stall_id) REFERENCES stalls(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PAYMENTS
-- ============================================================

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  method ENUM('cash','gcash','bank','card','manual','partial') NOT NULL,
  transaction_id VARCHAR(64) UNIQUE DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  receipt_path VARCHAR(255) DEFAULT NULL,
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DUES
-- ============================================================

CREATE TABLE dues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  due_date DATE NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  paid TINYINT(1) DEFAULT 0,
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ARREARS
-- ============================================================

CREATE TABLE arrears (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  total_arrears DECIMAL(10,2) DEFAULT 0.00,
  last_updated DATETIME DEFAULT CURRENT_TIMESTAMP(),
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PENALTIES
-- ============================================================

CREATE TABLE penalties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  due_id INT NOT NULL,
  penalty_amount DECIMAL(10,2) NOT NULL,
  applied_on DATE NOT NULL,
  INDEX (lease_id),
  INDEX (due_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id),
  FOREIGN KEY (due_id) REFERENCES dues(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PASSWORD RESETS
-- ============================================================

CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  INDEX (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRUSTED DEVICES
-- ============================================================

CREATE TABLE trusted_devices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  device_fingerprint VARCHAR(255) NOT NULL UNIQUE,
  device_name VARCHAR(255) DEFAULT NULL,
  device_token VARCHAR(255) NOT NULL UNIQUE,
  user_agent TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP()
    ON UPDATE CURRENT_TIMESTAMP(),
  is_active TINYINT(1) DEFAULT 1,
  INDEX idx_user_id (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  type ENUM('system','chat') NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  is_read TINYINT(1) DEFAULT 0,
  INDEX (sender_id),
  INDEX (receiver_id),
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUTH CODES
-- ============================================================

CREATE TABLE auth_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin') NOT NULL,
  code1 VARCHAR(16) NOT NULL,
  code2 VARCHAR(16) NOT NULL,
  code3 VARCHAR(16) NOT NULL,
  valid_until DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STALL APPLICATIONS
-- ============================================================

CREATE TABLE stall_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  type ENUM('wet','dry','apparel') NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  business_description TEXT NOT NULL,
  business_logo_path VARCHAR(255) DEFAULT NULL,
  business_permit_path VARCHAR(255) DEFAULT NULL,
  valid_id_path VARCHAR(255) DEFAULT NULL,
  signature_path VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
  INDEX (tenant_id),
  FOREIGN KEY (tenant_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;