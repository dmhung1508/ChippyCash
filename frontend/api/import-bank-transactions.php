<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

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
    
    // Kiểm tra xem có bank data trong session không
    if (!isset($_SESSION['bank_data']) || !isset($_SESSION['bank_data']['data']['transaction_data']['transactions'])) {
        throw new Exception('Không có dữ liệu giao dịch ngân hàng để import');
    }
    
    $bankTransactions = $_SESSION['bank_data']['data']['transaction_data']['transactions'];
    
    if (empty($bankTransactions)) {
        throw new Exception('Không có giao dịch nào để import');
    }
    
    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];
    
    foreach ($bankTransactions as $transaction) {
        try {
            // Parse transaction date
            $transactionDate = parseTransactionDate($transaction['transactionDate']);
            
            $creditAmount = intval($transaction['creditAmount']) ?? 0;
            $debitAmount = intval($transaction['debitAmount']) ?? 0;
            
            // Xác định loại và số tiền
            if ($creditAmount > 0) {
                $type = 'income';
                $amount = $creditAmount;
            } elseif ($debitAmount > 0) {
                $type = 'expense';
                $amount = $debitAmount;
            } else {
                continue; // Skip transactions with no amount
            }
            
            // Tạo description từ bank transaction
            $description = $transaction['description'];
            
            // Thêm thông tin người nhận nếu có
            if (!empty($transaction['benAccountName'])) {
                $description .= ' - ' . $transaction['benAccountName'];
            }
            
            if (!empty($transaction['benAccountNo'])) {
                $description .= ' (' . $transaction['benAccountNo'] . ')';
            }
            
            // Xác định category dựa trên description
            $category = determineCategoryFromDescription($description, $type);
            
            // Tạo unique transaction ID
            $bankTransactionId = $transaction['transactionDate'] . '_' . $amount . '_' . substr($transaction['description'], 0, 20);
            
            // Kiểm tra xem transaction đã tồn tại chưa dựa trên bank_transaction_id
            $checkStmt = $conn->prepare("
                SELECT id FROM transactions 
                WHERE user_id = ? AND bank_transaction_id = ? 
                LIMIT 1
            ");
            $checkStmt->execute([$userId, $bankTransactionId]);
            
            if ($checkStmt->fetch()) {
                $skippedCount++;
                continue; // Transaction already exists
            }
            
            // Insert transaction
            $insertStmt = $conn->prepare("
                INSERT INTO transactions (user_id, amount, description, type, category, date, bank_transaction_id, is_bank_import, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $insertStmt->execute([
                $userId,
                $amount,
                $description,
                $type,
                $category,
                $transactionDate,
                $bankTransactionId
            ]);
            
            $importedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Lỗi import transaction: " . $e->getMessage();
        }
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => "Import thành công {$importedCount} giao dịch, bỏ qua {$skippedCount} giao dịch trùng lặp",
        'data' => [
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper functions
function parseTransactionDate($dateString) {
    try {
        // Parse date string format: "25/05/2025 19:08:47"
        $datePart = explode(' ', $dateString)[0];
        $parts = explode('/', $datePart);
        
        if (count($parts) === 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $year = $parts[2];
            
            return "{$year}-{$month}-{$day}";
        }
        
        return date('Y-m-d'); // fallback to today
    } catch (Exception $e) {
        return date('Y-m-d'); // fallback to today
    }
}

function determineCategoryFromDescription($description, $type) {
    $description = strtolower($description);
    
    if ($type === 'income') {
        if (strpos($description, 'lương') !== false || strpos($description, 'salary') !== false) {
            return 'Lương';
        }
        if (strpos($description, 'thưởng') !== false || strpos($description, 'bonus') !== false) {
            return 'Thưởng';
        }
        return 'Thu nhập khác';
    } else {
        // Expense categories
        if (strpos($description, 'grab') !== false || strpos($description, 'taxi') !== false || 
            strpos($description, 'xe om') !== false || strpos($description, 'bus') !== false) {
            return 'Di chuyển';
        }
        
        if (strpos($description, 'com') !== false || strpos($description, 'rice') !== false || 
            strpos($description, 'food') !== false || strpos($description, 'restaurant') !== false ||
            strpos($description, 'cafe') !== false || strpos($description, 'coffee') !== false) {
            return 'Ăn uống';
        }
        
        if (strpos($description, 'shop') !== false || strpos($description, 'store') !== false || 
            strpos($description, 'market') !== false || strpos($description, 'buy') !== false) {
            return 'Mua sắm';
        }
        
        if (strpos($description, 'electric') !== false || strpos($description, 'water') !== false || 
            strpos($description, 'internet') !== false || strpos($description, 'phone') !== false ||
            strpos($description, 'bill') !== false) {
            return 'Hóa đơn';
        }
        
        if (strpos($description, 'movie') !== false || strpos($description, 'cinema') !== false || 
            strpos($description, 'game') !== false || strpos($description, 'entertainment') !== false) {
            return 'Giải trí';
        }
        
        return 'Chi phí khác';
    }
}
?> 