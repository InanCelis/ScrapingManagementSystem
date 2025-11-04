<?php
// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'scraper_management');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Application Settings
if (!defined('APP_NAME')) define('APP_NAME', 'Scraping Management System');
if (!defined('TIMEZONE')) define('TIMEZONE', 'UTC');
if (!defined('ITEMS_PER_PAGE')) define('ITEMS_PER_PAGE', 10);
if (!defined('ENABLE_NOTIFICATIONS')) define('ENABLE_NOTIFICATIONS', true);

// Scraper Settings
if (!defined('MAX_CONCURRENT_SCRAPERS')) define('MAX_CONCURRENT_SCRAPERS', 5);
if (!defined('DEFAULT_TIMEOUT')) define('DEFAULT_TIMEOUT', 3600);
if (!defined('LOG_RETENTION_DAYS')) define('LOG_RETENTION_DAYS', 30);

// Return configuration array for Database, Auth, and ScraperManager classes
return [
    'database' => [
        'host' => DB_HOST,
        'port' => 3306,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    'auth' => [
        'session_lifetime' => 3600, // 1 hour
        'remember_me_lifetime' => 604800, // 1 week
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],
    'scraper' => [
        'max_concurrent_scrapers' => MAX_CONCURRENT_SCRAPERS,
        'default_timeout' => DEFAULT_TIMEOUT,
        'log_retention_days' => LOG_RETENTION_DAYS,
        'scrapers_directory' => __DIR__ . '/../Executable',
        'xml_scrapers_directory' => __DIR__ . '/../ExecutableXML',
        'output_directory' => __DIR__ . '/../ScrapeFile',
        'log_directory' => __DIR__ . '/../logs',
    ]
];
