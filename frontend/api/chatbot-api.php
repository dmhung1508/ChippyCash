<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Lấy dữ liệu yêu cầu
$input = file_get_contents('php://input');
if (empty($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No input data provided']);
    exit;
}

try {
    // Phân tích JSON
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    $message = $data['message'] ?? '';
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Lưu tin nhắn người dùng
    saveChatMessage($conn, $user_id, $message, false);
    
    // Tạo phản hồi
    $response = generateChatbotResponse($conn, $user_id, $message);
    
    // Lưu phản hồi bot
    saveChatMessage($conn, $user_id, $response, true);
    
    // Trả về phản hồi
    echo json_encode(['response' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Tạo phản hồi chatbot dựa trên tin nhắn người dùng
 */
function generateChatbotResponse($conn, $user_id, $message) {
    // Chuyển đổi tin nhắn thành chữ thường để dễ xử lý
    $message = mb_strtolower($message, 'UTF-8');
    
    // Lấy thông tin người dùng
    $user = getUserById($conn, $user_id);
    $name = $user ? $user['name'] : 'bạn';
    
    // Lấy dữ liệu tài chính của người dùng
    $totalIncome = getTotalIncome($conn, $user_id);
    $totalExpense = getTotalExpense($conn, $user_id);
    $balance = $totalIncome - $totalExpense;
    $ratio = $totalIncome > 0 ? round(($totalExpense / $totalIncome) * 100, 1) : 0;
    
    // Lấy xu hướng chi tiêu
    $expenseTrend = getMonthlyExpenseTrend($conn, $user_id, 3);
    
    // Phân tích từ khóa trong tin nhắn - sử dụng strpos thay vì regex phức tạp
    if (strpos($message, 'xin chào') !== false || strpos($message, 'chào') !== false || strpos($message, 'hello') !== false) {
        return "Xin chào $name  'chào') !== false || strpos($message, 'hello') !== false) {
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
    
    if (strpos($message, 'xu hướng') !== false || strpos($message, 'trend') !== false) {
        $response = "Xu hướng chi tiêu của bạn trong 3 tháng gần đây:\n\n";
        
        if (empty($expenseTrend)) {
            return "Chưa có đủ dữ liệu để phân tích xu hướng chi tiêu. Hãy thêm nhiều giao dịch hơn để có thể xem xu hướng.";
        }
        
        foreach ($expenseTrend as $item) {
            $monthName = date('m/Y', mktime(0, 0, 0, $item['month'], 1, $item['year']));
            $response .= "- Tháng $monthName: " . formatMoney($item['total']) . "\n";
        }
        
        // Phân tích xu hướng
        if (count($expenseTrend) >= 2) {
            $lastMonth = $expenseTrend[count($expenseTrend) - 1]['total'];
            $previousMonth = $expenseTrend[count($expenseTrend) - 2]['total'];
            
            if ($lastMonth > $previousMonth) {
                $percentIncrease = round((($lastMonth - $previousMonth) / $previousMonth) * 100, 2);
                $response .= "\nChi tiêu của bạn tăng $percentIncrease% so với tháng trước. ";
                
                if ($percentIncrease > 20) {
                    $response .= "Đây là mức tăng đáng kể. Bạn nên xem xét lại các khoản chi tiêu trong tháng này.";
                }
            } else {
                $percentDecrease = round((($previousMonth - $lastMonth) / $previousMonth) * 100, 2);
                $response .= "\nChi tiêu của bạn giảm $percentDecrease% so với tháng trước. Tốt lắm!";
            }
        }
        
        return $response;
    }
    
    // Phát hiện giao dịch - sử dụng biểu thức chính quy đơn giản
    $expensePattern = '/(chi|mua|trả|thanh toán|tốn|hết|ăn|xăng|tiêu)\s+(\d+)/i';
    $incomePattern = '/(thu|nhận|lương|thưởng|được)\s+(\d+)/i';
    
    if (preg_match($expensePattern, $message)) {
        return "Tôi đã ghi nhận khoản chi tiêu của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút 'Sửa giao dịch' bên dưới.";
    }
    
    if (preg_match($incomePattern, $message)) {
        return "Tôi đã ghi nhận khoản thu nhập của bạn. Bạn có thể lưu giao dịch này bằng cách nhấn nút 'Sửa giao dịch' bên dưới.";
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
        
        // Chọn 3 lời khuyên ngẫu nhiên
        $randomKeys = array_rand($tips, min(3, count($tips)));
        if (!is_array($randomKeys)) {
            $randomKeys = [$randomKeys];
        }
        
        $response = "Dưới đây là một số lời khuyên tài chính hữu ích cho bạn:\n\n";
        
        foreach ($randomKeys as $index) {
            $response .= "- " . $tips[$index] . "\n";
        }
        
        return $response;
    }
    
    if (strpos($message, 'cách sử dụng') !== false || strpos($message, 'hướng dẫn') !== false || strpos($message, 'help') !== false) {
        $response = "Hướng dẫn sử dụng hệ thống Quản lý Thu Chi:\n\n";
        $response .= "1. Thêm giao dịch: Nhấn nút 'Thêm giao dịch' trên trang chủ để thêm khoản thu/chi mới.\n";
        $response .= "2. Xem giao dịch: Vào mục 'Giao dịch' để xem tất cả các giao dịch và lọc theo thời gian.\n";
        $response .= "3. Chỉnh sửa/Xóa: Nhấn vào biểu tượng bút chì để chỉnh sửa hoặc thùng rác để xóa giao dịch.\n";
        $response .= "4. Tổng quan: Trang chủ hiển thị tổng thu, tổng chi và số dư hiện tại của bạn.\n";
        $response .= "5. Chatbot: Bạn có thể hỏi tôi về tình hình tài chính, xu hướng chi tiêu, lời khuyên tiết kiệm hoặc cách sử dụng hệ thống.\n\n";
        $response .= "Bạn cần hỗ trợ gì cụ thể hơn không?";
        
        return $response;
    }
    
    // Xử lý các câu hỏi chung về tài chính
    if (strpos($message, 'đầu tư') !== false) {
        return "Đầu tư là cách tốt để gia tăng tài sản. Một số hình thức đầu tư phổ biến bao gồm: chứng khoán, trái phiếu, bất động sản, và quỹ đầu tư. Tuy nhiên, mỗi hình thức đều có rủi ro và lợi nhuận khác nhau. Nguyên tắc quan trọng là đa dạng hóa danh mục đầu tư và chỉ đầu tư số tiền mà bạn có thể chấp nhận mất.";
    }
    
    if (strpos($message, 'nợ') !== false || strpos($message, 'vay') !== false) {
        return "Quản lý nợ hiệu quả là một phần quan trọng của tài chính cá nhân. Nên ưu tiên trả các khoản nợ có lãi suất cao trước (như thẻ tín dụng). Đối với các khoản vay dài hạn như vay mua nhà, hãy cân nhắc trả thêm gốc mỗi tháng nếu có thể để giảm tổng lãi phải trả.";
    }
    
    // Phản hồi mặc định
    return "Cảm ơn bạn đã liên hệ với tôi. Tôi có thể giúp bạn phân tích tình hình tài chính, đưa ra lời khuyên tiết kiệm, hoặc trả lời các câu hỏi về quản lý tài chính cá nhân. Hãy cho tôi biết bạn cần hỗ trợ gì cụ thể?";
}
?>
