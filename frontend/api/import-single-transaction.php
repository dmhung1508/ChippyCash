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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['transaction_id']) || !isset($input['category'])) {
        throw new Exception('Transaction ID và category là bắt buộc');
    }
    
    $transactionId = $input['transaction_id'];
    $selectedCategory = $input['category'];
    
    // Kiểm tra xem có bank data trong session không
    if (!isset($_SESSION['bank_data']) || !isset($_SESSION['bank_data']['data']['transaction_data']['transactions'])) {
        throw new Exception('Không có dữ liệu giao dịch ngân hàng');
    }
    
    $bankTransactions = $_SESSION['bank_data']['data']['transaction_data']['transactions'];
    
    // Tìm transaction cụ thể
    $targetTransaction = null;
    foreach ($bankTransactions as $transaction) {
        $creditAmount = intval($transaction['creditAmount']) ?? 0;
        $debitAmount = intval($transaction['debitAmount']) ?? 0;
        $amount = $creditAmount > 0 ? $creditAmount : $debitAmount;
        
        $currentTransactionId = $transaction['transactionDate'] . '_' . $amount . '_' . substr($transaction['description'], 0, 20);
        
        if ($currentTransactionId === $transactionId) {
            $targetTransaction = $transaction;
            break;
        }
    }
    
    if (!$targetTransaction) {
        throw new Exception('Không tìm thấy giao dịch');
    }
    
    // Kiểm tra xem transaction đã tồn tại chưa
    $checkStmt = $conn->prepare("
        SELECT id FROM transactions 
        WHERE user_id = ? AND bank_transaction_id = ? 
        LIMIT 1
    ");
    $checkStmt->execute([$userId, $transactionId]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Giao dịch này đã được import');
    }
    
    // Parse transaction data
    $transactionDate = parseTransactionDate($targetTransaction['transactionDate']);
    $creditAmount = intval($targetTransaction['creditAmount']) ?? 0;
    $debitAmount = intval($targetTransaction['debitAmount']) ?? 0;
    
    // Xác định loại và số tiền
    if ($creditAmount > 0) {
        $type = 'income';
        $amount = $creditAmount;
    } elseif ($debitAmount > 0) {
        $type = 'expense';
        $amount = $debitAmount;
    } else {
        throw new Exception('Giao dịch không có số tiền hợp lệ');
    }
    
    // Tạo description từ bank transaction
    $description = $targetTransaction['description'];
    
    // Thêm thông tin người nhận nếu có
    if (!empty($targetTransaction['benAccountName'])) {
        $description .= ' - ' . $targetTransaction['benAccountName'];
    }
    
    if (!empty($targetTransaction['benAccountNo'])) {
        $description .= ' (' . $targetTransaction['benAccountNo'] . ')';
    }
    
    // Xử lý category tự động nếu cần
    $finalCategory = $selectedCategory;
    if ($selectedCategory === 'auto') {
        $finalCategory = autoDetectCategory($description, $type, $amount);
    }
    
    // Insert transaction với category được chọn
    $insertStmt = $conn->prepare("
        INSERT INTO transactions (user_id, amount, description, type, category, date, bank_transaction_id, is_bank_import, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $insertStmt->execute([
        $userId,
        $amount,
        $description,
        $type,
        $finalCategory,
        $transactionDate,
        $transactionId
    ]);
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => "Import thành công giao dịch với thể loại '{$finalCategory}'",
        'data' => [
            'transaction_id' => $transactionId,
            'category' => $finalCategory,
            'amount' => $amount,
            'type' => $type
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

function autoDetectCategory($description, $type, $amount) {
    $description = mb_strtolower($description, 'UTF-8');
    
    // Định nghĩa keywords cho các category
    $categoryKeywords = [
        'expense' => [
            'Ăn uống' => ['ăn', 'uống', 'thức ăn', 'đồ ăn', 'cơm', 'phở', 'café', 'coffee', 'kem', 'bánh', 'nhà hàng', 'quán', 'bún', 'mì', 'cà phê'],
            'Di chuyển' => ['grab', 'taxi', 'xe ôm', 'bus', 'xe buýt', 'giao hàng', 'ship', 'vận chuyển', 'xăng', 'petrol', 'xe', 'oto'],
            'Mua sắm' => ['mua', 'shop', 'shopping', 'siêu thị', 'chợ', 'điện thoại', 'laptop', 'quần áo', 'giày', 'túi'],
            'Tiền khác' => ['game', 'nạp tiền', 'thẻ', 'điện tử', 'online', 'web', 'app'],
            'Y tế' => ['bệnh viện', 'thuốc', 'khám', 'chữa', 'y tế', 'doctor', 'medicine'],
            'Học tập' => ['học', 'trường', 'khóa học', 'sách', 'học phí', 'education'],
            'Giải trí' => ['phim', 'cinema', 'karaoke', 'game', 'giải trí', 'entertainment', 'du lịch', 'travel'],
            'Nhà ở' => ['nhà', 'thuê', 'điện', 'nước', 'internet', 'wifi', 'rent'],
            'Chung' => ['chuyển', 'transfer', 'atm', 'rút tiền']
        ],
        'income' => [
            'Lương' => ['lương', 'salary', 'wage', 'thưởng', 'bonus'],
            'Bán hàng' => ['bán', 'sell', 'selling', 'revenue', 'doanh thu'],
            'Đầu tư' => ['đầu tư', 'investment', 'cổ tức', 'dividend', 'lãi', 'interest'],
            'Chung' => ['chuyển', 'transfer', 'nhận', 'receive']
        ]
    ];
    
    // Tìm category phù hợp nhất
    $bestMatch = 'Chung';
    $maxMatches = 0;
    
    if (isset($categoryKeywords[$type])) {
        foreach ($categoryKeywords[$type] as $category => $keywords) {
            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    $matchCount++;
                }
            }
            
            if ($matchCount > $maxMatches) {
                $maxMatches = $matchCount;
                $bestMatch = $category;
            }
        }
    }
    
    return $bestMatch;
}
?> 