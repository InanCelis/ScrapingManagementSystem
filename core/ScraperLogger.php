<?php
/**
 * Scraper Logger Class
 * Logs scraper process output to database in real-time
 */

class ScraperLogger {
    private Database $db;
    private int $processId;

    public function __construct(int $processId) {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance();
        $this->processId = $processId;
    }

    public function log(string $message, string $level = 'info'): void {
        try {
            $this->db->insert('scraper_logs', [
                'process_id' => $this->processId,
                'log_level' => $level,
                'message' => $message,
            ]);

            // Also output to console
            echo date('[Y-m-d H:i:s]') . " [$level] $message\n";
        } catch (Exception $e) {
            echo "Failed to log: " . $e->getMessage() . "\n";
        }
    }

    public function info(string $message): void {
        $this->log($message, 'info');
    }

    public function success(string $message): void {
        $this->log($message, 'success');
    }

    public function warning(string $message): void {
        $this->log($message, 'warning');
    }

    public function error(string $message): void {
        $this->log($message, 'error');
        $this->updateProcessStatus('failed', $message);
    }

    public function debug(string $message): void {
        $this->log($message, 'debug');
    }

    public function updateProgress(int $itemsScraped, int $itemsCreated, int $itemsUpdated): void {
        try {
            $this->db->update('scraper_processes', [
                'items_scraped' => $itemsScraped,
                'items_created' => $itemsCreated,
                'items_updated' => $itemsUpdated,
            ], 'id = ?', [$this->processId]);
        } catch (Exception $e) {
            echo "Failed to update progress: " . $e->getMessage() . "\n";
        }
    }

    public function complete(): void {
        $this->updateProcessStatus('completed');
        $this->log('Scraping completed successfully', 'success');
    }

    private function updateProcessStatus(string $status, string $errorMessage = null): void {
        try {
            $data = [
                'status' => $status,
                'completed_at' => date('Y-m-d H:i:s'),
            ];

            if ($errorMessage) {
                $data['error_message'] = $errorMessage;
                $data['last_error_at'] = date('Y-m-d H:i:s');
            }

            // Calculate duration
            $process = $this->db->fetchOne('SELECT started_at FROM scraper_processes WHERE id = ?', [$this->processId]);
            if ($process && $process['started_at']) {
                $start = strtotime($process['started_at']);
                $end = time();
                $data['duration'] = $end - $start;
            }

            $this->db->update('scraper_processes', $data, 'id = ?', [$this->processId]);
        } catch (Exception $e) {
            echo "Failed to update process status: " . $e->getMessage() . "\n";
        }
    }
}
