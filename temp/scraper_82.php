<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NiwibmFtZSI6IklkZWFsIEhvbWVzIFBvcnR1Z2FsIiwidHlwZSI6IndlYnNpdGUiLCJ3ZWJzaXRlX3VybCI6Imh0dHBzOi8vd3d3LmlkZWFsaG9tZXNwb3J0dWdhbC5jb20vcHJvcGVydHktZm9yLXNhbGUvcG9ydGltYW8/bG9jYXRpb249UG9ydGltYW8mcGFnZT17JHBhZ2V9IiwidXJsX3BhdHRlcm4iOiJ7JHBhZ2V9IiwiY291bnRfb2ZfcGFnZXMiOjEsInN0YXJ0X3BhZ2UiOjEsImVuZF9wYWdlIjoxLCJ4bWxfbGluayI6IiIsImNvdW50X29mX3Byb3BlcnRpZXMiOm51bGwsImVuYWJsZV91cGxvYWQiOjAsInRlc3RpbmdfbW9kZSI6MCwiZm9sZGVyX25hbWUiOiJJZGVhbEhvbWUiLCJmaWxlbmFtZSI6IlByb3BlcnRpZXMuanNvbiIsImZpbGVfcGF0aCI6IkV4ZWN1dGFibGUvSWRlYWxIb21lUG9ydHVnYWwucGhwIiwib3duZWRfYnkiOiJJZGVhbCBIb21lcyBQb3J0dWdhbCIsImNvbnRhY3RfcGVyc29uIjoiSWRlYWwgSG9tZXMgUG9ydHVnYWwiLCJwaG9uZSI6IisxIDgwMCA0MzUgMDc5NiIsImVtYWlsIjoiaW5mb0BpZGVhbGhvbWVzcG9ydHVnYWwuY29tIiwibGlzdGluZ19pZF9wcmVmaXgiOiIiLCJzdGF0dXMiOiJhY3RpdmUiLCJsYXN0X3J1bl9hdCI6bnVsbCwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTEtMTAgMTQ6MDA6NDAiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMS0xMCAxNDowMDo0MCJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(82, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(82);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}