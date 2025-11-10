<?php
/**
 * Scraper Adapter
 * Bridges old scraper classes with new configuration system
 */

class ScraperAdapter {
    private $scraper;
    private array $config;
    private ScraperLogger $logger;
    private int $processId;
    private Database $db;
    private int $lastStopCheck = 0;
    private int $stopCheckInterval = 5; // Check every 5 seconds

    public function __construct(int $processId, array $config) {
        $this->processId = $processId;
        $this->config = $config;
        $this->logger = new ScraperLogger($processId);
        $this->db = Database::getInstance();
    }

    public function run(): void {
        $obStarted = false;
        try {
            $this->logger->log("Starting scraper: {$this->config['name']}", 'info');
            $this->logger->log("Type: {$this->config['type']}", 'info');

            // Set up periodic stop signal checking using tick function
            declare(ticks=1);
            register_tick_function([$this, 'checkStopSignal']);

            // Load the scraper class
            $scraperPath = __DIR__ . '/../' . $this->config['file_path'];

            if (!file_exists($scraperPath)) {
                throw new Exception("Scraper file not found: {$scraperPath}");
            }

            require_once $scraperPath;

            // Get class name from filename
            $className = basename($this->config['file_path'], '.php');

            if (!class_exists($className)) {
                throw new Exception("Scraper class not found: {$className}");
            }

            // Instantiate the scraper
            $this->scraper = new $className();

            // Apply configuration to scraper
            $this->applyConfiguration();

            // Start output buffering to capture echo statements and send to database
            $logger = $this->logger;
            ob_start(function($output) use ($logger) {
                // Send each line of output to the logger database
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        // Determine log level based on emojis/content
                        $level = 'info';
                        if (strpos($line, '✅') !== false || strpos($line, 'Success') !== false) {
                            $level = 'success';
                        } elseif (strpos($line, '❌') !== false || strpos($line, 'Failed') !== false || strpos($line, 'Error') !== false) {
                            $level = 'error';
                        } elseif (strpos($line, '⚠️') !== false || strpos($line, 'Warning') !== false) {
                            $level = 'warning';
                        }

                        $logger->log($line, $level);
                    }
                }
                return $output; // Still output to console/log file
            }, 1); // Flush after each byte for real-time updates
            $obStarted = true;

            // Run based on type
            if ($this->config['type'] === 'website') {
                $this->runWebsiteScraper();
            } else {
                $this->runXmlScraper();
            }

            if ($obStarted) ob_end_flush();

            // Unregister tick function
            unregister_tick_function([$this, 'checkStopSignal']);

            $this->logger->complete();

        } catch (Exception $e) {
            if ($obStarted) ob_end_flush();

            // Unregister tick function on error too
            unregister_tick_function([$this, 'checkStopSignal']);

            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    private function applyConfiguration(): void {
        // Apply configuration through reflection if properties exist
        $reflection = new ReflectionClass($this->scraper);

        $this->logger->log("Applying configuration to scraper...", 'info');

        // Map configuration to scraper properties
        $propertyMap = [
            'foldername' => $this->config['folder_name'] ?? null,
            'filename' => $this->config['filename'] ?? null,
            'websiteUrl' => $this->config['website_url'] ?? null,
            'urlPattern' => $this->config['url_pattern'] ?? null,
            'enableUpload' => isset($this->config['enable_upload']) ? (bool)$this->config['enable_upload'] : null,
            'testingMode' => isset($this->config['testing_mode']) ? (bool)$this->config['testing_mode'] : null
        ];

        foreach ($propertyMap as $property => $value) {
            if ($value !== null && $reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($this->scraper, $value);
                echo "✓ Set {$property} = " . var_export($value, true) . "\n";
                $this->logger->log("Set {$property} = " . var_export($value, true), 'info');
            } else {
                if ($value === null) {
                    echo "⚠ Skipping {$property} (value is null)\n";
                } else {
                    echo "⚠ Property {$property} does not exist in scraper class\n";
                }
            }
        }

        // Set owner details if the scraper has a setConfidentialInfo method
        if (method_exists($this->scraper, 'setConfidentialInfo')) {
            $confidentialInfo = $this->buildConfidentialInfo();
            if (!empty($confidentialInfo)) {
                $this->scraper->setConfidentialInfo($confidentialInfo);
                echo "✓ Set owner details (confidentialInfo)\n";
                $this->logger->log("Set owner details (confidentialInfo)", 'info');
            }
        }

        echo "\n";
    }

    private function runWebsiteScraper(): void {
        $startPage = $this->config['start_page'] ?? 1;
        $endPage = $this->config['end_page'] ?? $this->config['count_of_pages'];

        $this->logger->log("Scraping pages {$startPage} to {$endPage}", 'info');

        // Check if scraper has a run method
        if (method_exists($this->scraper, 'run')) {
            // Some scrapers use (pageCount, limit) signature
            // HolidayHomesSpain uses run($pageCount, $limit)
            $this->scraper->run($endPage, 0);
        } else {
            throw new Exception("Scraper does not have a run() method");
        }
    }

    private function runXmlScraper(): void {
        $xmlLink = $this->config['xml_link'];
        $countProps = $this->config['count_of_properties'] ?? 0;

        $this->logger->log("Processing XML: {$xmlLink}", 'info');
        $this->logger->log("Expected properties: {$countProps}", 'info');

        // Build confidential info array from config
        $confidentialInfo = $this->buildConfidentialInfo();

        if (method_exists($this->scraper, 'run')) {
            $this->scraper->run($xmlLink, $countProps, $confidentialInfo);
        } else {
            throw new Exception("Scraper does not have a run() method");
        }
    }

    /**
     * Build confidential info array from configuration
     * Includes owner details and listing ID prefix
     */
    private function buildConfidentialInfo(): array {
        $confidentialInfo = [];

        // Add owner details if available
        if (!empty($this->config['owned_by'])) {
            $confidentialInfo['Owned by'] = $this->config['owned_by'];
        }

        if (!empty($this->config['contact_person'])) {
            $confidentialInfo['Contact Person'] = $this->config['contact_person'];
        }

        if (!empty($this->config['phone'])) {
            $confidentialInfo['Phone'] = $this->config['phone'];
        }

        if (!empty($this->config['email'])) {
            $confidentialInfo['Email'] = $this->config['email'];
        }

        // Add listing ID prefix (handled specially by scrapers)
        if (!empty($this->config['listing_id_prefix'])) {
            $confidentialInfo['listing_id_prefix'] = $this->config['listing_id_prefix'];
        }

        // Log what we're passing
        if (!empty($confidentialInfo)) {
            $this->logger->log("Passing owner details to scraper", 'info');
        }

        return $confidentialInfo;
    }

    /**
     * Check if the scraper should stop
     * Only queries database every N seconds to avoid performance impact
     */
    private function shouldStop(): bool {
        $currentTime = time();

        // Only check database every X seconds
        if ($currentTime - $this->lastStopCheck < $this->stopCheckInterval) {
            return false;
        }

        $this->lastStopCheck = $currentTime;

        try {
            $process = $this->db->fetchOne(
                'SELECT status FROM scraper_processes WHERE id = ?',
                [$this->processId]
            );

            if ($process && in_array($process['status'], ['stopped', 'failed'])) {
                return true;
            }
        } catch (Exception $e) {
            // If we can't check, assume we should continue
            echo "Warning: Failed to check stop status: " . $e->getMessage() . "\n";
        }

        return false;
    }

    /**
     * Check for stop signal - this will be called periodically
     */
    public function checkStopSignal(): void {
        if ($this->shouldStop()) {
            $this->logger->log("Stop signal received - terminating scraper", 'warning');
            echo "\n⚠️ Stop signal received - terminating gracefully...\n";

            // Update status to stopped
            $this->db->update('scraper_processes', [
                'status' => 'stopped',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$this->processId]);

            exit(0);
        }
    }
}
