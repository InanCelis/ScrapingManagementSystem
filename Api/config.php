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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'delete':
            $id = $input['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }

            $result = $scraperManager->deleteConfig($id);
            echo json_encode($result);
            break;

        case 'duplicate':
            $id = $input['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }

            $result = $scraperManager->duplicateConfig($id);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get':
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }

            $config = $scraperManager->getConfig($id);
            if ($config) {
                echo json_encode(['success' => true, 'config' => $config]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Configuration not found']);
            }
            break;

        case 'list':
            $filters = [];
            if (!empty($_GET['type'])) {
                $filters['type'] = $_GET['type'];
            }
            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }

            $configs = $scraperManager->getAllConfigs($filters);
            echo json_encode(['success' => true, 'configs' => $configs]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
