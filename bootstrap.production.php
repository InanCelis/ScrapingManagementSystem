<?php
/**
 * Bootstrap File - Initializes the application
 * PRODUCTION VERSION for scraper.staging-ptd.com
 * Include this at the top of every page
 */

// Define base paths
define('ROOT_PATH', __DIR__);
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('API_PATH', ROOT_PATH . '/api');
define('UTILS_PATH', ROOT_PATH . '/utils');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Define URL paths for production
// If deploying to root domain (https://scraper.staging-ptd.com/)
define('BASE_URL', '');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', PUBLIC_URL . '/assets');

// If deploying to subdirectory (https://scraper.staging-ptd.com/scraper/)
// Uncomment and modify these instead:
// define('BASE_URL', '/scraper');
// define('PUBLIC_URL', BASE_URL . '/public');
// define('ASSETS_URL', PUBLIC_URL . '/assets');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-load core classes
spl_autoload_register(function ($class) {
    $file = CORE_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Production error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');
error_reporting(E_ALL);
