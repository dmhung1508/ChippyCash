<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    redirectTo('index.php');
}

$user_id = $_SESSION['user_id'];

// Process delete transaction
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $transaction_id = $_GET['delete'];
    $transaction = getTransactionById($conn, $transaction_id, $user_id);
    
    if ($transaction) {
        if (deleteTransaction($conn, $transaction_id, $user_id)) {
            setFlashMessage('success', 'Xóa giao dịch thành công!');
        } else {
            setFlashMessage('error', 'Đã xảy ra lỗi khi xóa giao dịch.');
        }
    } else {
        setFlashMessage('error', 'Giao dịch không tồn tại.');
    }
    
    // Redirect to avoid repeated deletions
    redirectTo('transactions.php');
}

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add transaction
    if ($action === 'add') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        
        if (validateRequiredFields(['amount' => $amount, 'description' => $description, 'type' => $type, 'date' => $date])) {
            if (addTransaction($conn, $user_id, $amount, $description, $type, $category, $date)) {
                setFlashMessage('success', 'Thêm giao dịch thành công!');
            } else {
                setFlashMessage('error', 'Đã xảy ra lỗi khi thêm giao dịch.');
            }
        } else {
            setFlashMessage('error', 'Vui lòng điền đầy đủ thông tin.');
        }
    }
    
    // Update transaction
    if ($action === 'update') {
        $transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        
        if (validateRequiredFields(['transaction_id' => $transaction_id, 'amount' => $amount, 'description' => $description, 'type' => $type, 'date' => $date])) {
            if (updateTransaction($conn, $transaction_id, $user_id, $amount, $description, $type, $category, $date)) {
                setFlashMessage('success', 'Cập nhật giao dịch thành công!');
            } else {
                setFlashMessage('error', 'Đã xảy ra lỗi khi cập nhật giao dịch.');
            }
        } else {
            setFlashMessage('error', 'Vui lòng điền đầy đủ thông tin.');
        }
    }
    
    // Redirect to avoid form resubmission
    redirectTo('transactions.php');
}

// Get transaction to edit
$edit_transaction = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_transaction = getTransactionById($conn, $_GET['edit'], $user_id);
    if (!$edit_transaction) {
        setFlashMessage('error', 'Giao dịch không tồn tại.');
        redirectTo('transactions.php');
    }
}

// Process filters
$filter_type = $_GET['type'] ?? 'all';
$filter_month = $_GET['month'] ?? date('m');
$filter_year = $_GET['year'] ?? date('Y');
$filter_category = $_GET['category'] ?? 'all';

// Get filtered data
$transactions = getFilteredTransactions($conn, $user_id, $filter_type, $filter_month, $filter_year, $filter_category);
$filteredIncome = getFilteredTotal($conn, $user_id, 'income', $filter_month, $filter_year, $filter_category);
$filteredExpense = getFilteredTotal($conn, $user_id, 'expense', $filter_month, $filter_year, $filter_category);
$filteredBalance = $filteredIncome - $filteredExpense;

// Get categories
$income_categories = getUserCategories($conn, $user_id, 'income');
$expense_categories = getUserCategories($conn, $user_id, 'expense');

$pageTitle = "Quản lý giao dịch";
include 'includes/header.php';
?>

<div class="app-container">
    <header class="page-header">
        <div class="header-content">
            <div class="header-left">
                <h1>Quản lý giao dịch</h1>
                <p class="subtitle">Xem và quản lý tất cả giao dịch của bạn</p>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <button id="addTransactionBtn" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Thêm giao dịch
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php echo displayFlashMessages(); ?>
        
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="type">Loại giao dịch</label>
                    <select id="type" name="type" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="income" <?php echo $filter_type === 'income' ? 'selected' : ''; ?>>Thu nhập</option>
                        <option value="expense" <?php echo $filter_type === 'expense' ? 'selected' : ''; ?>>Chi tiêu</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="month">Tháng</label>
                    <select id="month" name="month" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo $filter_month == sprintf('%02d', $i) ? 'selected' : ''; ?>>
                                Tháng <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="year">Năm</label>
                    <select id="year" name="year" onchange="this.form.submit()">
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $filter_year == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category">Danh mục</label>
                    <select id="category" name="category" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>Tất cả danh mục</option>
                        <?php 
                        $all_categories = array_merge(
                            array_column($income_categories, 'name'),
                            array_column($expense_categories, 'name')
                        );
                        foreach ($all_categories as $category): 
                        ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $filter_category === $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="finance-cards">
            <div class="finance-card">
                <div class="card-header">
                    <h3>Tổng thu nhập</h3>
                    <p class="card-subtitle">Trong khoảng thời gian đã chọn</p>
                </div>
                <div class="card-amount positive">
                    <?php echo formatMoney($filteredIncome); ?>
                </div>
            </div>

            <div class="finance-card">
                <div class="card-header">
                    <h3>Tổng chi tiêu</h3>
                    <p class="card-subtitle">Trong khoảng thời gian đã chọn</p>
                </div>
                <div class="card-amount negative">
                    <?php echo formatMoney($filteredExpense); ?>
                </div>
            </div>

            <div class="finance-card">
                <div class="card-header">
                    <h3>Số dư</h3>
                    <p class="card-subtitle">Thu - Chi</p>
                </div>
                <div class="card-amount <?php echo $filteredBalance >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo formatMoney($filteredBalance); ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="section-header">
                <h2>Danh sách giao dịch</h2>
            </div>

            <?php if (count($transactions) > 0): ?>
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
                            <?php foreach ($transactions as $transaction): ?>
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
                                        <a href="transactions.php?edit=<?php echo $transaction['id']; ?>" class="btn-icon edit" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Không tìm thấy giao dịch nào</h3>
                    <p>Không có giao dịch nào phù hợp với bộ lọc đã chọn.</p>
                    <button id="resetFilterBtn" class="btn-secondary">
                        <i class="fas fa-redo"></i> Đặt lại bộ lọc
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Form chỉnh sửa giao dịch -->
        <?php if ($edit_transaction): ?>
            <div class="content-card mt-4">
                <div class="section-header">
                    <h2>Chỉnh sửa giao dịch</h2>
                </div>
                
                <form method="POST" action="" class="transaction-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="transaction_id" value="<?php echo $edit_transaction['id']; ?>">
                    
                    <div class="form-group">
                        <label for="amount">Số tiền:</label>
                        <input type="number" id="amount" name="amount" value="<?php echo $edit_transaction['amount']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả:</label>
                        <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($edit_transaction['description']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Loại:</label>
                        <select id="type" name="type" required>
                            <option value="income" <?php echo $edit_transaction['type'] === 'income' ? 'selected' : ''; ?>>Thu nhập</option>
                            <option value="expense" <?php echo $edit_transaction['type'] === 'expense' ? 'selected' : ''; ?>>Chi tiêu</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Danh mục:</label>
                        <select id="category" name="category">
                            <option value="">Chọn danh mục</option>
                            <optgroup label="Thu nhập">
                                <?php foreach ($income_categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $edit_transaction['category'] === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Chi tiêu">
                                <?php foreach ($expense_categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $edit_transaction['category'] === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Ngày:</label>
                        <input type="date" id="date" name="date" value="<?php echo $edit_transaction['date']; ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Cập nhật</button>
                        <a href="transactions.php" class="btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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
            <form id="addTransactionForm" method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="modal-amount">Số tiền:</label>
                    <input type="number" id="modal-amount" name="amount" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-description">Mô tả:</label>
                    <input type="text" id="modal-description" name="description" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-type">Loại:</label>
                    <select id="modal-type" name="type" required>
                        <option value="income">Thu nhập</option>
                        <option value="expense">Chi tiêu</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="modal-category">Danh mục:</label>
                    <select id="modal-category" name="category">
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
                </div>
                
                <div class="form-group">
                    <label for="modal-date">Ngày:</label>
                    <input type="date" id="modal-date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Lưu giao dịch</button>
                    <button type="button" class="btn-secondary cancel-modal">Hủy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

