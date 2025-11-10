<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NSwibmFtZSI6IlRoYWkgUHJvcGVydHkgMSIsInR5cGUiOiJ4bWwiLCJ3ZWJzaXRlX3VybCI6IiIsInVybF9wYXR0ZXJuIjoiIiwiY291bnRfb2ZfcGFnZXMiOm51bGwsInN0YXJ0X3BhZ2UiOjEsImVuZF9wYWdlIjpudWxsLCJ4bWxfbGluayI6Imh0dHBzOi8vdGhhaXByb3BlcnR5MS5jb20vYXBpL2xBMWJ3by9mZWVkcy9uZXN0b3BhLWZlZWQiLCJjb3VudF9vZl9wcm9wZXJ0aWVzIjpudWxsLCJlbmFibGVfdXBsb2FkIjoxLCJ0ZXN0aW5nX21vZGUiOjAsImZvbGRlcl9uYW1lIjoiMDEgUmFuZG9tIiwiZmlsZW5hbWUiOiJUaGFpUHJvcGVydHkxLmpzb24iLCJmaWxlX3BhdGgiOiJFeGVjdXRhYmxlWE1ML1RoYWlQcm9wZXJ0eTEucGhwIiwib3duZWRfYnkiOiJIdWEtSGluIE5vIENvbXBhbnkgTGltaXRlZCAoVGhhaSBQcm9wZXJ0eTEpIiwiY29udGFjdF9wZXJzb24iOiJTdGVmZmVuIEhlaXRtYW4iLCJwaG9uZSI6Iis2NiA5OCAzMDQgODAyMiIsImVtYWlsIjoic3RlZmZlbkB0aGFpcHJvcGVydHkxLmNvbSIsImxpc3RpbmdfaWRfcHJlZml4IjoiVFAxLSIsInN0YXR1cyI6ImFjdGl2ZSIsImxhc3RfcnVuX2F0IjoiMjAyNS0xMS0wNCAwNjo0MjowMSIsImNyZWF0ZWRfYnkiOjEsImNyZWF0ZWRfYXQiOiIyMDI1LTExLTA0IDExOjI4OjA5IiwidXBkYXRlZF9hdCI6IjIwMjUtMTEtMDQgMTM6NTY6NTAifQ==');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(81, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(81);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}