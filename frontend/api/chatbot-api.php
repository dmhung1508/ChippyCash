<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Save user message
    saveChatMessage($conn, $user_id, $message, false);
    
    // Generate response
    $response = generateChatbotResponse($conn, $user_id, $message);
    
    // Save bot response
    saveChatMessage($conn, $user_id, $response, true);
    
    // Return response
    echo json_encode(['response' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate chatbot response based on user message
 */
function generateChatbotResponse($conn, $user_id, $message) {
    // Convert message to lowercase for easier processing
    $message = mb_strtolower($message, 'UTF-8');
    
    // Get user info
    $user = getUserById($conn, $user_id);
    $name = $user['name'];
    
    // Get financial data
    $totalIncome = getTotalIncome($conn, $user_id);
    $totalExpense = getTotalExpense($conn, $user_id);
    $balance = $totalIncome - $totalExpense;
    $ratio = getIncomeExpenseRatio($conn, $user_id);
    
    // Get expense trends
    $expenseTrend = getMonthlyExpenseTrend($conn, $user_id, 3);
    
    // Process keywords in message
    if (strpos($message, 'xin chào') !== false || strpos($message, 'chào') !== false || strpos($message, 'hello') !== false) {
        return "Xin chào $name! Tôi là trợ lý tài chính AI. Tôi có thể giúp bạn phân tích chi tiêu, đưa ra lời khuyên tài chính và trả lời các câu hỏi về quản lý tài chính cá nhân. Bạn cần hỗ trợ gì?";
    }
    
    if (strpos($message, 'tổng quan') !== false || strpos($message, 'tình hình') !== false || strpos($message, 'tài chính') !== false) {
        $response = "Đây là tổng quan tài chính của bạn:\n\n";
        $response .= "- Tổng thu nhập: " . formatMoney($totalIncome) . "\n";
        $response .= "- Tổng chi tiêu: " . formatMoney($totalExpense) . "\n";
        $response .= "- Số dư hiện tại: " . formatMoney($balance) . "\n\n";
        
        if ($balance < 0) {
            $response .= "Cảnh báo: Bạn đang chi tiêu nhiều hơn thu nhập. Hãy xem xét cắt giảm một số khoản chi không cần thiết.";
        } elseif ($ratio > 70) {
            $response .= "Lưu ý: Tỷ lệ chi tiêu/thu nhập của bạn là $ratio%. Bạn nên cố gắng giữ tỷ lệ này dưới 70% để đảm bảo tài chính lành mạnh.";
        } else {
            $response .= "Tốt! Bạn đang quản lý tài chính khá tốt với tỷ lệ chi tiêu/thu nhập là $ratio%.";
        }
        
        return $response;
    }
    
    if (strpos($message, 'tiết kiệm') !== false || strpos($message, 'tiền') !== false || strpos($message, 'kế hoạch') !== false) {
        $monthlyIncome = $totalIncome / 12;
        $monthlyExpense = $totalExpense / 12;
        
        $savingPotential = $monthlyIncome - $monthlyExpense;
        
        if ($savingPotential <= 0) {
            return "Dựa trên dữ liệu hiện tại, bạn đang chi tiêu nhiều hơn hoặc bằng thu nhập hàng tháng. Để có thể tiết kiệm, bạn cần cắt giảm chi tiêu hoặc tăng thu nhập.\n\nGợi ý: Hãy xem xét các khoản chi tiêu không cần thiết và cố gắng cắt giảm ít nhất 10-15% tổng chi tiêu.";
        }
        
        $response = "Dựa trên dữ liệu tài chính của bạn, tôi đề xuất kế hoạch tiết kiệm như sau:\n\n";
        $response .= "- Thu nhập ước tính hàng tháng: " . formatMoney($monthlyIncome) . "\n";
        $response .= "- Chi tiêu ước tính hàng tháng: " . formatMoney($monthlyExpense) . "\n";
        $response .= "- Tiềm năng tiết kiệm hàng tháng: " . formatMoney($savingPotential) . "\n\n";
        
        $response .= "Kế hoạch tiết kiệm đề xuất:\n";
        $response .= "1. Quỹ khẩn cấp (50%): " . formatMoney($savingPotential * 0.5) . "/tháng\n";
        $response .= "2. Tiết kiệm dài hạn (30%): " . formatMoney($savingPotential * 0.3) . "/tháng\n";
        $response .= "3. Quỹ giải trí/cá nhân (20%): " . formatMoney($savingPotential * 0.2) . "/tháng\n\n";
        
        $response .= "Với kế hoạch này, sau 1 năm bạn sẽ tiết kiệm được khoảng " . formatMoney($savingPotential * 12) . ".";
        
        return $response;
    }
    
    if (strpos($message, 'lời khuyên') !== false || strpos($message, 'gợi ý') !== false || strpos($message, 'tư vấn') !== false) {
        $tips = [
            "Quy tắc 50/30/20: Dành 50% thu nhập cho nhu cầu thiết yếu, 30% cho mong muốn và 20% cho tiết kiệm.",
            "Hãy tạo quỹ khẩn cấp đủ chi tiêu cho 3-6 tháng để đề phòng những tình huống bất ngờ.",
            "Theo dõi mọi khoản chi tiêu, dù nhỏ. Những khoản nhỏ cộng lại có thể tạo nên số tiền lớn.",
            "Đặt mục tiêu tài chính cụ thể và thực tế. Ví dụ: tiết kiệm 10% thu nhập mỗi tháng.",
            "Hạn chế sử dụng thẻ tín dụng và luôn thanh toán đầy đủ số dư mỗi tháng để tránh lãi suất cao.",
            "Tự nấu ăn tại nhà thay vì ăn ngoài có thể giúp bạn tiết kiệm đáng kể.",
            "Trước khi mua sắm, hãy tự hỏi: 'Mình có thực sự cần món đồ này không?' để tránh mua sắm xung động.",
            "Đầu tư vào kiến thức tài chính. Hiểu biết về tài chính sẽ giúp bạn đưa ra quyết định tốt hơn."
        ];
        
        $randomTips = array_rand($tips, 3);
        $response = "Dưới đây là một số lời khuyên tài chính hữu ích cho bạn:\n\n";
        
        foreach ($randomTips as $index) {
            $response .= "- " . $tips[$index] . "\n";
        }
        
        return $response;
    }
    
    // Default response
    return "Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?";
}
?>

