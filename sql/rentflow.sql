CREATE DATABASE IF NOT EXISTS rentflow
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE rentflow;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- USERS
-- =========================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id VARCHAR(4) UNIQUE,
  role ENUM('tenant','admin','treasury') NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255),
  tenant_code_hash VARCHAR(255),
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
  INDEX idx_password_reset_otp(password_reset_otp)
);

-- =========================
-- TENANT ACCOUNTS
-- =========================
CREATE TABLE tenant_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- AUTH CODES
-- =========================
CREATE TABLE auth_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin','treasury') NOT NULL,
  code1 VARCHAR(16) NOT NULL,
  code2 VARCHAR(16) NOT NULL,
  code3 VARCHAR(16) NOT NULL,
  valid_until DATETIME NOT NULL
);

-- =========================
-- STALLS
-- =========================
CREATE TABLE stalls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stall_no VARCHAR(32) UNIQUE NOT NULL,
  type ENUM('wet','dry','apparel') NOT NULL,
  location VARCHAR(255) NOT NULL,
  status ENUM('available','occupied','maintenance') DEFAULT 'available',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  image_path VARCHAR(255),
  picture_path VARCHAR(255)
);

-- =========================
-- LEASES
-- =========================
CREATE TABLE leases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  stall_id INT NOT NULL,
  lease_start DATE NOT NULL,
  lease_end DATE,
  monthly_rent DECIMAL(10,2) NOT NULL,
  INDEX (tenant_id),
  INDEX (stall_id),
  FOREIGN KEY (tenant_id) REFERENCES users(id),
  FOREIGN KEY (stall_id) REFERENCES stalls(id)
);

-- =========================
-- DUES
-- =========================
CREATE TABLE dues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  due_date DATE NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  paid TINYINT(1) DEFAULT 0,
  marked_arrear_on DATE,
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
);

-- =========================
-- PAYMENTS
-- =========================
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  due_id INT,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  method ENUM('cash','manual','partial') NOT NULL,
  transaction_id VARCHAR(64) UNIQUE,
  remarks VARCHAR(255),
  receipt_path VARCHAR(255),
  INDEX (lease_id),
  INDEX (due_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id),
  FOREIGN KEY (due_id) REFERENCES dues(id)
);

-- =========================
-- ARREARS
-- =========================
CREATE TABLE arrears (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  total_arrears DECIMAL(10,2) DEFAULT 0.00,
  last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
  reason VARCHAR(255),
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
);

-- =========================
-- ARREAR ENTRIES
-- =========================
CREATE TABLE arrear_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lease_id INT NOT NULL,
  due_id INT,
  amount DECIMAL(10,2) NOT NULL,
  source ENUM('unpaid_due','marked_not_paid','partial_payment','overdue_7days') NOT NULL,
  created_on DATE NOT NULL,
  is_paid TINYINT(1) DEFAULT 0,
  paid_on DATE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (lease_id),
  INDEX (due_id),
  INDEX (created_on),
  FOREIGN KEY (lease_id) REFERENCES leases(id),
  FOREIGN KEY (due_id) REFERENCES dues(id)
);

-- =========================
-- PENALTIES
-- =========================
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
);

-- =========================
-- RECEIPTS
-- =========================
CREATE TABLE receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_no VARCHAR(12) UNIQUE NOT NULL,
  lease_id INT NOT NULL,
  payment_date DATE NOT NULL,
  received_from VARCHAR(255) NOT NULL,
  stall_no VARCHAR(50) NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  payment_for VARCHAR(255) NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  total_balance DECIMAL(10,2) NOT NULL,
  status ENUM('Full Payment','Partial Payment','Not Paid') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (lease_id),
  FOREIGN KEY (lease_id) REFERENCES leases(id)
);

-- =========================
-- MESSAGES
-- =========================
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  subject VARCHAR(255),
  message TEXT NOT NULL,
  sender_email VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  is_archived TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  attachment_path VARCHAR(255),
  attachment_type ENUM('image','document','other'),
  INDEX idx_sender_id(sender_id),
  INDEX idx_receiver_id(receiver_id),
  INDEX idx_conversation(sender_id,receiver_id),
  INDEX idx_created_at(created_at),
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- MESSAGE THREADS
-- =========================
CREATE TABLE message_threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user1_id INT NOT NULL,
  user2_id INT NOT NULL,
  last_message_id INT,
  last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_thread(user1_id,user2_id),
  INDEX (last_message_at),
  FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- NOTIFICATIONS
-- =========================
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  type ENUM('system','chat') NOT NULL,
  title VARCHAR(255),
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_read TINYINT(1) DEFAULT 0,
  message_id INT,
  INDEX (sender_id),
  INDEX (receiver_id),
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- =========================
-- STALL APPLICATIONS
-- =========================
CREATE TABLE stall_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  type ENUM('wet','dry','apparel') NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  business_description TEXT NOT NULL,
  business_logo_path VARCHAR(255),
  business_permit_path VARCHAR(255),
  valid_id_path VARCHAR(255),
  signature_path VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (tenant_id),
  FOREIGN KEY (tenant_id) REFERENCES users(id)
);

SET FOREIGN_KEY_CHECKS = 1;