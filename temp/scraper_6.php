<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration
    $configData = json_decode('{\"id\":2,\"name\":\"Holiday Homes Spain\",\"type\":\"website\",\"website_url\":\"https://holiday-homes-spain.com\",\"url_pattern\":\"/property-search-results/?mls_search_performed=1&query_id=7f1657d4-9690-11f0-86ca-02a3ded47a2d&page_num={$page}&p_sorttype=3\",\"count_of_pages\":10,\"start_page\":1,\"end_page\":2,\"xml_link\":\"\",\"count_of_properties\":null,\"enable_upload\":1,\"testing_mode\":0,\"folder_name\":\"HolidayHomesSpain\",\"filename\":\"Properties2.json\",\"file_path\":\"Executable/HolidayHomesSpain.php\",\"status\":\"active\",\"last_run_at\":\"2025-10-15 10:56:57\",\"created_by\":1,\"created_at\":\"2025-10-15 16:56:51\",\"updated_at\":\"2025-10-15 16:56:57\"}', true);

    // Create and run adapter
    $adapter = new ScraperAdapter(6, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(6);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}