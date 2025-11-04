<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6NCwibmFtZSI6IkJhbGkgQm91bmQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL2xod3Rsb2podWdhemh3dHRkbGVoLnN1cGFiYXNlLmNvL2Z1bmN0aW9ucy92MS9wcm9wZXJ0eS1mZWVkIiwiY291bnRfb2ZfcHJvcGVydGllcyI6MTAsImVuYWJsZV91cGxvYWQiOjAsInRlc3RpbmdfbW9kZSI6MCwiZm9sZGVyX25hbWUiOiJCYWxpQm91bmQiLCJmaWxlbmFtZSI6IlByb3BlcnRpZXMuanNvbiIsImZpbGVfcGF0aCI6IkV4ZWN1dGFibGVYTUwvQmFsaUJvdW5kLnBocCIsIm93bmVkX2J5IjoiQmFsaSBCb3VuZCBPd25lciIsImNvbnRhY3RfcGVyc29uIjoidGVzdCBjb250YWN0IiwicGhvbmUiOiIrMzQgNzIyIDQzIDMyIDk0IiwiZW1haWwiOiJ0ZXN0X2JhbGlib3VuZEBnbWFpbC5jb20iLCJsaXN0aW5nX2lkX3ByZWZpeCI6IkJCVC0iLCJzdGF0dXMiOiJhY3RpdmUiLCJsYXN0X3J1bl9hdCI6IjIwMjUtMTEtMDMgMDQ6NTc6MTUiLCJjcmVhdGVkX2J5IjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0xMC0zMCAxNDoxNjoyNSIsInVwZGF0ZWRfYXQiOiIyMDI1LTExLTAzIDEzOjI2OjQxIn0=');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(69, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(69);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}