<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Lấy danh sách transactions đã import từ bank
    $stmt = $conn->prepare("
        SELECT bank_transaction_id 
        FROM transactions 
        WHERE user_id = ? AND is_bank_import = 1 AND bank_transaction_id IS NOT NULL
    ");
    $stmt->execute([$userId]);
    $importedTransactions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $importedTransactions
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 