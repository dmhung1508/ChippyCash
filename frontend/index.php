<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle authentication
$auth_mode = null;
if (!isset($_SESSION['user_id'])) {
    $auth_mode = isset($_GET['register']) ? 'register' : 'login';
    $pageTitle = $auth_mode === 'register' ? 'Đăng ký' : 'Đăng nhập';
    $hideNavbar = true;
    
    // Process login
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        
        if (empty($username)) {
            setFlashMessage('error', 'Vui lòng nhập tên đăng nhập');
        } elseif (empty($password)) {
            setFlashMessage('error', 'Vui lòng nhập mật khẩu');
        } else {
            $user = getUserByUsername($conn, $username);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                
                // Update last login time
                updateLastLogin($conn, $user['id']);
                
                // Redirect to dashboard
                redirectTo('index.php');
            } else {
                setFlashMessage('error', 'Tên đăng nhập hoặc mật khẩu không đúng');
            }
        }
    }
    
    // Process registration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || strlen($name) < 3) {
            setFlashMessage('error', 'Tên phải có ít nhất 3 ký tự');
        } elseif (empty($username) || strlen($username) < 3) {
            setFlashMessage('error', 'Tên đăng nhập phải có ít nhất 3 ký tự');
        } elseif (strpos($username, ' ') !== false) {
            setFlashMessage('error', 'Tên đăng nhập không được chứa khoảng trắng');
        } elseif (isUsernameExists($conn, $username)) {
            setFlashMessage('error', 'Tên đăng nhập đã được sử dụng');
        } elseif (!$email) {
            setFlashMessage('error', 'Email không hợp lệ');
        } elseif (isEmailExists($conn, $email)) {
            setFlashMessage('error', 'Email đã được sử dụng');
        } elseif (strlen($password) < 6) {
            setFlashMessage('error', 'Mật khẩu phải có ít nhất 6 ký tự');
        } elseif ($password !== $confirm_password) {
            setFlashMessage('error', 'Mật khẩu xác nhận không khớp');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if (registerUser($conn, $name, $username, $email, $hashed_password)) {
                setFlashMessage('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
                redirectTo('index.php');
            } else {
                setFlashMessage('error', 'Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại sau.');
            }
        }
    }
    
    include 'includes/header.php';
    include 'includes/auth.php';
    include 'includes/footer.php';
    exit;
}

// Main application for authenticated users
$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Update username in session
$_SESSION['user_name'] = $user['name'];

// Get financial data
$totalIncome = getTotalIncome($conn, $user_id);
$totalExpense = getTotalExpense($conn, $user_id);
$balance = $totalIncome - $totalExpense;
$ratio = getIncomeExpenseRatio($conn, $user_id);

// Get recent transactions
$recentTransactions = getRecentTransactions($conn, $user_id, 5);

// Get categories
$income_categories = getUserCategories($conn, $user_id, 'income');
$expense_categories = getUserCategories($conn, $user_id, 'expense');

$pageTitle = "Quản lý Thu Chi";
include 'includes/header.php';
?>

<div class="app-container">
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <h1>Quản lý Thu Chi</h1>
                <p class="subtitle">Kiểm soát tài chính cá nhân của bạn</p>
            </div>
            <div class="header-right">
                <button id="themeToggle" class="icon-button" aria-label="Chuyển đổi chế độ tối">
                    <i class="fas fa-moon"></i>
                </button>
                <button id="addTransactionBtn" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Thêm giao dịch
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php echo displayFlashMessages(); ?>
        
        <div class="finance-cards">
            <div class="finance-card">
                <div class="card-header">
                    <h3>Số dư hiện tại</h3>
                    <p class="card-subtitle">Tổng số dư tài chính của bạn</p>
                </div>
                <div class="card-amount <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo formatMoney($balance); ?>
                </div>
            </div>

            <div class="finance-card">
                <div class="card-header">
                    <h3>Tổng thu nhập</h3>
                    <p class="card-subtitle">Tổng tiền đã nhận</p>
                </div>
                <div class="card-amount positive">
                    <?php echo formatMoney($totalIncome); ?>
                    <i class="fas fa-arrow-up trend-icon"></i>
                </div>
            </div>

            <div class="finance-card">
                <div class="card-header">
                    <h3>Tổng chi tiêu</h3>
                    <p class="card-subtitle">Tổng tiền đã chi</p>
                </div>
                <div class="card-amount negative">
                    <?php echo formatMoney($totalExpense); ?>
                    <i class="fas fa-arrow-down trend-icon"></i>
                </div>
            </div>
        </div>

        <div class="content-tabs">
            <nav class="tab-nav">
                <button class="tab-button active" data-tab="transactions">Giao dịch</button>
                <button class="tab-button" data-tab="analytics">Phân tích</button>
                <button class="tab-button" data-tab="qa">Trợ lý AI</button>
            </nav>

            <div class="tab-content">
                <!-- Tab Giao dịch -->
                <div id="transactions" class="tab-pane active">
                    <div class="section-header">
                        <h2>Giao dịch gần đây</h2>
                        <a href="transactions.php" class="btn-link">Xem tất cả</a>
                    </div>

                    <?php if (count($recentTransactions) > 0): ?>
                        <div class="transactions-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Mô tả</th>
                                        <th>Danh mục</th>
                                        <th>Loại</th>
                                        <th>Số tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['category'] ?? 'Chung'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $transaction['type'] === 'income' ? 'income' : 'expense'; ?>">
                                                    <?php echo $transaction['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu'; ?>
                                                </span>
                                            </td>
                                            <td class="amount <?php echo $transaction['type'] === 'income' ? 'positive' : 'negative'; ?>">
                                                <?php echo formatMoney($transaction['amount']); ?>
                                            </td>
                                            <td class="actions">
                                                <button class="btn-icon edit edit-transaction-btn" title="Chỉnh sửa" 
                                                    data-id="<?php echo $transaction['id']; ?>"
                                                    data-amount="<?php echo $transaction['amount']; ?>"
                                                    data-description="<?php echo htmlspecialchars($transaction['description']); ?>"
                                                    data-type="<?php echo $transaction['type']; ?>"
                                                    data-category="<?php echo htmlspecialchars($transaction['category'] ?? ''); ?>"
                                                    data-date="<?php echo $transaction['date']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="transactions.php?delete=<?php echo $transaction['id']; ?>" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3>Chưa có giao dịch nào</h3>
                            <p>Hãy thêm giao dịch đầu tiên của bạn để bắt đầu theo dõi tài chính.</p>
                            <button id="emptyAddTransactionBtn" class="btn-primary">
                                <i class="fas fa-plus"></i> Thêm giao dịch
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Phân tích -->
                <div id="analytics" class="tab-pane">
                    <div class="section-header">
                        <h2>Phân tích tài chính</h2>
                    </div>
                    
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <h3>Tỷ lệ thu/chi</h3>
                            <div class="ratio-chart">
                                <?php 
                                $ratio = min($ratio, 100); // Limit to 100%
                                ?>
                                <div class="ratio-bar">
                                    <div class="ratio-fill" style="width: <?php echo $ratio; ?>%"></div>
                                </div>
                                <div class="ratio-labels">
                                    <span>0%</span>
                                    <span>50%</span>
                                    <span>100%</span>
                                </div>
                                <p class="ratio-value">Tỷ lệ chi/thu: <?php echo round($ratio, 1); ?>%</p>
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h3>Xu hướng chi tiêu</h3>
                            <div class="trend-chart">
                                <?php
                                $expenseTrend = getMonthlyExpenseTrend($conn, $user_id, 3);
                                if (count($expenseTrend) > 0):
                                ?>
                                    <ul class="trend-list">
                                        <?php foreach ($expenseTrend as $item): ?>
                                            <li>
                                                <span class="trend-month"><?php echo date('m/Y', mktime(0, 0, 0, $item['month'], 1, $item['year'])); ?></span>
                                                <span class="trend-amount"><?php echo formatMoney($item['total']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="chart-placeholder">Biểu đồ xu hướng chi tiêu sẽ hiển thị ở đây khi có đủ dữ liệu.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h3>Lời khuyên tài chính</h3>
                            <div class="financial-advice">
                                <div class="advice-item">
                                    <i class="fas fa-lightbulb"></i>
                                    <p>Cố gắng giữ tỷ lệ chi tiêu dưới 70% thu nhập để đảm bảo tài chính lành mạnh.</p>
                                </div>
                                <div class="advice-item">
                                    <i class="fas fa-piggy-bank"></i>
                                    <p>Tạo quỹ khẩn cấp đủ chi tiêu cho 3-6 tháng để đề phòng những tình huống bất ngờ.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Trợ lý AI -->
                <div id="qa" class="tab-pane">
                    <div class="qa-container">
                        <div class="qa-main">
                            <h2>Trợ lý tài chính AI</h2>
                            <p class="section-subtitle">Nhận lời khuyên và phân tích tài chính cá nhân</p>
                            
                            <div class="qa-chat">
                                <div class="qa-messages" id="qaMessages">
                                    <div class="message bot">
                                        <div class="message-content">
                                            Xin chào! Tôi là trợ lý tài chính AI. Tôi có thể giúp bạn phân tích chi tiêu, đưa ra lời khuyên tài chính và trả lời các câu hỏi về quản lý tài chính cá nhân. Bạn cần hỗ trợ gì?
                                        </div>
                                    </div>
                                </div>
                                <div class="qa-input">
                                    <input type="text" id="questionInput" placeholder="Nhập câu hỏi hoặc chọn từ câu hỏi phổ biến...">
                                    <button id="sendQuestion" class="btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="common-questions">
                            <h3>Câu hỏi phổ biến</h3>
                            <p class="section-subtitle">Các câu hỏi tài chính thường gặp</p>
                            
                            <div class="question-list">
                                <button class="question-item">Làm thế nào để lập ngân sách hiệu quả?</button>
                                <button class="question-item">Làm sao để tiết kiệm tiền?</button>
                                <button class="question-item">Quỹ khẩn cấp là gì?</button>
                                <button class="question-item">Làm thế nào để theo dõi chi tiêu hiệu quả?</button>
                                <button class="question-item">Làm sao để giảm chi tiêu hàng tháng?</button>
                                <button class="question-item">Quy tắc 50/30/20 là gì?</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal thêm giao dịch -->
<div id="addTransactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Thêm giao dịch mới</h2>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addTransactionForm" method="POST" action="transactions.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="amount">Số tiền:</label>
                    <input type="number" id="amount" name="amount" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô tả:</label>
                    <input type="text" id="description" name="description" required>
                </div>
                
                <div class="form-group">
                    <label for="type">Loại:</label>
                    <select id="type" name="type" required>
                        <option value="income">Thu nhập</option>
                        <option value="expense">Chi tiêu</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="category">Danh mục:</label>
                    <select id="category" name="category">
                        <option value="">Chọn danh mục</option>
                        <optgroup label="Thu nhập" id="income-categories">
                            <?php foreach ($income_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Chi tiêu" id="expense-categories" style="display:none;">
                            <?php foreach ($expense_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <p class="form-hint">
                        <a href="categories.php">Quản lý danh mục</a>
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="date">Ngày:</label>
                    <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Lưu giao dịch</button>
                    <button type="button" class="btn-secondary cancel-modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa giao dịch -->
<div id="editTransactionModal" class="modal">
   <div class="modal-content">
       <div class="modal-header">
           <h2>Chỉnh sửa giao dịch</h2>
           <button class="close-modal">&times;</button>
       </div>
       <div class="modal-body">
           <form id="editTransactionForm" method="POST" action="transactions.php">
               <input type="hidden" name="action" value="update">
               <input type="hidden" name="transaction_id" id="edit-transaction-id">
               <div class="form-group">
                   <label for="edit-amount">Số tiền:</label>
                   <input type="number" id="edit-amount" name="amount" required>
               </div>
               
               <div class="form-group">
                   <label for="edit-description">Mô tả:</label>
                   <input type="text" id="edit-description" name="description" required>
               </div>
               
               <div class="form-group">
                   <label for="edit-type">Loại:</label>
                   <select id="edit-type" name="type" required>
                       <option value="income">Thu nhập</option>
                       <option value="expense">Chi tiêu</option>
                   </select>
               </div>
               
               <div class="form-group">
                   <label for="edit-category">Danh mục:</label>
                   <select id="edit-category" name="category">
                       <option value="">Chọn danh mục</option>
                       <optgroup label="Thu nhập" id="edit-income-categories">
                           <?php foreach ($income_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                       <optgroup label="Chi tiêu" id="edit-expense-categories" style="display:none;">
                           <?php foreach ($expense_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                   </select>
               </div>
               
               <div class="form-group">
                   <label for="edit-date">Ngày:</label>
                   <input type="date" id="edit-date" name="date" required>
               </div>
               
               <div class="form-actions">
                   <button type="submit" class="btn-primary">Cập nhật</button>
                   <button type="button" class="btn-secondary cancel-modal">Hủy</button>
               </div>
           </form>
       </div>
   </div>
</div>

<?php include 'includes/footer.php'; ?>

