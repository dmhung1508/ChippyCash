<?php
// Turn off all error reporting để không có HTML output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
session_start();

echo "Checking session...\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied', 'session' => $_SESSION]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'API working',
    'session' => $_SESSION
]);
?> 