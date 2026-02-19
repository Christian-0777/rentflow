CREATE DATABASE IF NOT EXISTS rentflow
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rentflow;

-- =========================
-- USERS
-- =========================
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  tenant_id VARCHAR(4) UNIQUE,
  role ENUM('tenant','admin','treasury') NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','lease_ended') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  confirmed TINYINT(1) DEFAULT 0,
  cover_photo VARCHAR(255),
  profile_photo VARCHAR(255),
  location VARCHAR(255),
  business_name VARCHAR(255),
  two_factor_enabled TINYINT(1) DEFAULT 0,
  remember_device_enabled TINYINT(1) DEFAULT 0,
  password_reset_otp VARCHAR(255),
  password_reset_expires DATETIME,
  password_reset_requested_at DATETIME,
  notif_email TINYINT(1) DEFAULT 1,
  notif_sms TINYINT(1) DEFAULT 0,
  notify_email_on_messages TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id),
  INDEX idx_password_reset_otp (password_reset_otp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- STALLS
-- =========================
CREATE TABLE stalls (
  id INT(11) NOT NULL AUTO_INCREMENT,
  stall_no VARCHAR(32) NOT NULL UNIQUE,
  type ENUM('wet','dry','apparel') NOT NULL,
  location VARCHAR(255) NOT NULL,
  status ENUM('available','occupied','maintenance') DEFAULT 'available',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  image_path VARCHAR(255),
  picture_path VARCHAR(255),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- LEASES
-- =========================
CREATE TABLE leases (
  id INT(11) NOT NULL AUTO_INCREMENT,
  tenant_id INT(11) NOT NULL,
  stall_id INT(11) NOT NULL,
  lease_start DATE NOT NULL,
  lease_end DATE,
  monthly_rent DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  INDEX (tenant_id),
  INDEX (stall_id),
  CONSTRAINT fk_leases_user
    FOREIGN KEY (tenant_id) REFERENCES users(id),
  CONSTRAINT fk_leases_stall
    FOREIGN KEY (stall_id) REFERENCES stalls(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- DUES
-- =========================
CREATE TABLE dues (
  id INT(11) NOT NULL AUTO_INCREMENT,
  lease_id INT(11) NOT NULL,
  due_date DATE NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  paid TINYINT(1) DEFAULT 0,
  marked_arrear_on DATE,
  PRIMARY KEY (id),
  INDEX (lease_id),
  CONSTRAINT fk_dues_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- PAYMENTS
-- =========================
CREATE TABLE payments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  lease_id INT(11) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  method ENUM('cash','gcash','bank','card','manual','partial') NOT NULL,
  transaction_id VARCHAR(64) UNIQUE,
  remarks VARCHAR(255),
  receipt_path VARCHAR(255),
  PRIMARY KEY (id),
  INDEX (lease_id),
  CONSTRAINT fk_payments_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- ARREARS
-- =========================
CREATE TABLE arrears (
  id INT(11) NOT NULL AUTO_INCREMENT,
  lease_id INT(11) NOT NULL,
  total_arrears DECIMAL(10,2) DEFAULT 0.00,
  last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
  reason VARCHAR(255),
  PRIMARY KEY (id),
  INDEX (lease_id),
  CONSTRAINT fk_arrears_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE arrear_entries (
  id INT(11) NOT NULL AUTO_INCREMENT,
  lease_id INT(11) NOT NULL,
  due_id INT(11),
  amount DECIMAL(10,2) NOT NULL,
  source ENUM('unpaid_due','marked_not_paid','partial_payment','overdue_7days') NOT NULL,
  created_on DATE NOT NULL,
  is_paid TINYINT(1) DEFAULT 0,
  paid_on DATE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX (lease_id),
  INDEX (due_id),
  INDEX (created_on),
  CONSTRAINT fk_arrear_entries_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id),
  CONSTRAINT fk_arrear_entries_due
    FOREIGN KEY (due_id) REFERENCES dues(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- PENALTIES
-- =========================
CREATE TABLE penalties (
  id INT(11) NOT NULL AUTO_INCREMENT,
  lease_id INT(11) NOT NULL,
  due_id INT(11) NOT NULL,
  penalty_amount DECIMAL(10,2) NOT NULL,
  applied_on DATE NOT NULL,
  PRIMARY KEY (id),
  INDEX (lease_id),
  INDEX (due_id),
  CONSTRAINT fk_penalties_lease
    FOREIGN KEY (lease_id) REFERENCES leases(id),
  CONSTRAINT fk_penalties_due
    FOREIGN KEY (due_id) REFERENCES dues(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- MESSAGING
-- =========================
CREATE TABLE messages (
  id INT(11) NOT NULL AUTO_INCREMENT,
  sender_id INT(11) NOT NULL,
  receiver_id INT(11) NOT NULL,
  subject VARCHAR(255),
  message TEXT NOT NULL,
  sender_email VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  is_archived TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  attachment_path VARCHAR(255),
  attachment_type ENUM('image','document','other'),
  PRIMARY KEY (id),
  INDEX idx_sender_id (sender_id),
  INDEX idx_receiver_id (receiver_id),
  INDEX idx_conversation (sender_id, receiver_id),
  INDEX idx_created_at (created_at),
  CONSTRAINT fk_messages_sender
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_receiver
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE message_threads (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user1_id INT(11) NOT NULL,
  user2_id INT(11) NOT NULL,
  last_message_id INT(11),
  last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_thread (user1_id, user2_id),
  INDEX (last_message_at),
  CONSTRAINT fk_thread_user1
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_thread_user2
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- NOTIFICATIONS
-- =========================
CREATE TABLE notifications (
  id INT(11) NOT NULL AUTO_INCREMENT,
  sender_id INT(11) NOT NULL,
  receiver_id INT(11) NOT NULL,
  type ENUM('system','chat') NOT NULL,
  title VARCHAR(255),
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_read TINYINT(1) DEFAULT 0,
  message_id INT(11),
  PRIMARY KEY (id),
  INDEX (sender_id),
  INDEX (receiver_id),
  CONSTRAINT fk_notifications_sender
    FOREIGN KEY (sender_id) REFERENCES users(id),
  CONSTRAINT fk_notifications_receiver
    FOREIGN KEY (receiver_id) REFERENCES users(id),
  CONSTRAINT fk_notifications_message
    FOREIGN KEY (message_id) REFERENCES messages(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- STALL APPLICATIONS
-- =========================
CREATE TABLE stall_applications (
  id INT(11) NOT NULL AUTO_INCREMENT,
  tenant_id INT(11) NOT NULL,
  type ENUM('wet','dry','apparel') NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  business_description TEXT NOT NULL,
  business_logo_path VARCHAR(255),
  business_permit_path VARCHAR(255),
  valid_id_path VARCHAR(255),
  signature_path VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX (tenant_id),
  CONSTRAINT fk_stall_applications_user
    FOREIGN KEY (tenant_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- SECURITY TABLES
-- =========================
CREATE TABLE auth_codes (
  id INT(11) NOT NULL AUTO_INCREMENT,
  role ENUM('admin','treasury') NOT NULL,
  code1 VARCHAR(16) NOT NULL,
  code2 VARCHAR(16) NOT NULL,
  code3 VARCHAR(16) NOT NULL,
  valid_until DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE password_resets (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  PRIMARY KEY (id),
  INDEX (user_id),
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE trusted_devices (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  device_fingerprint VARCHAR(255) NOT NULL UNIQUE,
  device_name VARCHAR(255),
  device_token VARCHAR(255) NOT NULL UNIQUE,
  user_agent TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  is_active TINYINT(1) DEFAULT 1,
  PRIMARY KEY (id),
  INDEX (user_id),
  CONSTRAINT fk_trusted_devices_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
