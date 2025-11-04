<?php
/**
 * Scraper Manager Class
 * Manages scraping configurations and processes
 */

class ScraperManager {
    private Database $db;
    private array $config;

    public function __construct() {
        $this->db = Database::getInstance();
        $configFile = __DIR__ . '/../config/config.php';
        $appConfig = require $configFile;
        $this->config = $appConfig['scraper'];
    }

    // ==================== Configuration Management ====================

    public function createConfig(array $data): array {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['type'])) {
                return ['success' => false, 'message' => 'Name and type are required'];
            }

            // Validate type-specific fields
            if ($data['type'] === 'website') {
                if (empty($data['website_url']) || empty($data['url_pattern'])) {
                    return ['success' => false, 'message' => 'Website URL and URL pattern are required'];
                }
            } elseif ($data['type'] === 'xml') {
                if (empty($data['xml_link'])) {
                    return ['success' => false, 'message' => 'XML link is required'];
                }
            }

            $configId = $this->db->insert('scraper_configs', [
                'name' => $data['name'],
                'type' => $data['type'],
                'website_url' => $data['website_url'] ?? null,
                'url_pattern' => $data['url_pattern'] ?? null,
                'count_of_pages' => $data['count_of_pages'] ?? null,
                'start_page' => $data['start_page'] ?? 1,
                'end_page' => $data['end_page'] ?? null,
                'xml_link' => $data['xml_link'] ?? null,
                'count_of_properties' => $data['count_of_properties'] ?? null,
                'enable_upload' => $data['enable_upload'] ?? 1,
                'testing_mode' => $data['testing_mode'] ?? 0,
                'folder_name' => $data['folder_name'] ?? null,
                'filename' => $data['filename'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'owned_by' => $data['owned_by'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'listing_id_prefix' => $data['listing_id_prefix'] ?? null,
                'status' => 'active',
                'created_by' => $_SESSION['user_id'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Configuration created successfully',
                'id' => $configId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create configuration: ' . $e->getMessage()];
        }
    }

    public function updateConfig(int $id, array $data): array {
        try {
            unset($data['created_by'], $data['created_at']); // Prevent overwriting

            $affected = $this->db->update('scraper_configs', $data, 'id = ?', [$id]);

            return [
                'success' => $affected > 0,
                'message' => $affected > 0 ? 'Configuration updated successfully' : 'No changes made'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update configuration: ' . $e->getMessage()];
        }
    }

    public function deleteConfig(int $id): array {
        try {
            // Check if there are active processes
            $activeProcesses = $this->db->fetchOne(
                'SELECT COUNT(*) as count FROM scraper_processes WHERE config_id = ? AND status IN ("running", "pending")',
                [$id]
            );

            if ($activeProcesses['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete configuration with active processes. Stop the running scraper first.'];
            }

            // Delete related processes first (if any)
            $this->db->delete('scraper_processes', 'config_id = ?', [$id]);

            // Delete the configuration
            $affected = $this->db->delete('scraper_configs', 'id = ?', [$id]);

            return [
                'success' => $affected > 0,
                'message' => $affected > 0 ? 'Configuration deleted successfully' : 'Configuration not found'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete configuration: ' . $e->getMessage()];
        }
    }

    public function getConfig(int $id): ?array {
        return $this->db->fetchOne('SELECT * FROM scraper_configs WHERE id = ?', [$id]);
    }

    public function getAllConfigs(array $filters = []): array {
        $sql = 'SELECT * FROM scraper_configs WHERE 1=1';
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= ' AND type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (name LIKE ? OR folder_name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->db->fetchAll($sql, $params);
    }

    public function duplicateConfig(int $id): array {
        try {
            $original = $this->getConfig($id);
            if (!$original) {
                return ['success' => false, 'message' => 'Configuration not found'];
            }

            unset($original['id'], $original['created_at'], $original['updated_at'], $original['last_run_at']);
            $original['name'] = $original['name'] . ' (Copy)';
            $original['status'] = 'inactive';

            return $this->createConfig($original);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to duplicate configuration: ' . $e->getMessage()];
        }
    }

    // ==================== Process Management ====================

    public function startScraper(int $configId): array {
        try {
            $config = $this->getConfig($configId);
            if (!$config) {
                return ['success' => false, 'message' => 'Configuration not found'];
            }

            // Check if already running
            $running = $this->db->fetchOne(
                'SELECT id FROM scraper_processes WHERE config_id = ? AND status = "running"',
                [$configId]
            );

            if ($running) {
                return ['success' => false, 'message' => 'Scraper is already running'];
            }

            // Create process record
            $processId = $this->db->insert('scraper_processes', [
                'config_id' => $configId,
                'status' => 'pending',
                'total_items' => $config['type'] === 'website' ? $config['count_of_pages'] : $config['count_of_properties'],
                'started_at' => date('Y-m-d H:i:s'),
            ]);

            // Start background process
            $result = $this->executeScraperBackground($processId, $config);

            if ($result['success']) {
                $this->db->update('scraper_processes', [
                    'status' => 'running',
                    'process_id' => $result['pid']
                ], 'id = ?', [$processId]);

                $this->db->update('scraper_configs', [
                    'last_run_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$configId]);
            }

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'process_id' => $processId
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to start scraper: ' . $e->getMessage()];
        }
    }

    private function executeScraperBackground(int $processId, array $config): array {
        try {
            // Ensure directories exist
            $tempDir = __DIR__ . '/../temp';
            $logsDir = __DIR__ . '/../logs';

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }

            // Create a PHP script to run the scraper
            $scriptPath = $tempDir . '/scraper_' . $processId . '.php';
            $logPath = $logsDir . '/scraper_' . $processId . '.log';
            $pidFile = $tempDir . '/scraper_' . $processId . '.pid';

            $scriptContent = $this->generateScraperScript($processId, $config);
            file_put_contents($scriptPath, $scriptContent);

            // Execute in background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - Get real PID using WMIC
                $batScript = $tempDir . '/scraper_' . $processId . '.bat';
                $batContent = "@echo off\r\n";
                $batContent .= "start /B php \"$scriptPath\" > \"$logPath\" 2>&1\r\n";
                $batContent .= "for /f \"tokens=2\" %%a in ('wmic process where \"commandline like '%%scraper_$processId.php%%' and name='php.exe'\" get processid /format:list ^| find \"ProcessId\"') do echo %%a > \"$pidFile\"\r\n";
                file_put_contents($batScript, $batContent);

                // Execute batch script
                pclose(popen("\"$batScript\"", 'r'));

                // Wait a moment for PID file to be created
                sleep(2);

                // Read PID from file
                if (file_exists($pidFile)) {
                    $pid = trim(file_get_contents($pidFile));
                } else {
                    $pid = 'win_' . $processId; // Fallback to process ID
                }
            } else {
                // Linux/Unix
                $command = "php \"$scriptPath\" > \"$logPath\" 2>&1 & echo $!";
                $pid = shell_exec($command);
            }

            return [
                'success' => true,
                'message' => 'Scraper started successfully',
                'pid' => trim($pid)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to execute scraper: ' . $e->getMessage()];
        }
    }

    private function generateScraperScript(int $processId, array $config): string {
        // Serialize config for passing to adapter
        // Use base64 encoding to avoid issues with special characters and quotes
        $configJson = json_encode($config, JSON_UNESCAPED_SLASHES);
        $configBase64 = base64_encode($configJson);

        $script = <<<'PHPCODE'
<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('CONFIGDATA');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(PROCESSID, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(PROCESSID);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}
PHPCODE;

        // Replace placeholders
        $script = str_replace('CONFIGDATA', $configBase64, $script);
        $script = str_replace('PROCESSID', $processId, $script);

        return $script;
    }

    public function stopScraper(int $processId): array {
        try {
            $process = $this->getProcess($processId);
            if (!$process) {
                return ['success' => false, 'message' => 'Process not found'];
            }

            $tempDir = __DIR__ . '/../temp';
            $killed = false;

            // Kill the process
            if ($process['process_id']) {
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows - Use taskkill with specific PID
                    $pid = $process['process_id'];

                    // If it's not a numeric PID, try to find the process by script name
                    if (!is_numeric($pid)) {
                        // Try to read from PID file
                        $pidFile = $tempDir . '/scraper_' . $processId . '.pid';
                        if (file_exists($pidFile)) {
                            $pid = trim(file_get_contents($pidFile));
                        }
                    }

                    if (is_numeric($pid)) {
                        // Verify process exists before killing
                        exec("tasklist /FI \"PID eq $pid\" 2>&1", $tasklistOutput);
                        $processExists = false;
                        foreach ($tasklistOutput as $line) {
                            if (strpos($line, (string)$pid) !== false) {
                                $processExists = true;
                                break;
                            }
                        }

                        if ($processExists) {
                            exec("taskkill /F /PID $pid 2>&1", $output, $returnCode);
                            $killed = ($returnCode === 0);
                        } else {
                            $killed = true; // Process already dead
                        }
                    }
                } else {
                    // Linux/Unix
                    exec("kill -9 {$process['process_id']} 2>&1", $output, $returnCode);
                    $killed = ($returnCode === 0);
                }
            }

            // Clean up temporary files
            $scriptPath = $tempDir . '/scraper_' . $processId . '.php';
            $batScript = $tempDir . '/scraper_' . $processId . '.bat';
            $pidFile = $tempDir . '/scraper_' . $processId . '.pid';

            if (file_exists($scriptPath)) @unlink($scriptPath);
            if (file_exists($batScript)) @unlink($batScript);
            if (file_exists($pidFile)) @unlink($pidFile);

            // Update process status
            $this->db->update('scraper_processes', [
                'status' => 'stopped',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$processId]);

            return ['success' => true, 'message' => 'Scraper stopped successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to stop scraper: ' . $e->getMessage()];
        }
    }

    public function getProcess(int $id): ?array {
        return $this->db->fetchOne('SELECT * FROM scraper_processes WHERE id = ?', [$id]);
    }

    public function getProcessesByConfig(int $configId, int $limit = 10): array {
        return $this->db->fetchAll(
            'SELECT * FROM scraper_processes WHERE config_id = ? ORDER BY created_at DESC LIMIT ?',
            [$configId, $limit]
        );
    }

    public function getRunningProcesses(): array {
        $sql = "
            SELECT sp.*, sc.name as config_name, sc.type
            FROM scraper_processes sp
            JOIN scraper_configs sc ON sp.config_id = sc.id
            WHERE sp.status = 'running'
            ORDER BY sp.started_at DESC
        ";
        return $this->db->fetchAll($sql);
    }

    public function getAllProcesses(int $limit = 50): array {
        $sql = "
            SELECT sp.*, sc.name as config_name, sc.type
            FROM scraper_processes sp
            JOIN scraper_configs sc ON sp.config_id = sc.id
            ORDER BY sp.created_at DESC
            LIMIT ?
        ";
        return $this->db->fetchAll($sql, [$limit]);
    }

    // ==================== Statistics ====================

    public function getDashboardStats(): array {
        $stats = [];

        // Total configurations
        $result = $this->db->fetchOne('SELECT COUNT(*) as count FROM scraper_configs WHERE status = "active"');
        $stats['total_configs'] = $result['count'];

        // Running processes
        $result = $this->db->fetchOne('SELECT COUNT(*) as count FROM scraper_processes WHERE status = "running"');
        $stats['running_processes'] = $result['count'];

        // Properties scraped today
        $result = $this->db->fetchOne('
            SELECT SUM(items_created + items_updated) as count
            FROM scraper_processes
            WHERE DATE(started_at) = CURDATE()
        ');
        $stats['properties_today'] = $result['count'] ?? 0;

        // Failed scrapes today
        $result = $this->db->fetchOne('
            SELECT COUNT(*) as count
            FROM scraper_processes
            WHERE status = "failed" AND DATE(started_at) = CURDATE()
        ');
        $stats['failed_today'] = $result['count'];

        // Success rate
        $result = $this->db->fetchOne('
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            FROM scraper_processes
            WHERE DATE(started_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ');
        $stats['success_rate'] = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100, 2) : 0;

        return $stats;
    }

    public function getRecentActivity(int $limit = 10): array {
        $sql = "
            SELECT sp.*, sc.name as config_name, sc.type
            FROM scraper_processes sp
            JOIN scraper_configs sc ON sp.config_id = sc.id
            ORDER BY sp.created_at DESC
            LIMIT ?
        ";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getWeeklyActivity(): array {
        // Get activity for last 7 days
        $sql = "
            SELECT
                DATE(started_at) as date,
                SUM(items_created + items_updated) as total
            FROM scraper_processes
            WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(started_at)
            ORDER BY date ASC
        ";

        $results = $this->db->fetchAll($sql);

        // Create array for all 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));
            $data[$dayName] = 0;
        }

        // Fill in actual data
        foreach ($results as $row) {
            $dayName = date('D', strtotime($row['date']));
            $data[$dayName] = (int)$row['total'];
        }

        return $data;
    }

    public function getStatusDistribution(): array {
        $sql = "
            SELECT
                status,
                COUNT(*) as count
            FROM scraper_processes
            GROUP BY status
        ";

        $results = $this->db->fetchAll($sql);

        $data = [
            'completed' => 0,
            'running' => 0,
            'failed' => 0,
            'stopped' => 0
        ];

        foreach ($results as $row) {
            if (isset($data[$row['status']])) {
                $data[$row['status']] = (int)$row['count'];
            }
        }

        return $data;
    }
}
