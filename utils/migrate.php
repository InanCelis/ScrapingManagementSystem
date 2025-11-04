<?php
/**
 * Web-based Database Migration Runner
 * Run this file in your browser to apply database migrations
 */

// For security, you may want to require authentication
// Uncomment the lines below to require login:
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     die('Please login first');
// }

require_once __DIR__ . '/../config/config.php';

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$output = [];
$success = true;

try {
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $output[] = "✓ Connected to database: {$dbConfig['database']}";

    // Get all migration files
    $migrationsDir = __DIR__ . '/../database/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0755, true);
        $output[] = "✓ Created migrations directory";
    }

    $migrationFiles = glob($migrationsDir . '/*.sql');

    if (empty($migrationFiles)) {
        $output[] = "⚠ No migration files found.";
    } else {
        $output[] = "✓ Found " . count($migrationFiles) . " migration file(s)";
        $output[] = "";

        // Run each migration
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            $output[] = "<strong>Running migration: {$filename}</strong>";

            $sql = file_get_contents($file);

            // Split by semicolons to handle multiple statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !str_starts_with($stmt, '--');
                }
            );

            foreach ($statements as $statement) {
                try {
                    $pdo->exec($statement);
                    $output[] = "  ✓ Executed: " . substr($statement, 0, 60) . "...";
                } catch (PDOException $e) {
                    // Check if error is "Duplicate column" - which means migration already ran
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        $output[] = "  ⚠ Column already exists (migration previously applied)";
                    } else {
                        throw $e;
                    }
                }
            }

            $output[] = "  <strong style='color: green;'>✓ Migration completed successfully</strong>";
            $output[] = "";
        }
    }

    $output[] = "<strong style='color: green; font-size: 1.2em;'>✓ All migrations completed successfully!</strong>";

} catch (PDOException $e) {
    $success = false;
    $output[] = "<strong style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</strong>";
} catch (Exception $e) {
    $success = false;
    $output[] = "<strong style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</strong>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .migration-box {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .output-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="migration-box">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-database"></i> Database Migration
                    </h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>Success!</strong> All migrations have been applied successfully.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>Error!</strong> Migration failed. Please check the output below.
                        </div>
                    <?php endif; ?>

                    <div class="output-box">
                        <?php echo implode("\n", $output); ?>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="/ScrapingToolsAutoSync/views/dashboard.php" class="btn btn-primary">
                            Go to Dashboard
                        </a>
                        <a href="/ScrapingToolsAutoSync/views/profile.php" class="btn btn-success">
                            Go to Profile
                        </a>
                        <button onclick="location.reload()" class="btn btn-secondary">
                            Run Again
                        </button>
                    </div>

                    <div class="mt-4 small text-muted">
                        <strong>Note:</strong> If migrations have already been applied, you'll see warnings about duplicate columns. This is normal and safe to ignore.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
