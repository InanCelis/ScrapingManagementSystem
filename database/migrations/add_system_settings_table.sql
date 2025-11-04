-- Add system settings table for configurable application settings
-- Created: 2025-10-16

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    is_editable TINYINT(1) DEFAULT 1,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default API settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_editable) VALUES
('api_base_domain', 'https://internationalpropertyalerts.com', 'string', 'api', 'Base domain for API requests', 1),
('api_token', 'eyJpYXQiOjE3NTk4NDI5OTYsImV4cCI6MTc2MDAxNTc5Nn0=', 'string', 'api', 'API authentication token', 1),
('api_max_retries', '3', 'integer', 'api', 'Maximum number of retry attempts', 1),
('api_timeout', '600', 'integer', 'api', 'Request timeout in seconds', 1),
('api_connect_timeout', '60', 'integer', 'api', 'Connection timeout in seconds', 1),
('api_debug', '0', 'boolean', 'api', 'Enable debug mode for API requests', 1),
('api_properties_endpoint', '/wp-json/houzez/v1/properties', 'string', 'api', 'Properties endpoint path', 1),
('api_links_endpoint', '/wp-json/houzez/v1/links-by-owner', 'string', 'api', 'Links endpoint path', 1)
ON DUPLICATE KEY UPDATE id=id;
