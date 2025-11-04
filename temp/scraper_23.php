<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ScraperLogger.php';
require_once __DIR__ . '/../core/ScraperAdapter.php';

try {
    // Decode configuration from base64
    $configJson = base64_decode('eyJpZCI6MywibmFtZSI6Ik5pbHNvdHQiLCJ0eXBlIjoieG1sIiwid2Vic2l0ZV91cmwiOiIiLCJ1cmxfcGF0dGVybiI6IiIsImNvdW50X29mX3BhZ2VzIjpudWxsLCJzdGFydF9wYWdlIjoxLCJlbmRfcGFnZSI6bnVsbCwieG1sX2xpbmsiOiJodHRwczovL3dlYjM5MzA6OWE0MmRlZDljYkB3d3cubmlsc290dC5jb20veG1sL2t5ZXJvLnhtbCIsImNvdW50X29mX3Byb3BlcnRpZXMiOm51bGwsImVuYWJsZV91cGxvYWQiOjEsInRlc3RpbmdfbW9kZSI6MCwiZm9sZGVyX25hbWUiOiJLeWVyb1hNTCIsImZpbGVuYW1lIjoiUHJvcGVydGllczEuanNvbiIsImZpbGVfcGF0aCI6IkV4ZWN1dGFibGVYTUwvS3llcm9YTUwucGhwIiwic3RhdHVzIjoiYWN0aXZlIiwibGFzdF9ydW5fYXQiOiIyMDI1LTEwLTE2IDA1OjI5OjA1IiwiY3JlYXRlZF9ieSI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMTAtMTYgMTE6Mjg6NTkiLCJ1cGRhdGVkX2F0IjoiMjAyNS0xMC0xNiAxMTozMzozNyJ9');
    $configData = json_decode($configJson, true);

    if ($configData === null) {
        throw new Exception('Failed to decode configuration: ' . json_last_error_msg());
    }

    // Create and run adapter
    $adapter = new ScraperAdapter(23, $configData);
    $adapter->run();

} catch (Exception $e) {
    $logger = new ScraperLogger(23);
    $logger->error('Fatal error: ' . $e->getMessage());
    $logger->error('Stack trace: ' . $e->getTraceAsString());
}