<?php
/**
 * Front Controller - Routes all requests
 * Web Scraping Management System
 */

// Get the requested page
$page = $_GET['page'] ?? 'welcome';

// Define allowed pages
$allowedPages = [
    'welcome' => '../views/welcome.php',
    'login' => '../views/login.php',
    'logout' => '../views/logout.php',
    'dashboard' => '../views/dashboard.php',
    'running-tools' => '../views/running-tools.php',
    'configurations' => '../views/configurations.php',
    'configuration-form' => '../views/configuration-form.php',
    'activity-log' => '../views/activity-log.php',
];

// Route to the correct page
if (array_key_exists($page, $allowedPages) && file_exists(__DIR__ . '/' . $allowedPages[$page])) {
    require_once __DIR__ . '/' . $allowedPages[$page];
} else {
    // Page not found - redirect to welcome
    header('Location: ?page=welcome');
    exit;
}
