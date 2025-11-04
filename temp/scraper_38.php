<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6MiwibmFtZSI6IkhvbGlkYXkgSG9tZXMgU3BhaW4iLCJ0eXBlIjoid2Vic2l0ZSIsIndlYnNpdGVfdXJsIjoiaHR0cHM6Ly9ob2xpZGF5LWhvbWVzLXNwYWluLmNvbSIsInVybF9wYXR0ZXJuIjoiL3Byb3BlcnR5LXNlYXJjaC1yZXN1bHRzLz9tbHNfc2VhcmNoX3BlcmZvcm1lZD0xJnF1ZXJ5X2lkPWIxZTEwNGI0LWE5YTgtMTFmMC05ZWJkLTAyYTNkZWQ0N2EyZCZwYWdlX251bT17JHBhZ2V9JnBfc29ydHR5cGU9MyIsImNvdW50X29mX3BhZ2VzIjoxMCwic3RhcnRfcGFnZSI6MSwiZW5kX3BhZ2UiOjIsInhtbF9saW5rIjoiIiwiY291bnRfb2ZfcHJvcGVydGllcyI6bnVsbCwiZW5hYmxlX3VwbG9hZCI6MSwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IkhvbGlkYXlIb21lc1NwYWluIiwiZmlsZW5hbWUiOiJQcm9wZXJ0aWVzMy5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZS9Ib2xpZGF5SG9tZXNTcGFpbi5waHAiLCJvd25lZF9ieSI6bnVsbCwiY29udGFjdF9wZXJzb24iOm51bGwsInBob25lIjpudWxsLCJlbWFpbCI6bnVsbCwibGlzdGluZ19pZF9wcmVmaXgiOm51bGwsInN0YXR1cyI6ImFjdGl2ZSIsImxhc3RfcnVuX2F0IjoiMjAyNS0xMC0xNyAwODoxMjo0MiIsImNyZWF0ZWRfYnkiOjEsImNyZWF0ZWRfYXQiOiIyMDI1LTEwLTE1IDE2OjU2OjUxIiwidXBkYXRlZF9hdCI6IjIwMjUtMTAtMTcgMTQ6MTI6NDIifQ==');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(38, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(38);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}