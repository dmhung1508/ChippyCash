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
    
    // Lấy danh sách categories của user
    $stmt = $conn->prepare("
        SELECT name, type, description 
        FROM categories 
        WHERE user_id = ? 
        ORDER BY type ASC, name ASC
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll();
    
    // Tạo default categories nếu chưa có
    $defaultCategories = [
        // Income categories
        ['name' => 'Lương', 'type' => 'income', 'description' => 'Lương hàng tháng'],
        ['name' => 'Thưởng', 'type' => 'income', 'description' => 'Thưởng thành tích'],
        ['name' => 'Đầu tư', 'type' => 'income', 'description' => 'Thu nhập từ đầu tư'],
        ['name' => 'Thu nhập khác', 'type' => 'income', 'description' => 'Các khoản thu khác'],
        
        // Expense categories
        ['name' => 'Ăn uống', 'type' => 'expense', 'description' => 'Chi phí ăn uống hàng ngày'],
        ['name' => 'Di chuyển', 'type' => 'expense', 'description' => 'Chi phí xe bus, taxi, xăng xe'],
        ['name' => 'Mua sắm', 'type' => 'expense', 'description' => 'Quần áo, đồ dùng cá nhân'],
        ['name' => 'Giải trí', 'type' => 'expense', 'description' => 'Xem phim, du lịch, vui chơi'],
        ['name' => 'Hóa đơn', 'type' => 'expense', 'description' => 'Điện, nước, internet, điện thoại'],
        ['name' => 'Chi phí khác', 'type' => 'expense', 'description' => 'Các khoản chi khác']
    ];
    
    // Nếu chưa có categories nào, tạo default
    if (empty($categories)) {
        foreach ($defaultCategories as $category) {
            $insertStmt = $conn->prepare("
                INSERT IGNORE INTO categories (user_id, name, type, description) 
                VALUES (?, ?, ?, ?)
            ");
            $insertStmt->execute([$userId, $category['name'], $category['type'], $category['description']]);
        }
        
        // Lấy lại categories sau khi insert
        $stmt->execute([$userId]);
        $categories = $stmt->fetchAll();
    }
    
    // Phân loại categories theo type
    $result = [
        'income' => [],
        'expense' => []
    ];
    
    foreach ($categories as $category) {
        $result[$category['type']][] = [
            'name' => $category['name'],
            'description' => $category['description']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 