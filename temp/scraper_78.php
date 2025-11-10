<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NSwibmFtZSI6IlRoYWkgUHJvcGVydHkgMSIsInR5cGUiOiJ4bWwiLCJ3ZWJzaXRlX3VybCI6IiIsInVybF9wYXR0ZXJuIjoiIiwiY291bnRfb2ZfcGFnZXMiOm51bGwsInN0YXJ0X3BhZ2UiOjEsImVuZF9wYWdlIjpudWxsLCJ4bWxfbGluayI6Imh0dHBzOi8vdGhhaXByb3BlcnR5MS5jb20vYXBpL2xBMWJ3by9mZWVkcy9uZXN0b3BhLWZlZWQiLCJjb3VudF9vZl9wcm9wZXJ0aWVzIjoxMCwiZW5hYmxlX3VwbG9hZCI6MCwidGVzdGluZ19tb2RlIjowLCJmb2xkZXJfbmFtZSI6IlJhbmRvbSIsImZpbGVuYW1lIjoiVGhhaVByb3BlcnR5MS5qc29uIiwiZmlsZV9wYXRoIjoiRXhlY3V0YWJsZVhNTC9UaGFpUHJvcGVydHkxLnBocCIsIm93bmVkX2J5IjoiSHVhLUhpbiBObyBDb21wYW55IExpbWl0ZWQgKFRoYWkgUHJvcGVydHkxKSIsImNvbnRhY3RfcGVyc29uIjoiU3RlZmZlbiBIZWl0bWFuIiwicGhvbmUiOiIrNjYgOTggMzA0IDgwMjIiLCJlbWFpbCI6InN0ZWZmZW5AdGhhaXByb3BlcnR5MS5jb20iLCJsaXN0aW5nX2lkX3ByZWZpeCI6IlRQMS0iLCJzdGF0dXMiOiJhY3RpdmUiLCJsYXN0X3J1bl9hdCI6IjIwMjUtMTEtMDQgMDQ6NDk6MTMiLCJjcmVhdGVkX2J5IjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0xMS0wNCAxMToyODowOSIsInVwZGF0ZWRfYXQiOiIyMDI1LTExLTA0IDExOjQ5OjEzIn0=');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(78, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(78);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}