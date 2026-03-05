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
