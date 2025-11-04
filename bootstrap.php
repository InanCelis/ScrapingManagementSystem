<?php
/**
 * Bootstrap File - Initializes the application
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

// Define URL paths
define('BASE_URL', '/ScrapingToolsAutoSync');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', PUBLIC_URL . '/assets');

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
