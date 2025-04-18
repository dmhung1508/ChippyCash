<?php
class FinancialChatbot {
    private $conn;
    private $user_id;
    private $common_questions = [
        'làm thế nào để lập ngân sách hiệu quả' => [
            'title' => 'Làm thế nào để lập ngân sách hiệu quả?',
            'response' => "Để lập ngân sách hiệu quả, bạn nên làm theo các bước sau:

1. Xác định tổng thu nhập hàng tháng
2. Liệt kê tất cả các khoản chi tiêu cố định (tiền nhà, điện nước, internet...)
3. Ước tính các khoản chi tiêu biến động (ăn uống, giải trí, đi lại...)
4. Áp dụng quy tắc 50/30/20:
   - 50% cho nhu cầu thiết yếu
   - 30% cho mong muốn
   - 20% cho tiết kiệm và đầu tư
5. Theo dõi chi tiêu thực tế và điều chỉnh ngân sách khi cần

Bạn muốn tôi giúp lập ngân sách cụ thể cho tình hình tài chính của bạn không?"
        ],
        'làm sao để tiết kiệm tiền' => [
            'title' => 'Làm sao để tiết kiệm tiền?',
            'response' => "Dưới đây là một số cách hiệu quả để tiết kiệm tiền:

1. Tự động chuyển một phần thu nhập vào tài khoản tiết kiệm ngay khi nhận lương
2. Cắt giảm các khoản chi tiêu không cần thiết (đăng ký dịch vụ không dùng đến, ăn ngoài quá nhiều...)
3. Lập danh sách mua sắm trước khi đi chợ/siêu thị và tuân thủ nó
4. Sử dụng các ứng dụng theo dõi chi tiêu để nhận biết thói quen tiêu tiền
5. Áp dụng quy tắc 24 giờ: chờ ít nhất 24 giờ trước khi mua các món đồ đắt tiền
6. Tìm kiếm các chương trình khuyến mãi, giảm giá khi mua sắm

Bạn đang gặp khó khăn trong việc tiết kiệm khoản chi tiêu nào cụ thể?"
        ],
        'quỹ khẩn cấp là gì' => [
            'title' => 'Quỹ khẩn cấp là gì?',
            'response' => "Quỹ khẩn cấp là một khoản tiền bạn dành riêng để đối phó với các tình huống tài chính bất ngờ như mất việc, ốm đau, sửa chữa khẩn cấp...

Đặc điểm của quỹ khẩn cấp:
1. Nên tương đương 3-6 tháng chi tiêu cơ bản
2. Cần được giữ ở nơi dễ dàng tiếp cận (tài khoản tiết kiệm không kỳ hạn)
3. Chỉ sử dụng cho các trường hợp thực sự khẩn cấp
4. Nên bổ sung lại ngay khi đã sử dụng

Việc có quỹ khẩn cấp giúp bạn tránh phải vay nợ hoặc rút tiền từ các khoản đầu tư dài hạn khi gặp khó khăn tài chính đột xuất.

Bạn đã có quỹ khẩn cấp chưa?"
        ]
    ];
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    
    public function processMessage($message) {
        // Convert message to lowercase for matching
        $message_lower = mb_strtolower($message, 'UTF-8');
        
        // Check for common questions
        foreach ($this->common_questions as $key => $data) {
            if (strpos($message_lower, $key) !== false) {
                return $data['response'];
            }
        }
        
        // Process custom questions
        if (strpos($message_lower, 'ngân sách') !== false || strpos($message_lower, 'budget') !== false) {
            return $this->getBudgetAdvice();
        }
        
        if (strpos($message_lower, 'chi tiêu') !== false || strpos($message_lower, 'spending') !== false) {
            return $this->getSpendingAnalysis();
        }
        
        if (strpos($message_lower, 'tiết kiệm') !== false || strpos($message_lower, 'saving') !== false) {
            return $this->getSavingAdvice();
        }
        
        if (strpos($message_lower, 'tổng quan') !== false || strpos($message_lower, 'overview') !== false) {
            return $this->getFinancialOverview();
        }
        
        // Default response
        return "Tôi có thể giúp bạn với các vấn đề về ngân sách, chi tiêu, tiết kiệm và quản lý tài chính cá nhân. Bạn muốn biết thêm về vấn đề nào cụ thể?";
    }
    
    private function getBudgetAdvice() {
        // Get user's financial data
        $total_income = $this->getTotalIncome();
        $total_expenses = $this->getTotalExpenses();
        
        $response = "Dựa trên dữ liệu tài chính của bạn:

";
        $response .= "Thu nhập hàng tháng: " . number_format($total_income, 0, ',', '.') . "₫
";
        $response .= "Chi tiêu hàng tháng: " . number_format($total_expenses, 0, ',', '.') . "₫

";
        
        if ($total_expenses > $total_income) {
            $response .= "⚠️ Chi tiêu của bạn đang vượt quá thu nhập. Một số gợi ý:

";
            $response .= "1. Xem xét và cắt giảm các khoản chi tiêu không cần thiết
";
            $response .= "2. Tìm kiếm nguồn thu nhập bổ sung
";
            $response .= "3. Tạo kế hoạch ngân sách chặt chẽ hơn
";
        } else {
            $savings = $total_income - $total_expenses;
            $response .= "✅ Bạn đang tiết kiệm được " . number_format($savings, 0, ',', '.') . "₫ mỗi tháng.

";
            $response .= "Phân bổ ngân sách đề xuất:
";
            $response .= "- Chi tiêu thiết yếu: " . number_format($total_income * 0.5, 0, ',', '.') . "₫ (50%)
";
            $response .= "- Chi tiêu cá nhân: " . number_format($total_income * 0.3, 0, ',', '.') . "₫ (30%)
";
            $response .= "- Tiết kiệm và đầu tư: " . number_format($total_income * 0.2, 0, ',', '.') . "₫ (20%)
";
        }
        
        return $response;
    }
    
    private function getSpendingAnalysis() {
        // Get spending categories
        $categories = $this->getSpendingByCategory();
        
        $response = "Phân tích chi tiêu của bạn:

";
        
        if (empty($categories)) {
            $response .= "Chưa có đủ dữ liệu chi tiêu để phân tích. Hãy thêm các giao dịch chi tiêu với danh mục cụ thể để nhận phân tích chi tiết hơn.";
            return $response;
        }
        
        foreach ($categories as $category) {
            $percentage = ($category['total'] / $this->getTotalExpenses()) * 100;
            $response .= sprintf(
                "%s: %s₫ (%.1f%%)
",
                $category['category'],
                number_format($category['total'], 0, ',', '.'),
                $percentage
            );
        }
        
        $response .= "
Cơ hội tiết kiệm:
";
        $response .= $this->getTopSavingOpportunities($categories);
        
        return $response;
    }
    
    private function getSavingAdvice() {
        $current_savings = $this->getTotalIncome() - $this->getTotalExpenses();
        $potential_savings = $this->calculatePotentialSavings();
        
        $response = "Phân tích tiết kiệm:

";
        $response .= "Tiết kiệm hiện tại hàng tháng: " . number_format($current_savings, 0, ',', '.') . "₫
";
        $response .= "Tiết kiệm tiềm năng hàng tháng: " . number_format($potential_savings, 0, ',', '.') . "₫

";
        
        $response .= "Lời khuyên tiết kiệm cá nhân:
";
        $response .= "1. Thiết lập chuyển khoản tự động cho tiết kiệm
";
        $response .= "2. Tạo mục tiêu tiết kiệm cụ thể
";
        $response .= "3. Theo dõi chi tiêu thường xuyên
";
        $response .= "4. Tìm kiếm các ưu đãi và giảm giá
";
        $response .= "5. So sánh giá cả trước khi mua sắm
";
        
        return $response;
    }
    
    private function getFinancialOverview() {
        $total_income = $this->getTotalIncome();
        $total_expenses = $this->getTotalExpenses();
        $balance = $total_income - $total_expenses;
        $ratio = $total_income > 0 ? ($total_expenses / $total_income) * 100 : 0;
        
        $response = "Tổng quan tài chính của bạn:

";
        $response .= "Tổng thu nhập: " . number_format($total_income, 0, ',', '.') . "₫
";
        $response .= "Tổng chi tiêu: " . number_format($total_expenses, 0, ',', '.') . "₫
";
        $response .= "Số dư: " . number_format($balance, 0, ',', '.') . "₫
";
        $response .= "Tỷ lệ chi tiêu/thu nhập: " . round($ratio, 1) . "%

";
        
        if ($balance < 0) {
            $response .= "⚠️ Cảnh báo: Chi tiêu của bạn đang vượt quá thu nhập. Hãy xem xét cắt giảm chi tiêu hoặc tăng thu nhập.";
        } elseif ($ratio > 70) {
            $response .= "⚠️ Lưu ý: Tỷ lệ chi tiêu/thu nhập của bạn khá cao (>70%). Hãy cố gắng giảm xuống dưới 70% để đảm bảo tài chính lành mạnh.";
        } else {
            $response .= "✅ Tốt! Bạn đang quản lý tài chính khá tốt với tỷ lệ chi tiêu/thu nhập là " . round($ratio, 1) . "%.";
        }
        
        return $response;
    }
    
    // Helper methods
    private function getTotalIncome() {
        $stmt = $this->conn->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income'");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function getTotalExpenses() {
        $stmt = $this->conn->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense'");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function getSpendingByCategory() {
        $stmt = $this->conn->prepare(
            "SELECT category, SUM(amount) as total 
             FROM transactions 
             WHERE user_id = ? AND type = 'expense' AND category IS NOT NULL AND category != ''
             GROUP BY category 
             ORDER BY total DESC"
        );
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll();
    }
    
    private function calculatePotentialSavings() {
        // Implement your logic to calculate potential savings
        // This could be based on average spending patterns
        return $this->getTotalExpenses() * 0.2; // Example: 20% of current expenses
    }
    
    private function getTopSavingOpportunities($categories) {
        $opportunities = "";
        foreach ($categories as $category) {
            if ($category['total'] > $this->getTotalExpenses() * 0.3) {
                $opportunities .= "- Xem xét giảm chi tiêu cho " . $category['category'] . "
";
            }
        }
        return $opportunities ?: "- Không phát hiện khoản chi tiêu quá mức nào
";
    }
}
?>
