<?php
session_start();

require __DIR__ . '/guard.php';
require __DIR__ . '/router.php';

// 1. Auth
checkAuth();

// 2. Authorization + routing
$route = $_GET['r'] ?? '';
dispatch($route);