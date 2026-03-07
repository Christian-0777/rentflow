-- migration.sql
-- Add due_id column to payments so that each payment (including status markers) can be linked to the
-- specific due that triggered it.  This makes it possible to show the correct amount when a due is
-- explicitly marked as not paid and to avoid accidentally counting those placeholder records as real
-- collections.

ALTER TABLE payments
    ADD COLUMN due_id INT(11) NULL AFTER lease_id,
    ADD INDEX idx_payments_due (due_id),
    ADD CONSTRAINT fk_payments_due FOREIGN KEY (due_id) REFERENCES dues(id);

-- No data transformation is strictly necessary; existing rows will have NULL due_id.
-- Future code ensures the field is populated whenever a payment record is created from a due.

-- Remove bank methods from payments table as project doesn't handle physical/digital money
ALTER TABLE payments MODIFY COLUMN method ENUM('cash','manual','partial') NOT NULL;

-- Create tenant_accounts table for new tenant login system
CREATE TABLE tenant_accounts (
  id INT(11) NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL UNIQUE,
  code_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remove password-related columns from users table for tenants (keep for admins)
-- But since role is in users, we can't easily drop columns. Instead, we'll ignore password for tenants in code.
-- For new tenants, password_hash will be NULL.

-- Remove 2FA and trusted devices tables as they are no longer needed for tenants
DROP TABLE IF EXISTS trusted_devices;
DROP TABLE IF EXISTS password_resets;
