<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /ScrapingToolsAutoSync/login');
exit;
