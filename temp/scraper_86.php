<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NiwibmFtZSI6IklkZWFsIEhvbWVzIFBvcnR1Z2FsIiwidHlwZSI6IndlYnNpdGUiLCJ3ZWJzaXRlX3VybCI6Imh0dHBzOi8vd3d3LmlkZWFsaG9tZXNwb3J0dWdhbC5jb20iLCJ1cmxfcGF0dGVybiI6Ii9wcm9wZXJ0eS1mb3Itc2FsZS9wb3J0aW1hbz9sb2NhdGlvbj1Qb3J0aW1hbyZwYWdlPXskcGFnZX0iLCJjb3VudF9vZl9wYWdlcyI6MSwic3RhcnRfcGFnZSI6MSwiZW5kX3BhZ2UiOjEsInhtbF9saW5rIjoiIiwiY291bnRfb2ZfcHJvcGVydGllcyI6bnVsbCwiZW5hYmxlX3VwbG9hZCI6MCwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IklkZWFsSG9tZSIsImZpbGVuYW1lIjoiUHJvcGVydGllcy5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZS9JZGVhbEhvbWVQb3J0dWdhbC5waHAiLCJvd25lZF9ieSI6IklkZWFsIEhvbWVzIFBvcnR1Z2FsIiwiY29udGFjdF9wZXJzb24iOiJJbmFuIiwicGhvbmUiOiIrMSA4MDAgNDM1IDA3OTYiLCJlbWFpbCI6ImluZm9AaWRlYWxob21lc3BvcnR1Z2FsLmNvbSIsImxpc3RpbmdfaWRfcHJlZml4IjoiIiwic3RhdHVzIjoiYWN0aXZlIiwibGFzdF9ydW5fYXQiOiIyMDI1LTExLTEwIDA3OjE3OjEyIiwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTEtMTAgMTQ6MDA6NDAiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMS0xMCAxNDoxODoxOCJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(86, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(86);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}