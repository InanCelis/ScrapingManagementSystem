<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6MywibmFtZSI6Ik5pbHNvdHQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL3dlYjM5MzA6OWE0MmRlZDljYkB3d3cubmlsc290dC5jb20veG1sL2t5ZXJvLnhtbCIsImNvdW50X29mX3Byb3BlcnRpZXMiOm51bGwsImVuYWJsZV91cGxvYWQiOjEsInRlc3RpbmdfbW9kZSI6MCwiZm9sZGVyX25hbWUiOiJLeWVyb1hNTCIsImZpbGVuYW1lIjoiUHJvcGVydGllczEuanNvbiIsImZpbGVfcGF0aCI6IkV4ZWN1dGFibGVYTUwvS3llcm9YTUwucGhwIiwib3duZWRfYnkiOiJOaWxzIE90dCBHcm91cCBMdGQuIiwiY29udGFjdF9wZXJzb24iOiJOaWxzXHUwMGEwQmlyZ2VyXHUwMGEwT3R0IDsgTWlsZW5hIEtyYXN0ZXZhIiwicGhvbmUiOiI0OSAxNzIgOTUzNTAzMCIsImVtYWlsIjoibWlsZW5hQG5pbHNvdHQuY29tIiwibGlzdGluZ19pZF9wcmVmaXgiOiJOT0ctIiwic3RhdHVzIjoiYWN0aXZlIiwibGFzdF9ydW5fYXQiOiIyMDI1LTEwLTE2IDExOjEyOjM4IiwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTAtMTYgMTE6Mjg6NTkiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMC0xNiAxNzoxMjozOCJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(30, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(30);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}