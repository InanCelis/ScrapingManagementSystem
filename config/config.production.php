<?php
/**
 * Application Configuration File - PRODUCTION
 * Web Scraping Management System
 * Domain: scraper.staging-ptd.com
 *
 * INSTRUCTIONS:
 * 1. Rename this file to config.php after deployment
 * 2. Update database credentials from cPanel MySQL Databases section
 * 3. Update API token if needed
 * 4. Ensure debug is set to false in production
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'YOUR_CPANEL_USERNAME_scraperman_db',  // UPDATE: e.g., 'user123_scraperman_db'
        'username' => 'YOUR_CPANEL_USERNAME_scraperman_user', // UPDATE: e.g., 'user123_scraperman_user'
        'password' => 'YOUR_DATABASE_PASSWORD',               // UPDATE: From cPanel MySQL setup
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // Application Settings
    'app' => [
        'name' => 'Scraping Management System',
        'url' => 'https://scraper.staging-ptd.com',  // Your production domain
        'timezone' => 'UTC',
        'debug' => false,  // IMPORTANT: Must be false in production
        'env' => 'production',
    ],

    // Session Configuration
    'session' => [
        'lifetime' => 120, // minutes
        'cookie_name' => 'scraper_session',
        'secure' => true,  // IMPORTANT: true for HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // Authentication
    'auth' => [
        'remember_me_lifetime' => 30 * 24 * 60 * 60, // 30 days
        'password_min_length' => 8,  // Increased for production
        'max_login_attempts' => 5,
        'lockout_duration' => 15 * 60, // 15 minutes
    ],

    // Google OAuth (Optional - configure when implementing)
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'https://scraper.staging-ptd.com/auth/google/callback',
    ],

    // Scraper Settings
    'scraper' => [
        'max_concurrent_processes' => 5,
        'log_retention_days' => 30,
        'default_timeout' => 3600, // 1 hour
        'executable_path' => __DIR__ . '/../Executable',
        'executable_xml_path' => __DIR__ . '/../ExecutableXML',
        'scrape_file_path' => __DIR__ . '/../ScrapeFile',
    ],

    // API Settings
    'api' => [
        'base_domain' => 'https://internationalpropertyalerts.com',
        'endpoints' => [
            'properties' => '/wp-json/houzez/v1/properties',
            'links' => '/wp-json/houzez/v1/links-by-owner',
        ],
        'token' => 'eyJpYXQiOjE3NTk4NDI5OTYsImV4cCI6MTc2MDAxNTc5Nn0=',  // UPDATE if needed
        'max_retries' => 3,
        'timeout' => 600,
        'connect_timeout' => 60,
        'debug' => false,
    ],

    // Paths
    'paths' => [
        'root' => dirname(__DIR__),
        'logs' => dirname(__DIR__) . '/logs',
        'uploads' => dirname(__DIR__) . '/uploads',
        'temp' => dirname(__DIR__) . '/temp',
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'path' => dirname(__DIR__) . '/logs/app.log',
        'level' => 'info', // Production: 'info' or 'warning'
        'max_files' => 14, // Keep 2 weeks of logs
        'max_file_size' => 10485760, // 10MB
    ],

    // Email Configuration (Optional - for notifications)
    'email' => [
        'enabled' => false,
        'driver' => 'smtp', // smtp, sendmail, mail
        'host' => 'mail.scraper.staging-ptd.com',
        'port' => 587,
        'username' => 'noreply@scraper.staging-ptd.com',
        'password' => '',
        'encryption' => 'tls', // tls or ssl
        'from' => [
            'address' => 'noreply@scraper.staging-ptd.com',
            'name' => 'Scraping Management System',
        ],
    ],

    // Security Settings
    'security' => [
        'csrf_protection' => true,
        'rate_limiting' => true,
        'rate_limit_requests' => 60, // requests per minute
        'allowed_origins' => [
            'https://scraper.staging-ptd.com',
            'https://internationalpropertyalerts.com',
        ],
    ],

    // Backup Settings (for future implementation)
    'backup' => [
        'enabled' => false,
        'schedule' => 'daily', // daily, weekly
        'retention_days' => 7,
        'path' => dirname(__DIR__) . '/backups',
    ],
];
