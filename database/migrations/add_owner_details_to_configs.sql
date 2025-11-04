-- Add owner details and listing ID prefix to scraper configurations
-- Created: 2025-10-16

ALTER TABLE scraper_configs
ADD COLUMN owned_by VARCHAR(200) NULL AFTER file_path,
ADD COLUMN contact_person VARCHAR(200) NULL AFTER owned_by,
ADD COLUMN phone VARCHAR(50) NULL AFTER contact_person,
ADD COLUMN email VARCHAR(100) NULL AFTER phone,
ADD COLUMN listing_id_prefix VARCHAR(20) NULL AFTER email;
