<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NiwibmFtZSI6IklkZWFsIEhvbWVzIFBvcnR1Z2FsIiwidHlwZSI6IndlYnNpdGUiLCJ3ZWJzaXRlX3VybCI6Imh0dHBzOi8vd3d3LmlkZWFsaG9tZXNwb3J0dWdhbC5jb20iLCJ1cmxfcGF0dGVybiI6Ii9wcm9wZXJ0eS1mb3Itc2FsZS9wb3J0aW1hbz9sb2NhdGlvbj1Qb3J0aW1hbyZwYWdlPXskcGFnZX0iLCJjb3VudF9vZl9wYWdlcyI6MSwic3RhcnRfcGFnZSI6MSwiZW5kX3BhZ2UiOjEsInhtbF9saW5rIjoiIiwiY291bnRfb2ZfcHJvcGVydGllcyI6bnVsbCwiZW5hYmxlX3VwbG9hZCI6MCwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IklkZWFsSG9tZSIsImZpbGVuYW1lIjoiUHJvcGVydGllczEuanNvbiIsImZpbGVfcGF0aCI6IkV4ZWN1dGFibGUvSWRlYWxIb21lUG9ydHVnYWwucGhwIiwib3duZWRfYnkiOiJJZGVhbCBIb21lcyBQb3J0dWdhbCIsImNvbnRhY3RfcGVyc29uIjoiSWRlYWwgSG9tZXMgUG9ydHVnYWwiLCJwaG9uZSI6IisxIDgwMCA0MzUgMDc5NiIsImVtYWlsIjoiaW5mb0BpZGVhbGhvbWVzcG9ydHVnYWwuY29tIiwibGlzdGluZ19pZF9wcmVmaXgiOiIiLCJzdGF0dXMiOiJhY3RpdmUiLCJsYXN0X3J1bl9hdCI6IjIwMjUtMTEtMTAgMDc6MzA6NDgiLCJjcmVhdGVkX2J5IjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0xMS0xMCAxNDowMDo0MCIsInVwZGF0ZWRfYXQiOiIyMDI1LTExLTEwIDE0OjMwOjQ4In0=');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(91, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(91);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}