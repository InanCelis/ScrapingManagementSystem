<?php
/**
 * Setup Checker & Directory Creator
 * Ensures all required directories and files are in place
 */

$basePath = dirname(__DIR__);
$errors = [];
$warnings = [];
$success = [];

// Required directories
$directories = [
    'temp' => $basePath . '/temp',
    'logs' => $basePath . '/logs',
    'ScrapeFile' => $basePath . '/ScrapeFile',
    'Executable' => $basePath . '/Executable',
    'ExecutableXML' => $basePath . '/ExecutableXML',
    'database/migrations' => $basePath . '/database/migrations',
    'config' => $basePath . '/config'
];

// Required files
$files = [
    'config/config.php' => $basePath . '/config/config.php',
    'database/schema.sql' => $basePath . '/database/schema.sql',
    'core/Database.php' => $basePath . '/core/Database.php',
    'core/Auth.php' => $basePath . '/core/Auth.php',
    'core/ScraperManager.php' => $basePath . '/core/ScraperManager.php',
    'core/ScraperLogger.php' => $basePath . '/core/ScraperLogger.php',
    'core/ScraperAdapter.php' => $basePath . '/core/ScraperAdapter.php'
];

echo "Checking system setup...\n\n";

// Check and create directories
echo "=== Checking Directories ===\n";
foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            $success[] = "✓ Created directory: {$name}";
            echo "✓ Created: {$name}\n";
        } else {
            $errors[] = "✗ Failed to create directory: {$name}";
            echo "✗ Failed: {$name}\n";
        }
    } else {
        if (is_writable($path)) {
            $success[] = "✓ Directory exists and is writable: {$name}";
            echo "✓ OK: {$name}\n";
        } else {
            $warnings[] = "⚠ Directory exists but may not be writable: {$name}";
            echo "⚠ Check permissions: {$name}\n";
        }
    }
}

echo "\n=== Checking Required Files ===\n";
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $success[] = "✓ File exists: {$name}";
        echo "✓ OK: {$name}\n";
    } else {
        $errors[] = "✗ Missing file: {$name}";
        echo "✗ Missing: {$name}\n";
    }
}

// Check database connection
echo "\n=== Checking Database Connection ===\n";
$configFile = $basePath . '/config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    $dbConfig = $config['database'];

    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

        $success[] = "✓ Database connection successful";
        echo "✓ Connected to database: {$dbConfig['database']}\n";

        // Check if tables exist
        $tables = ['users', 'scraper_configs', 'scraper_processes', 'scraper_logs', 'activity_logs'];
        $missingTables = [];

        foreach ($tables as $table) {
            $result = $pdo->query("SHOW TABLES LIKE '{$table}'")->fetch();
            if (!$result) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $success[] = "✓ All required database tables exist";
            echo "✓ All required tables exist\n";
        } else {
            $warnings[] = "⚠ Missing tables: " . implode(', ', $missingTables);
            echo "⚠ Missing tables: " . implode(', ', $missingTables) . "\n";
            echo "  Run: php utils/run-migrations.php\n";
        }

        // Check if last_login column exists
        $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'")->fetch();
        if (!$columns) {
            $warnings[] = "⚠ users.last_login column missing - run migration";
            echo "⚠ users.last_login column missing\n";
            echo "  Visit: http://localhost/ScrapingToolsAutoSync/utils/migrate.php\n";
        } else {
            $success[] = "✓ users.last_login column exists";
            echo "✓ users.last_login column exists\n";
        }

    } catch (PDOException $e) {
        $errors[] = "✗ Database connection failed: " . $e->getMessage();
        echo "✗ Database error: " . $e->getMessage() . "\n";
    }
} else {
    $errors[] = "✗ Config file not found";
    echo "✗ Config file not found\n";
}

// Check PHP extensions
echo "\n=== Checking PHP Extensions ===\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "✓ PHP extension loaded: {$ext}";
        echo "✓ OK: {$ext}\n";
    } else {
        $errors[] = "✗ Missing PHP extension: {$ext}";
        echo "✗ Missing: {$ext}\n";
    }
}

// Check file permissions
echo "\n=== Checking File Permissions ===\n";
$writableDirs = ['temp', 'logs', 'ScrapeFile', 'config'];
foreach ($writableDirs as $dir) {
    $path = $basePath . '/' . $dir;
    if (is_dir($path) && is_writable($path)) {
        $success[] = "✓ Directory writable: {$dir}";
        echo "✓ Writable: {$dir}\n";
    } else {
        $warnings[] = "⚠ Directory may not be writable: {$dir}";
        echo "⚠ Check permissions: {$dir}\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Success: " . count($success) . "\n";
echo "⚠ Warnings: " . count($warnings) . "\n";
echo "✗ Errors: " . count($errors) . "\n";

if (empty($errors)) {
    echo "\n✅ System is ready to use!\n";
    echo "\nNext steps:\n";
    echo "1. Visit: http://localhost/ScrapingToolsAutoSync/views/login.php\n";
    echo "2. Login with: admin / admin123\n";
    echo "3. Create a scraper configuration\n";
    echo "4. Start scraping!\n";
} else {
    echo "\n❌ Please fix the errors above before proceeding.\n";
}

echo "\n";
