<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6MywibmFtZSI6Ik5pbHNvdHQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL3dlYjM5MzA6OWE0MmRlZDljYkB3d3cubmlsc290dC5jb20veG1sL2t5ZXJvLnhtbCIsImNvdW50X29mX3Byb3BlcnRpZXMiOm51bGwsImVuYWJsZV91cGxvYWQiOjEsInRlc3RpbmdfbW9kZSI6MCwiZm9sZGVyX25hbWUiOiJOaWxzIiwiZmlsZW5hbWUiOiJUZXN0c3Nzcy5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZVhNTC9LeWVyb1hNTC5waHAiLCJzdGF0dXMiOiJhY3RpdmUiLCJsYXN0X3J1bl9hdCI6bnVsbCwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTAtMTYgMTE6Mjg6NTkiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMC0xNiAxMToyODo1OSJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(22, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(22);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}