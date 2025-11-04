<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NCwibmFtZSI6IkJhbGkgQm91bmQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL2ZlZWQuYmFsaWJvdW5kcmVhbHR5LmNvbS94bWwiLCJjb3VudF9vZl9wcm9wZXJ0aWVzIjoxMCwiZW5hYmxlX3VwbG9hZCI6MCwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IkJhbGlCb3VuZCIsImZpbGVuYW1lIjoiUHJvcGVydGllcy5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZVhNTC9CYWxpQm91bmRQdXBwZXRlZXIucGhwIiwib3duZWRfYnkiOiJCYWxpIEJvdW5kIE93bmVyIiwiY29udGFjdF9wZXJzb24iOiJ0ZXN0IGNvbnRhY3QiLCJwaG9uZSI6IiszNCA3MjIgNDMgMzIgOTQiLCJlbWFpbCI6InRlc3RfYmFsaWJvdW5kQGdtYWlsLmNvbSIsImxpc3RpbmdfaWRfcHJlZml4IjoiQkItIiwic3RhdHVzIjoiYWN0aXZlIiwibGFzdF9ydW5fYXQiOiIyMDI1LTEwLTMwIDA4OjAyOjI0IiwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTAtMzAgMTQ6MTY6MjUiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMC0zMCAxNTowMjoyNCJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(60, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(60);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}