-- Migration: Add last_login column to users table
-- Created: 2025-10-15

ALTER TABLE users
ADD COLUMN last_login TIMESTAMP NULL AFTER is_active;

-- Create index for last_login
CREATE INDEX idx_last_login ON users(last_login);
