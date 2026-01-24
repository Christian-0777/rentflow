-- ============================================================================
-- RENTFLOW DATABASE SECURITY CONFIGURATION AND SEED DATA
-- ============================================================================
-- This migration file sets up:
-- 1. Database user 'rentflow_team' with secure permissions
-- 2. Security settings for the database connection
-- 3. Initial seed data for the application
-- ============================================================================

-- ============================================================================
-- STEP 1: DATABASE USER SETUP (Run as root or admin user)
-- ============================================================================
-- Create the rentflow_team database user with secure password
-- NOTE: Execute these commands as database root user if they don't exist

-- Create user (if not exists)
CREATE USER IF NOT EXISTS 'rentflow_team'@'localhost' IDENTIFIED BY 'rentflow_3006';

-- Grant specific permissions (principle of least privilege)
-- Revoke all first to ensure clean state
REVOKE ALL PRIVILEGES ON rentflow.* FROM 'rentflow_team'@'localhost';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER 
ON `rentflow`.* TO 'rentflow_team'@'localhost';

-- For stored procedures (if needed in future)
GRANT EXECUTE ON `rentflow`.* TO 'rentflow_team'@'localhost';

-- Apply permissions
FLUSH PRIVILEGES;

-- ============================================================================
-- STEP 2: DATABASE SECURITY SETTINGS
-- ============================================================================
-- Set strict SQL mode for data integrity
SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Use utf8mb4 character set (supports emojis and special characters securely)
SET NAMES utf8mb4;

-- ============================================================================
-- STEP 3: INITIAL SEED DATA
-- ============================================================================

-- Insert default admin user
INSERT INTO `users` 
(`tenant_id`, `role`, `email`, `first_name`, `last_name`, `password_hash`, `status`, `confirmed`, 
 `cover_photo`, `profile_photo`, `business_name`, `location`, `two_factor_enabled`, `remember_device_enabled`, 
 `password_reset_otp`, `password_reset_expires`, `password_reset_requested_at`, 
 `notif_email`, `notif_sms`)
VALUES
(NULL, 'admin', 'admin@rentflow.local', 'Admin', 'User', '$2y$10$examplehash', 'active', 1, 
 NULL, NULL, NULL, 'Baliwag Public Market', 0, 0, NULL, NULL, NULL, 1, 0);
