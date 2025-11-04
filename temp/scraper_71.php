<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NCwibmFtZSI6IkJhbGkgQm91bmQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL2xod3Rsb2podWdhemh3dHRkbGVoLnN1cGFiYXNlLmNvL2Z1bmN0aW9ucy92MS9wcm9wZXJ0eS1mZWVkIiwiY291bnRfb2ZfcHJvcGVydGllcyI6bnVsbCwiZW5hYmxlX3VwbG9hZCI6MCwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IkJhbGlCb3VuZCIsImZpbGVuYW1lIjoiUHJvcGVydGllcy5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZVhNTC9CYWxpQm91bmQucGhwIiwib3duZWRfYnkiOiJCYWxpYm91bmQgUmVhbHR5ICIsImNvbnRhY3RfcGVyc29uIjoiUGF5YWwgQmlkIC8gQ2hyaXMiLCJwaG9uZSI6Iis2MjgyMTQ0NTExNTU2IiwiZW1haWwiOiJpbmZvQGJhbGlib3VuZHJlYWx0eS5jb20iLCJsaXN0aW5nX2lkX3ByZWZpeCI6IkJCLSIsInN0YXR1cyI6ImFjdGl2ZSIsImxhc3RfcnVuX2F0IjoiMjAyNS0xMS0wMyAwNjozMzozMSIsImNyZWF0ZWRfYnkiOjEsImNyZWF0ZWRfYXQiOiIyMDI1LTEwLTMwIDE0OjE2OjI1IiwidXBkYXRlZF9hdCI6IjIwMjUtMTEtMDMgMTQ6MTY6MTkifQ==');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(71, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(71);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}