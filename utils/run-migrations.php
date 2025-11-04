<?php
/**
 * Database Migration Runner
 * Runs pending database migrations
 */

require_once __DIR__ . '/../config/config.php';

// Get database configuration
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

try {
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    echo "Connected to database: {$dbConfig['database']}\n\n";

    // Get all migration files
    $migrationsDir = __DIR__ . '/../database/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0755, true);
        echo "Created migrations directory\n";
    }

    $migrationFiles = glob($migrationsDir . '/*.sql');

    if (empty($migrationFiles)) {
        echo "No migration files found.\n";
        exit(0);
    }

    echo "Found " . count($migrationFiles) . " migration file(s):\n\n";

    // Run each migration
    foreach ($migrationFiles as $file) {
        $filename = basename($file);
        echo "Running migration: {$filename}...\n";

        $sql = file_get_contents($file);

        // Split by semicolons to handle multiple statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                // Filter out empty statements and comments
                return !empty($stmt) && !str_starts_with($stmt, '--');
            }
        );

        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Check if error is "Duplicate column" - which means migration already ran
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "  ⚠ Column already exists (migration previously applied)\n";
                } else {
                    throw $e;
                }
            }
        }

        echo "  ✓ Migration completed successfully\n\n";
    }

    echo "All migrations completed!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
