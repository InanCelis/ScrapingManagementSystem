<?php
/**
 * Simple Setup Check - Shows detailed error information
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>System Check</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 2rem; background: #f5f5f5; }
        .status-ok { color: green; }
        .status-error { color: red; }
        .status-warning { color: orange; }
    </style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3>Installation Check</h3>
        </div>
        <div class='card-body'>";

// PHP Version
echo "<h5>1. PHP Version</h5>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<p class='status-ok'>✓ PHP " . PHP_VERSION . " (OK)</p>";
} else {
    echo "<p class='status-error'>✗ PHP " . PHP_VERSION . " - Need 7.4+ (ERROR)</p>";
}

// Extensions
echo "<h5>2. Required Extensions</h5>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='status-ok'>✓ $ext loaded</p>";
    } else {
        echo "<p class='status-error'>✗ $ext NOT loaded</p>";
    }
}

// Config file
echo "<h5>3. Configuration File</h5>";
if (file_exists(__DIR__ . '/config/config.php')) {
    echo "<p class='status-ok'>✓ config/config.php exists</p>";

    try {
        $config = require __DIR__ . '/config/config.php';
        echo "<p class='status-ok'>✓ Configuration file is valid</p>";

        echo "<h5>4. Database Connection</h5>";
        echo "<p>Database: " . htmlspecialchars($config['database']['database']) . "</p>";
        echo "<p>Host: " . htmlspecialchars($config['database']['host']) . "</p>";
        echo "<p>Username: " . htmlspecialchars($config['database']['username']) . "</p>";

        try {
            $dsn = "mysql:host={$config['database']['host']};charset={$config['database']['charset']}";
            $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
            echo "<p class='status-ok'>✓ Can connect to MySQL server</p>";

            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']['database']}'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='status-ok'>✓ Database '{$config['database']['database']}' exists</p>";

                // Connect to database
                $dsn2 = "mysql:host={$config['database']['host']};dbname={$config['database']['database']};charset={$config['database']['charset']}";
                $pdo2 = new PDO($dsn2, $config['database']['username'], $config['database']['password']);

                // Check tables
                echo "<h5>5. Database Tables</h5>";
                $tables = ['users', 'scraper_configs', 'scraper_processes', 'scraper_logs', 'activity_logs', 'user_sessions'];
                foreach ($tables as $table) {
                    $stmt = $pdo2->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        echo "<p class='status-ok'>✓ Table '$table' exists</p>";
                    } else {
                        echo "<p class='status-error'>✗ Table '$table' NOT found</p>";
                    }
                }

            } else {
                echo "<p class='status-error'>✗ Database '{$config['database']['database']}' does NOT exist</p>";
                echo "<div class='alert alert-warning mt-3'>
                    <h6>Action Required:</h6>
                    <ol>
                        <li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>
                        <li>Click 'New' to create a database</li>
                        <li>Name: <strong>scraper_management</strong></li>
                        <li>Collation: <strong>utf8mb4_unicode_ci</strong></li>
                        <li>Click 'Create'</li>
                        <li>Select the database, go to 'Import' tab</li>
                        <li>Choose file: <strong>database/schema.sql</strong></li>
                        <li>Click 'Go'</li>
                        <li>Refresh this page</li>
                    </ol>
                </div>";
            }

        } catch (PDOException $e) {
            echo "<p class='status-error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<div class='alert alert-danger mt-3'>
                <h6>Possible fixes:</h6>
                <ul>
                    <li>Make sure MySQL is running in XAMPP Control Panel</li>
                    <li>Check username/password in config/config.php</li>
                </ul>
            </div>";
        }

    } catch (Exception $e) {
        echo "<p class='status-error'>✗ Config error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='status-error'>✗ config/config.php NOT found</p>";
}

// Directories
echo "<h5>6. Directories</h5>";
$dirs = ['logs', 'temp', 'uploads', 'ScrapeFile', 'Executable', 'ExecutableXML'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<p class='status-ok'>✓ $dir/ (writable)</p>";
        } else {
            echo "<p class='status-warning'>⚠ $dir/ (exists but not writable)</p>";
        }
    } else {
        echo "<p class='status-warning'>⚠ $dir/ (will be created automatically)</p>";
    }
}

echo "<hr>
<div class='d-flex gap-2'>
    <a href='login.php' class='btn btn-primary'>Go to Login</a>
    <a href='setup.php' class='btn btn-secondary'>Advanced Check</a>
    <button onclick='location.reload()' class='btn btn-info'>Refresh</button>
</div>

        </div>
    </div>
</div>
</body>
</html>";
?>
