<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ScraperManager.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check authentication
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$scraperManager = new ScraperManager();

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'start':
            $configId = $input['config_id'] ?? 0;
            if (!$configId) {
                echo json_encode(['success' => false, 'message' => 'Config ID required']);
                exit;
            }

            $result = $scraperManager->startScraper($configId);
            echo json_encode($result);
            break;

        case 'stop':
            $processId = $input['process_id'] ?? 0;
            if (!$processId) {
                echo json_encode(['success' => false, 'message' => 'Process ID required']);
                exit;
            }

            $result = $scraperManager->stopScraper($processId);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_process':
            $processId = $_GET['process_id'] ?? 0;
            if (!$processId) {
                echo json_encode(['success' => false, 'message' => 'Process ID required']);
                exit;
            }

            $process = $scraperManager->getProcess($processId);
            if ($process) {
                // Get config name
                $config = $scraperManager->getConfig($process['config_id']);
                $process['config_name'] = $config['name'] ?? 'Unknown';

                echo json_encode(['success' => true, 'process' => $process]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Process not found']);
            }
            break;

        case 'get_logs':
            $processId = $_GET['process_id'] ?? 0;
            $limit = $_GET['limit'] ?? 100;

            if (!$processId) {
                echo json_encode(['success' => false, 'message' => 'Process ID required']);
                exit;
            }

            $db = Database::getInstance();
            $logs = $db->fetchAll(
                'SELECT * FROM scraper_logs WHERE process_id = ? ORDER BY created_at DESC LIMIT ?',
                [$processId, $limit]
            );

            // Reverse to show oldest first
            $logs = array_reverse($logs);

            echo json_encode(['success' => true, 'logs' => $logs]);
            break;

        case 'get_running':
            $processes = $scraperManager->getRunningProcesses();
            echo json_encode(['success' => true, 'processes' => $processes]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
