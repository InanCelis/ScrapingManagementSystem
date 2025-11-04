<?php
/**
 * Setup Helper Page
 * This page helps verify the installation and setup
 */

$errors = [];
$warnings = [];
$success = [];

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $success[] = 'PHP version ' . PHP_VERSION . ' is supported';
} else {
    $errors[] = 'PHP version must be 7.4 or higher. Current: ' . PHP_VERSION;
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "Extension '$ext' is loaded";
    } else {
        $errors[] = "Extension '$ext' is required but not loaded";
    }
}

// Check config file
$configFile = __DIR__ . '/config/config.php';
if (file_exists($configFile)) {
    $success[] = 'Configuration file exists';

    // Try to load config
    try {
        $config = require $configFile;
        $success[] = 'Configuration file is valid';

        // Test database connection
        try {
            require_once __DIR__ . '/core/Database.php';

            // Suppress errors during connection attempt
            $db = @Database::getInstance();

            if ($db) {
                try {
                    $connection = @$db->getConnection();
                    $success[] = 'Database connection successful';

                    // Check if tables exist
                    $tables = ['users', 'scraper_configs', 'scraper_processes', 'scraper_logs', 'activity_logs', 'user_sessions'];
                    $existingTables = [];

                    foreach ($tables as $table) {
                        try {
                            $result = @$db->query("SELECT 1 FROM $table LIMIT 1");
                            $existingTables[] = $table;
                        } catch (Exception $e) {
                            $errors[] = "Table '$table' does not exist";
                        }
                    }

                    if (count($existingTables) === count($tables)) {
                        $success[] = 'All required database tables exist';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Database connection failed: ' . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

    } catch (Exception $e) {
        $errors[] = 'Configuration file has errors: ' . $e->getMessage();
    }
} else {
    $errors[] = 'Configuration file not found';
}

// Check directories
$directories = ['logs', 'temp', 'uploads', 'ScrapeFile', 'Executable', 'ExecutableXML'];
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            $success[] = "Directory '$dir' exists and is writable";
        } else {
            $warnings[] = "Directory '$dir' exists but is not writable";
        }
    } else {
        $warnings[] = "Directory '$dir' does not exist (will be created automatically)";
    }
}

// Check vendor directory
if (is_dir(__DIR__ . '/vendor')) {
    $success[] = 'Composer dependencies are installed';
} else {
    $warnings[] = 'Composer dependencies not found. Run: composer install';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Verification - Scraping Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem; }
        .setup-card { max-width: 800px; margin: 0 auto; }
        .check-item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .check-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card setup-card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Setup Verification
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Errors Found</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($warnings)): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Warnings</h5>
                        <ul class="mb-0">
                            <?php foreach ($warnings as $warning): ?>
                                <li><?php echo htmlspecialchars($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Successful Checks</h5>
                    <ul class="mb-0">
                        <?php foreach ($success as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <hr>

                <h5>Setup Status</h5>
                <div class="progress mb-3" style="height: 30px;">
                    <?php
                    $totalChecks = count($errors) + count($warnings) + count($success);
                    $successRate = $totalChecks > 0 ? round((count($success) / $totalChecks) * 100) : 0;
                    $color = $successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger');
                    ?>
                    <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar"
                         style="width: <?php echo $successRate; ?>%">
                        <?php echo $successRate; ?>% Complete
                    </div>
                </div>

                <?php if (empty($errors)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-thumbs-up me-2"></i>
                        <strong>Great! Your system is ready.</strong>
                        <p class="mb-0 mt-2">
                            You can now proceed to login and start using the system.
                        </p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-tools me-2"></i>
                        <strong>Please fix the errors above before proceeding.</strong>
                    </div>

                    <h5 class="mt-4">Quick Fixes</h5>
                    <div class="accordion" id="quickFixes">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fix1">
                                    Database Connection Issues
                                </button>
                            </h2>
                            <div id="fix1" class="accordion-collapse collapse show" data-bs-parent="#quickFixes">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Make sure MySQL is running in XAMPP Control Panel</li>
                                        <li>Open phpMyAdmin and create database: <code>scraper_management</code></li>
                                        <li>Import the file: <code>database/schema.sql</code></li>
                                        <li>Update <code>config/config.php</code> with correct database credentials</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fix2">
                                    Missing Tables
                                </button>
                            </h2>
                            <div id="fix2" class="accordion-collapse collapse" data-bs-parent="#quickFixes">
                                <div class="accordion-body">
                                    <p>Import the database schema:</p>
                                    <ol>
                                        <li>Open phpMyAdmin</li>
                                        <li>Select database <code>scraper_management</code></li>
                                        <li>Go to "Import" tab</li>
                                        <li>Choose file: <code>database/schema.sql</code></li>
                                        <li>Click "Go"</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <h5>Documentation</h5>
                <ul>
                    <li><a href="README.md" target="_blank">Full Documentation</a></li>
                    <li><a href="INSTALLATION.md" target="_blank">Installation Guide</a></li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
