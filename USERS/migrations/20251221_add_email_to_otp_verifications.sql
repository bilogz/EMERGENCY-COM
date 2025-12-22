-- Migration: Add email column to otp_verifications for email-based OTPs
-- Run this as a one-time migration (e.g., via phpMyAdmin or mysql CLI)

ALTER TABLE otp_verifications
  ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL COMMENT 'Email to verify' AFTER phone;

-- Create index on email for faster lookup
CREATE INDEX IF NOT EXISTS idx_email ON otp_verifications (email);

-- Optional: update any existing rows to populate email if you have a mapping
-- UPDATE otp_verifications o
-- JOIN users u ON REPLACE(REPLACE(REPLACE(REPLACE(u.phone, ' ', ''), '-', ''), '(', ''), ')', '') = REPLACE(REPLACE(REPLACE(REPLACE(o.phone, ' ', ''), '-', ''), '(', ''), ')', '')
-- SET o.email = u.email
-- WHERE o.email IS NULL AND u.email IS NOT NULL;

-- Note: Some MySQL versions may not support 'IF NOT EXISTS' in ADD COLUMN; if so, check first with SHOW COLUMNS and add the column manually.
