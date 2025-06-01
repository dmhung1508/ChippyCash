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
    
    redirectTo('transactions.php');
}

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add transaction
    if ($action === 'add') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $description = trim($_POST['description'] ?? '');
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $category = trim($_POST['category'] ?? '');
        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
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
        $description = trim($_POST['description'] ?? '');
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $category = trim($_POST['category'] ?? '');
        $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
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
    <!-- Enhanced Magical Header -->
    <header class="magical-header" style="background:var(--card-background);border-bottom:1px solid var(--border-color);padding:2rem 0;position:relative;overflow:hidden;">
        <!-- Animated Background Pattern -->
        <div class="header-pattern" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.03;background:radial-gradient(circle at 25% 25%, var(--accent-color) 2px, transparent 2px), radial-gradient(circle at 75% 75%, var(--primary-color) 1px, transparent 1px);background-size:50px 50px;animation:patternDrift 15s ease-in-out infinite;"></div>
        
        <!-- Floating Particles -->
        <div class="floating-particles" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;">
            <div class="particle" style="position:absolute;width:5px;height:5px;background:var(--accent-color);border-radius:50%;opacity:0.4;top:20%;left:10%;animation:floatUp 8s ease-in-out infinite;"></div>
            <div class="particle" style="position:absolute;width:3px;height:3px;background:var(--positive-color);border-radius:50%;opacity:0.6;top:60%;left:80%;animation:floatUp 6s ease-in-out infinite 2s;"></div>
            <div class="particle" style="position:absolute;width:4px;height:4px;background:var(--primary-color);border-radius:50%;opacity:0.5;top:40%;left:60%;animation:floatUp 10s ease-in-out infinite 1s;"></div>
        </div>

        <div class="header-content" style="max-width:1400px;margin:0 auto;padding:0 2rem;display:flex;justify-content:space-between;align-items:center;position:relative;z-index:2;">
            <div class="header-left" style="animation:slideInLeft 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <h1 class="magical-title" style="margin:0 0 8px;color:var(--primary-color);font-size:2rem;font-weight:700;position:relative;display:inline-block;">
                    💰 Quản lý giao dịch
                    <span class="title-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(45deg,transparent,rgba(102,126,234,0.3),transparent);animation:titleGlow 3s ease-in-out infinite;"></span>
                </h1>
                <p class="magical-subtitle" style="margin:0;color:var(--secondary-color);font-size:1rem;animation:fadeInUp 1s ease-out 0.3s both;">Xem và quản lý tất cả giao dịch của bạn</p>
                <div class="breadcrumb" style="margin-top:8px;display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--secondary-color);animation:fadeInUp 1s ease-out 0.6s both;">
                    <i class="fas fa-home" style="animation:iconSpin 4s ease-in-out infinite;"></i>
                    <span>Trang chủ</span>
                    <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
                    <span style="color:var(--accent-color);">Giao dịch</span>
                </div>
            </div>
            <div class="header-right" style="display:flex;gap:12px;align-items:center;animation:slideInRight 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <!-- <button id="darkModeToggle" class="btn-icon" title="Chuyển đổi chế độ" style="background:var(--hover-color);color:var(--text-secondary);border:1px solid var(--border-color);border-radius:8px;padding:12px;cursor:pointer;transition:all 0.3s ease;font-size:1rem;width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin-right:8px;">
                    <i class="fas fa-moon"></i>
                </button> -->
                <a href="index.php" class="magical-button back-btn" style="background:var(--hover-color);color:var(--primary-color);padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:500;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--border-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.background='var(--hover-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-arrow-left" style="animation:iconBounce 2s ease-in-out infinite;"></i> 
                    Quay lại
                    <span class="button-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                </a>
                <a href="export_excel.php?type=<?php echo $filter_type; ?>&month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>&category=<?php echo urlencode($filter_category); ?>" class="magical-button excel-btn" style="background:var(--positive-color);color:white;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:500;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='#16a34a';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(34,197,94,0.4)'" onmouseout="this.style.background='var(--positive-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-file-csv" style="animation:iconSpin 3s ease-in-out infinite;"></i>
                    Xuất CSV
                    <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                </a>
                <button id="addTransactionBtn" class="magical-button primary-btn" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:12px 18px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-plus" style="animation:iconSpin 3s ease-in-out infinite;"></i>
                    Thêm giao dịch
                    <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                </button>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding:2rem;max-width:1400px;margin:0 auto;">
        <?php echo displayFlashMessages(); ?>
        
        <!-- Enhanced Magical Filter Card -->
        <div class="magical-filter-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:28px;margin-bottom:32px;box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 35px rgba(0,0,0,0.12)';this.querySelector('.filter-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.filter-glow').style.opacity='0'">
            <div class="filter-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(102,126,234,0.05) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
            
            <div style="position:relative;z-index:2;">
                <h3 class="filter-title" style="margin:0 0 20px;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:12px;">
                    <div class="filter-icon" style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                        <i class="fas fa-filter" style="color:var(--accent-color);font-size:1rem;"></i>
                    </div>
                    Bộ lọc giao dịch
                </h3>
                
                <form method="GET" action="" class="magical-filter-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">
                    <div class="magical-filter-group" style="animation:fadeInUp 0.6s ease-out;">
                        <label for="type" style="display:block;margin-bottom:8px;color:var(--primary-color);font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-exchange-alt" style="color:var(--accent-color);font-size:0.8rem;"></i>
                            Loại giao dịch
                        </label>
                        <select id="type" name="type" onchange="this.form.submit()" class="magical-select" style="width:100%;padding:12px 16px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);cursor:pointer;" onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';this.style.transform='translateY(-1px)'" onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none';this.style.transform='translateY(0)'">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>📊 Tất cả</option>
                            <option value="income" <?php echo $filter_type === 'income' ? 'selected' : ''; ?>>💰 Thu nhập</option>
                            <option value="expense" <?php echo $filter_type === 'expense' ? 'selected' : ''; ?>>💸 Chi tiêu</option>
                        </select>
                    </div>
                    
                    <div class="magical-filter-group" style="animation:fadeInUp 0.6s ease-out 0.1s both;">
                        <label for="month" style="display:block;margin-bottom:8px;color:var(--primary-color);font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-alt" style="color:var(--accent-color);font-size:0.8rem;"></i>
                            Tháng
                        </label>
                        <select id="month" name="month" onchange="this.form.submit()" class="magical-select" style="width:100%;padding:12px 16px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);cursor:pointer;" onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';this.style.transform='translateY(-1px)'" onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none';this.style.transform='translateY(0)'">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo $filter_month == sprintf('%02d', $i) ? 'selected' : ''; ?>>
                                    Tháng <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="magical-filter-group" style="animation:fadeInUp 0.6s ease-out 0.2s both;">
                        <label for="year" style="display:block;margin-bottom:8px;color:var(--primary-color);font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar" style="color:var(--accent-color);font-size:0.8rem;"></i>
                            Năm
                        </label>
                        <select id="year" name="year" onchange="this.form.submit()" class="magical-select" style="width:100%;padding:12px 16px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);cursor:pointer;" onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';this.style.transform='translateY(-1px)'" onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none';this.style.transform='translateY(0)'">
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_year == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="magical-filter-group" style="animation:fadeInUp 0.6s ease-out 0.3s both;">
                        <label for="category" style="display:block;margin-bottom:8px;color:var(--primary-color);font-weight:600;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-tags" style="color:var(--accent-color);font-size:0.8rem;"></i>
                            Danh mục
                        </label>
                        <select id="category" name="category" onchange="this.form.submit()" class="magical-select" style="width:100%;padding:12px 16px;border:1px solid var(--border-color);border-radius:10px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);cursor:pointer;" onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)';this.style.transform='translateY(-1px)'" onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none';this.style.transform='translateY(0)'">
                            <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>🏷️ Tất cả danh mục</option>
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
        </div>

        <!-- Enhanced Magical Finance Summary Cards -->
        <div class="magical-finance-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:32px;">
            <!-- Total Income Card -->
            <div class="magical-finance-card income-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:28px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(34,197,94,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
                <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(34,197,94,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
                <div class="card-header" style="position:relative;z-index:2;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tổng thu nhập</h3>
                            <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Trong khoảng thời gian đã chọn</p>
                        </div>
                        <div class="card-icon" style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(34,197,94,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                            <i class="fas fa-arrow-up" style="color:var(--positive-color);font-size:1.3rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-amount" style="font-size:2.2rem;font-weight:800;margin-top:12px;color:var(--positive-color);display:flex;align-items:center;gap:8px;position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite;">
                    <?php echo formatMoney($filteredIncome); ?>
                    <i class="fas fa-trending-up" style="font-size:1.2rem;animation:iconBounce 2s ease-in-out infinite;"></i>
                </div>
            </div>

            <!-- Total Expense Card -->
            <div class="magical-finance-card expense-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:28px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out 0.1s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(239,68,68,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
                <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(239,68,68,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
                <div class="card-header" style="position:relative;z-index:2;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tổng chi tiêu</h3>
                            <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Trong khoảng thời gian đã chọn</p>
                        </div>
                        <div class="card-icon" style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite 0.5s;">
                            <i class="fas fa-arrow-down" style="color:var(--negative-color);font-size:1.3rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-amount" style="font-size:2.2rem;font-weight:800;margin-top:12px;color:var(--negative-color);display:flex;align-items:center;gap:8px;position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite 0.3s;">
                    <?php echo formatMoney($filteredExpense); ?>
                    <i class="fas fa-trending-down" style="font-size:1.2rem;animation:iconBounce 2s ease-in-out infinite 0.2s;"></i>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="magical-finance-card balance-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:28px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out 0.2s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(102,126,234,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
                <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(102,126,234,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
                <div class="card-header" style="position:relative;z-index:2;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Số dư</h3>
                            <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Thu nhập - Chi tiêu</p>
                        </div>
                        <div class="card-icon" style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite 1s;">
                            <i class="fas fa-balance-scale" style="color:var(--accent-color);font-size:1.3rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-amount" style="font-size:2.2rem;font-weight:800;margin-top:12px;color:<?php echo $filteredBalance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;display:flex;align-items:center;gap:8px;position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite 0.6s;">
                    <?php echo formatMoney($filteredBalance); ?>
                    <i class="fas fa-<?php echo $filteredBalance >= 0 ? 'smile' : 'frown'; ?>" style="font-size:1.2rem;animation:iconBounce 2s ease-in-out infinite 0.4s;"></i>
                </div>
            </div>
        </div>

        <!-- Enhanced Magical Transactions Table -->
        <div class="magical-content-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;animation:cardSlideIn 0.8s ease-out 0.4s both;" onmouseover="this.style.boxShadow='0 8px 35px rgba(0,0,0,0.12)'" onmouseout="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)'">
            <div class="magical-section-header" style="padding:24px 28px;border-bottom:1px solid var(--border-color);background:linear-gradient(135deg,var(--hover-color),rgba(248,250,252,0.8));">
                <h2 style="margin:0;color:var(--primary-color);font-size:1.4rem;font-weight:700;display:flex;align-items:center;gap:12px;">
                    <div class="table-icon" style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                        <i class="fas fa-table" style="color:var(--accent-color);font-size:1rem;"></i>
                    </div>
                    📋 Danh sách giao dịch
                </h2>
                <p style="margin:8px 0 0;color:var(--secondary-color);font-size:0.9rem;">Tổng cộng <?php echo count($transactions); ?> giao dịch</p>
            </div>

            <?php if (count($transactions) > 0): ?>
                <div class="magical-transactions-table" style="overflow:hidden;">
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead style="background:linear-gradient(135deg,var(--hover-color),rgba(248,250,252,0.9));">
                                <tr>
                                    <th style="padding:16px 20px;text-align:left;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;position:relative;">
                                        <i class="fas fa-calendar-alt" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Ngày
                                    </th>
                                    <th style="padding:16px 20px;text-align:left;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;">
                                        <i class="fas fa-file-alt" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Mô tả
                                    </th>
                                    <th style="padding:16px 20px;text-align:left;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;">
                                        <i class="fas fa-tags" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Danh mục
                                    </th>
                                    <th style="padding:16px 20px;text-align:left;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;">
                                        <i class="fas fa-exchange-alt" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Loại
                                    </th>
                                    <th style="padding:16px 20px;text-align:left;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;">
                                        <i class="fas fa-dollar-sign" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Số tiền
                                    </th>
                                    <th style="padding:16px 20px;text-align:center;font-weight:700;color:var(--primary-color);border:none;font-size:0.9rem;">
                                        <i class="fas fa-cogs" style="color:var(--accent-color);margin-right:6px;"></i>
                                        Thao tác
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                    <tr class="magical-table-row" style="border-bottom:1px solid var(--border-color);transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);animation:tableRowSlideIn 0.6s ease-out <?php echo $index * 0.05; ?>s both;" onmouseover="this.style.background='var(--hover-color)';this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent';this.style.transform='translateX(0)'">
                                        <td style="padding:18px 20px;border:none;color:var(--secondary-color);font-size:0.9rem;font-weight:500;">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <div style="width:8px;height:8px;border-radius:50%;background:var(--accent-color);opacity:0.6;"></div>
                                                <?php echo date('d/m/Y', strtotime($transaction['date'])); ?>
                                            </div>
                                        </td>
                                        <td style="padding:18px 20px;border:none;color:var(--primary-color);font-weight:600;font-size:0.9rem;">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <i class="fas fa-receipt" style="color:var(--accent-color);font-size:0.8rem;opacity:0.6;"></i>
                                                <?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td style="padding:18px 20px;border:none;color:var(--secondary-color);font-size:0.9rem;">
                                            <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:var(--hover-color);border-radius:12px;">
                                                <i class="fas fa-tag" style="color:var(--accent-color);font-size:0.7rem;"></i>
                                                <?php echo htmlspecialchars($transaction['category'] ?? 'Chung'); ?>
                                            </div>
                                        </td>
                                        <td style="padding:18px 20px;border:none;">
                                            <span class="magical-badge" style="padding:6px 12px;border-radius:20px;font-size:0.8rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;background:<?php echo $transaction['type'] === 'income' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;border:1px solid <?php echo $transaction['type'] === 'income' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)'; ?>;">
                                                <i class="fas fa-<?php echo $transaction['type'] === 'income' ? 'arrow-up' : 'arrow-down'; ?>" style="font-size:0.7rem;"></i>
                                                <?php echo $transaction['type'] === 'income' ? '💰 Thu nhập' : '💸 Chi tiêu'; ?>
                                            </span>
                                        </td>
                                        <td style="padding:18px 20px;border:none;font-weight:700;font-size:1rem;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <?php echo formatMoney($transaction['amount']); ?>
                                                <i class="fas fa-<?php echo $transaction['type'] === 'income' ? 'trending-up' : 'trending-down'; ?>" style="font-size:0.8rem;opacity:0.7;"></i>
                                            </div>
                                        </td>
                                        <td style="padding:18px 20px;border:none;text-align:center;">
                                            <div class="magical-actions" style="display:flex;gap:8px;justify-content:center;">
                                                <button class="magical-btn-icon edit-transaction-btn" title="Chỉnh sửa" style="background:var(--accent-color);color:white;padding:8px;border:none;border-radius:8px;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;cursor:pointer;position:relative;overflow:hidden;" 
                                                    data-id="<?php echo $transaction['id']; ?>"
                                                    data-amount="<?php echo $transaction['amount']; ?>"
                                                    data-description="<?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-type="<?php echo $transaction['type']; ?>"
                                                    data-category="<?php echo htmlspecialchars($transaction['category'] ?? ''); ?>"
                                                    data-date="<?php echo $transaction['date']; ?>"
                                                    onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(102,126,234,0.3)'" 
                                                    onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                                </button>
                                                <a href="transactions.php?delete=<?php echo $transaction['id']; ?>" class="magical-btn-icon delete" title="Xóa" style="background:#ef4444;color:white;padding:8px;border-radius:8px;text-decoration:none;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;" 
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?');"
                                                    onmouseover="this.style.background='#dc2626';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(239,68,68,0.3)'" 
                                                    onmouseout="this.style.background='#ef4444';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="magical-empty-state" style="text-align:center;padding:60px 40px;background:var(--card-background);position:relative;">
                    <div class="empty-icon" style="font-size:4rem;color:var(--secondary-color);margin-bottom:20px;opacity:0.6;animation:iconFloat 3s ease-in-out infinite;">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 style="margin:0 0 12px;color:var(--primary-color);font-size:1.3rem;font-weight:700;">Không tìm thấy giao dịch nào</h3>
                    <p style="margin:0 0 28px;color:var(--secondary-color);font-size:1rem;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.5;">Không có giao dịch nào phù hợp với bộ lọc đã chọn. Hãy thử điều chỉnh bộ lọc hoặc thêm giao dịch mới.</p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <button id="resetFilterBtn" class="magical-button secondary" style="background:var(--hover-color);color:var(--primary-color);border:1px solid var(--border-color);border-radius:12px;padding:12px 20px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--border-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.background='var(--hover-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                            <i class="fas fa-redo" style="animation:iconSpin 3s ease-in-out infinite;"></i> 
                            Đặt lại bộ lọc
                            <span class="button-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                        </button>
                        <button id="addNewTransactionBtn" class="magical-button primary" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:12px 20px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                            <i class="fas fa-plus" style="animation:iconBounce 2s ease-in-out infinite;"></i>
                            Thêm giao dịch mới
                            <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add/Edit Transaction Modal -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Thêm giao dịch mới</h2>
            <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="transactionForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="transaction_id" id="transactionId">
                
                <div class="form-group">
                    <label for="transactionType">Loại giao dịch <span style="color: red;">*</span></label>
                    <select id="transactionType" name="type" required onchange="updateCategoryOptions()">
                        <option value="">-- Chọn loại --</option>
                        <option value="income">Thu nhập</option>
                        <option value="expense">Chi tiêu</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transactionCategory">Danh mục</label>
                    <select id="transactionCategory" name="category">
                        <option value="">-- Chọn danh mục --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transactionAmount">Số tiền <span style="color: red;">*</span></label>
                    <input type="number" id="transactionAmount" name="amount" step="1" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="transactionDescription">Mô tả <span style="color: red;">*</span></label>
                    <input type="text" id="transactionDescription" name="description" required placeholder="VD: Ăn trưa, Lương tháng..." accept-charset="UTF-8">
                </div>
                
                <div class="form-group">
                    <label for="transactionDate">Ngày <span style="color: red;">*</span></label>
                    <input type="date" id="transactionDate" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Categories data for JavaScript
const incomeCategories = <?php echo json_encode(array_column($income_categories, 'name')); ?>;
const expenseCategories = <?php echo json_encode(array_column($expense_categories, 'name')); ?>;

// Modal functions
function openModal() {
    document.getElementById('transactionModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('transactionModal').style.display = 'none';
    resetForm();
}

function resetForm() {
    document.getElementById('transactionForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Thêm giao dịch mới';
    document.getElementById('transactionDate').value = '<?php echo date('Y-m-d'); ?>';
    updateCategoryOptions();
}

// Update category options based on transaction type
function updateCategoryOptions() {
    const typeSelect = document.getElementById('transactionType');
    const categorySelect = document.getElementById('transactionCategory');
    const selectedType = typeSelect.value;
    
    // Clear current options
    categorySelect.innerHTML = '<option value="">-- Chọn danh mục --</option>';
    
    if (selectedType === 'income') {
        incomeCategories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categorySelect.appendChild(option);
        });
    } else if (selectedType === 'expense') {
        expenseCategories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categorySelect.appendChild(option);
        });
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add transaction button
    document.getElementById('addTransactionBtn').addEventListener('click', function() {
        resetForm();
        openModal();
    });
    
    // Edit transaction buttons
    document.querySelectorAll('.edit-transaction-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const amount = this.dataset.amount;
            const description = this.dataset.description;
            const type = this.dataset.type;
            const category = this.dataset.category;
            const date = this.dataset.date;
            
            document.getElementById('formAction').value = 'update';
            document.getElementById('transactionId').value = id;
            document.getElementById('modalTitle').textContent = 'Chỉnh sửa giao dịch';
            document.getElementById('transactionAmount').value = amount;
            document.getElementById('transactionDescription').value = description;
            document.getElementById('transactionType').value = type;
            document.getElementById('transactionDate').value = date;
            
            updateCategoryOptions();
            
            // Set category after options are updated
            setTimeout(() => {
                document.getElementById('transactionCategory').value = category;
            }, 10);
            
            openModal();
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Initialize category options
    updateCategoryOptions();
});
</script>

<!-- Dark Mode & Dropdown JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark Mode Toggle
    const darkModeBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Check saved theme
    const savedTheme = localStorage.getItem('darkMode');
    let isDarkMode = savedTheme === 'true';
    
    // Apply initial theme
    if (isDarkMode) {
        body.classList.add('dark-mode');
        updateButton(true);
    }
    
    // Toggle function
    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        
        if (isDarkMode) {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }
        
        updateButton(isDarkMode);
        localStorage.setItem('darkMode', isDarkMode.toString());
        console.log(`Theme switched to: ${isDarkMode ? 'dark' : 'light'}`);
    }
    
    // Update button icon
    function updateButton(dark) {
        if (darkModeBtn) {
            const icon = darkModeBtn.querySelector('i');
            icon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Add click event for dark mode
    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', toggleDarkMode);
        console.log("✅ Dark mode toggle ready!");
    }
    
    // Dropdown Menu Handler
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownToggle && dropdownMenu) {
        // Toggle dropdown on click
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isVisible = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = isVisible ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdownMenu.style.display = 'none';
            }
        });
        
        console.log("✅ Dropdown menu ready!");
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<!-- Enhanced Magical CSS Animations -->
<style>
/* ===== TRANSACTIONS PAGE MAGICAL ANIMATIONS ===== */

/* Header Animations */
@keyframes patternDrift {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(0.5deg); }
    50% { transform: translateY(-4px) rotate(-0.5deg); }
    75% { transform: translateY(-12px) rotate(0.3deg); }
}

@keyframes floatUp {
    0%, 100% { 
        transform: translateY(0px) scale(1); 
        opacity: 0.4; 
    }
    25% { 
        transform: translateY(-15px) scale(1.1); 
        opacity: 0.7; 
    }
    50% { 
        transform: translateY(-30px) scale(0.9); 
        opacity: 0.3; 
    }
    75% { 
        transform: translateY(-20px) scale(1.2); 
        opacity: 0.6; 
    }
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-60px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(60px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes titleGlow {
    0%, 100% { transform: translateX(-100%); opacity: 0; }
    50% { transform: translateX(100%); opacity: 1; }
}

@keyframes iconSpin {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(90deg) scale(1.1); }
    50% { transform: rotate(180deg) scale(1); }
    75% { transform: rotate(270deg) scale(1.1); }
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0px) scale(1); }
    25% { transform: translateY(-4px) scale(1.05); }
    50% { transform: translateY(-8px) scale(1.1); }
    75% { transform: translateY(-4px) scale(1.05); }
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-6px) rotate(3deg); }
    66% { transform: translateY(-3px) rotate(-3deg); }
}

@keyframes buttonShine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

@keyframes cardSlideIn {
    0% { opacity: 0; transform: translateY(40px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes numberPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}

@keyframes tableRowSlideIn {
    0% { opacity: 0; transform: translateX(-20px) scale(0.98); }
    100% { opacity: 1; transform: translateX(0) scale(1); }
}

@keyframes cardGlowPulse {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.02); }
}

.magical-button:hover .button-ripple {
    width: 100px !important;
    height: 100px !important;
}

.magical-btn-icon:hover .btn-ripple {
    width: 60px !important;
    height: 60px !important;
}

.magical-finance-card:hover .card-glow {
    animation: cardGlowPulse 2s ease-in-out infinite;
}

.magical-table-row:hover {
    background: var(--hover-color) !important;
    transform: translateX(6px) scale(1.005) !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
}

.magical-select:hover {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 6px 20px rgba(102,126,234,0.15);
}

.magical-select:focus {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 0 0 4px rgba(102,126,234,0.15) !important;
}

@media (max-width: 768px) {
    .floating-particles { display: none; }
    .magical-finance-cards { grid-template-columns: 1fr !important; gap: 16px !important; }
    .magical-filter-form { grid-template-columns: 1fr !important; gap: 16px !important; }
    .magical-header { padding: 1.5rem 0 !important; }
    .header-content { flex-direction: column !important; gap: 20px !important; text-align: center !important; }
    .magical-transactions-table { font-size: 0.85rem !important; }
    .magical-table-row td { padding: 12px 16px !important; }
    .magical-actions { flex-direction: column !important; gap: 6px !important; }
}

@media (prefers-reduced-motion: reduce) {
    * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
    .floating-particles, .header-pattern { display: none !important; }
}

/* Vietnamese Font Support */
body, input, textarea, select, button {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji" !important;
}

/* Ensure UTF-8 text input rendering */
input[type="text"], textarea {
    unicode-bidi: normal !important;
    text-rendering: optimizeLegibility !important;
}
</style>
<!-- Enhanced Magical JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const magicalButtons = document.querySelectorAll('.magical-button');
    magicalButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = this.querySelector('.button-ripple');
            if (ripple) {
                ripple.style.width = '120px';
                ripple.style.height = '120px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 600);
            }
            this.style.transform = 'scale(0.95)';
            setTimeout(() => { this.style.transform = ''; }, 150);
        });
    });
    
    const tableRows = document.querySelectorAll('.magical-table-row');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    const filterSelects = document.querySelectorAll('.magical-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.style.transform = 'scale(1.05)';
            setTimeout(() => { this.style.transform = ''; }, 200);
        });
    });
    
    const financeCards = document.querySelectorAll('.magical-finance-card');
    financeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '1';
                glow.style.animation = 'cardGlowPulse 2s ease-in-out infinite';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '0';
                glow.style.animation = '';
            }
        });
    });
    
    document.documentElement.style.scrollBehavior = 'smooth';
    
    window.addEventListener('load', function() {
        document.body.style.opacity = '1';
        const mainElements = document.querySelectorAll('.magical-header, .magical-filter-card, .magical-finance-cards, .magical-content-card');
        mainElements.forEach((element, index) => {
            element.style.animation = `fadeInUp 0.8s ease-out ${index * 0.1}s both`;
        });
    });
});
</script>

