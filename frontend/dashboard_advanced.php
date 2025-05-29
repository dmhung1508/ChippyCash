<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    redirectTo('index.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Get financial data
$totalIncome = getTotalIncome($conn, $user_id);
$totalExpense = getTotalExpense($conn, $user_id);
$balance = $totalIncome - $totalExpense;

// Get monthly data for current month
$currentMonth = date('n');
$currentYear = date('Y');
$monthlyIncome = getTotalIncome($conn, $user_id, $currentMonth, $currentYear);
$monthlyExpense = getTotalExpense($conn, $user_id, $currentMonth, $currentYear);

// Get recent transactions
$recentTransactions = getRecentTransactions($conn, $user_id, 8);

// Get categories
$income_categories = getUserCategories($conn, $user_id, 'income');
$expense_categories = getUserCategories($conn, $user_id, 'expense');

// Get monthly data for trends (6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('n', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $monthlyData[] = [
        'month' => date('M Y', strtotime("-$i months")),
        'income' => getTotalIncome($conn, $user_id, $month, $year),
        'expense' => getTotalExpense($conn, $user_id, $month, $year)
    ];
}

$pageTitle = "Dashboard Nâng Cao";
include 'includes/header.php';
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    --danger-gradient: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    --warning-gradient: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    --info-gradient: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    --purple-gradient: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-light: 0 4px 20px rgba(0, 0, 0, 0.1);
    --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.15);
    --shadow-heavy: 0 12px 40px rgba(0, 0, 0, 0.2);
}

.dashboard-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.hero-section {
    background: var(--primary-gradient);
    color: white;
    padding: 3rem 0;
    position: relative;
    overflow: hidden;
}

.hero-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.1;
    background-image: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.4"><circle cx="30" cy="30" r="4"/></g></svg>');
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

.hero-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 3rem;
    align-items: center;
}

.welcome-section h1 {
    font-size: 3rem;
    font-weight: 800;
    margin: 0 0 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    background: linear-gradient(45deg, #ffffff, #e2e8f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.quick-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.quick-stat {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
}

.quick-stat:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
}

.glass-button {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 12px 20px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.glass-button:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
}

.finance-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    margin: -4rem 2rem 3rem;
    position: relative;
    z-index: 3;
}

.finance-card {
    border-radius: 24px;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
    box-shadow: var(--shadow-light);
}

.finance-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-heavy);
}

.finance-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transition: all 0.4s ease;
}

.finance-card:hover::before {
    transform: scale(1.1);
}

.card-content {
    position: relative;
    z-index: 2;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.card-amount {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 1rem;
    line-height: 1;
}

.trend-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    opacity: 0.9;
}

.balance-card {
    background: var(--primary-gradient);
    color: white;
}

.income-card {
    background: var(--success-gradient);
    color: white;
}

.expense-card {
    background: var(--danger-gradient);
    color: white;
}

.savings-card {
    background: var(--purple-gradient);
    color: white;
}

.main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 3rem;
}

.advanced-tabs {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    margin-bottom: 3rem;
}

.tab-nav {
    background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 12px;
    display: flex;
    gap: 8px;
    border-bottom: 1px solid var(--border-color);
}

.tab-button {
    flex: 1;
    padding: 16px 24px;
    border: none;
    background: transparent;
    color: var(--secondary-color);
    border-radius: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-button.active {
    background: white;
    color: var(--primary-color);
    box-shadow: var(--shadow-light);
}

.tab-button:hover:not(.active) {
    background: rgba(255,255,255,0.5);
    color: var(--primary-color);
}

.tab-content {
    padding: 3rem;
}

.tab-pane {
    display: none;
    animation: fadeInUp 0.5s ease-out;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.quick-action-btn {
    border: none;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    color: white;
    font-weight: 600;
}

.quick-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-medium);
}

.quick-action-btn i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.overview-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 3rem;
}

.overview-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow-light);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.overview-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
}

.recent-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.recent-item:hover {
    background: var(--hover-color);
    margin: 0 -16px;
    padding: 16px;
    border-radius: 12px;
}

.transaction-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.health-score-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    border-radius: 24px;
    padding: 3rem;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
}

.health-score-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
}

.score-circle {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    font-size: 1.8rem;
    font-weight: 800;
}

.transactions-grid {
    display: grid;
    gap: 1rem;
}

.transaction-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.transaction-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
}

.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.chart-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
}

.budget-overview {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 20px;
    padding: 3rem;
    margin-bottom: 3rem;
}

.budget-stat {
    text-align: center;
    padding: 1rem;
}

.budget-item {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.budget-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
}

.progress-bar {
    background: var(--hover-color);
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    margin-top: 1rem;
}

.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.8s ease;
}

.ai-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.ai-feature-card {
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.3s ease;
    cursor: pointer;
    color: white;
}

.ai-feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-medium);
}

.chat-container {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    height: 600px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

.chat-header {
    background: linear-gradient(90deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.chat-messages {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    background: white;
}

.chat-input {
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
    background: var(--hover-color);
}

.message {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.message-content {
    background: var(--hover-color);
    border-radius: 16px;
    padding: 1rem 1.5rem;
    max-width: 80%;
}

.ai-message .message-content {
    background: linear-gradient(135deg, #e0f2fe 0%, #b3e5fc 100%);
}

.user-message {
    flex-direction: row-reverse;
}

.user-message .message-content {
    background: var(--primary-gradient);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 20px;
}

.empty-icon {
    font-size: 4rem;
    color: var(--secondary-color);
    margin-bottom: 2rem;
    opacity: 0.6;
}

@media (max-width: 768px) {
    .hero-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }
    
    .welcome-section h1 {
        font-size: 2rem;
    }
    
    .quick-stats {
        justify-content: center;
    }
    
    .finance-overview {
        grid-template-columns: 1fr;
        margin: -2rem 1rem 2rem;
    }
    
    .overview-grid {
        grid-template-columns: 1fr;
    }
    
    .tab-nav {
        flex-direction: column;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-pattern"></div>
        <div class="hero-content">
            <div class="hero-grid">
                <div class="hero-left">
                    <div class="welcome-section">
                        <h1>Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h1>
                        <p style="font-size:1.3rem;opacity:0.9;margin:0;">Chào mừng bạn đến với Dashboard tài chính nâng cao</p>
                    </div>
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <div style="font-size:0.9rem;opacity:0.8;margin-bottom:8px;">Số dư hiện tại</div>
                            <div style="font-size:1.8rem;font-weight:700;"><?php echo formatMoney($balance); ?></div>
                        </div>
                        <div class="quick-stat">
                            <div style="font-size:0.9rem;opacity:0.8;margin-bottom:8px;">Giao dịch tháng này</div>
                            <div style="font-size:1.8rem;font-weight:700;"><?php echo getTotalTransactionCount($conn, $user_id, $currentMonth, $currentYear); ?></div>
                        </div>
                        <div class="quick-stat">
                            <div style="font-size:0.9rem;opacity:0.8;margin-bottom:8px;">Tỷ lệ tiết kiệm</div>
                            <div style="font-size:1.8rem;font-weight:700;"><?php echo $monthlyIncome > 0 ? round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1) : 0; ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="hero-right">
                    <div style="text-align:right;margin-bottom:2rem;">
                        <div style="font-size:1.2rem;font-weight:600;margin-bottom:4px;"><?php echo date('l, d F Y'); ?></div>
                        <div style="font-size:1rem;opacity:0.8;" id="currentTime"></div>
                    </div>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:flex-end;">
                        <button id="themeToggle" class="glass-button">
                            <i class="fas fa-moon"></i>
                        </button>
                        <button id="addTransactionBtn" class="glass-button">
                            <i class="fas fa-plus"></i> Thêm giao dịch
                        </button>
                        <a href="transactions.php?export=excel" class="glass-button">
                            <i class="fas fa-download"></i> Xuất báo cáo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Finance Overview Cards -->
    <div class="finance-overview">
        <!-- Balance Card -->
        <div class="finance-card balance-card">
            <div class="card-content">
                <div class="card-header">
                    <div>
                        <h3 style="margin:0;font-size:1.1rem;opacity:0.9;font-weight:500;">Số dư hiện tại</h3>
                        <p style="margin:0;font-size:0.9rem;opacity:0.7;">Tổng tài sản ròng</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="card-amount"><?php echo formatMoney($balance); ?></div>
                <div class="trend-indicator">
                    <i class="fas fa-arrow-<?php echo $balance >= 0 ? 'up' : 'down'; ?>" style="color:<?php echo $balance >= 0 ? '#4ade80' : '#f87171'; ?>;"></i>
                    <span><?php echo $balance >= 0 ? 'Tài chính ổn định' : 'Cần cân bằng chi tiêu'; ?></span>
                </div>
            </div>
        </div>

        <!-- Income Card -->
        <div class="finance-card income-card">
            <div class="card-content">
                <div class="card-header">
                    <div>
                        <h3 style="margin:0;font-size:1.1rem;opacity:0.9;font-weight:500;">Tổng thu nhập</h3>
                        <p style="margin:0;font-size:0.9rem;opacity:0.7;">Tất cả nguồn thu</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
                <div class="card-amount"><?php echo formatMoney($totalIncome); ?></div>
                <div class="trend-indicator">
                    <i class="fas fa-calendar"></i>
                    <span>Tháng này: <?php echo formatMoney($monthlyIncome); ?></span>
                </div>
            </div>
        </div>

        <!-- Expense Card -->
        <div class="finance-card expense-card">
            <div class="card-content">
                <div class="card-header">
                    <div>
                        <h3 style="margin:0;font-size:1.1rem;opacity:0.9;font-weight:500;">Tổng chi tiêu</h3>
                        <p style="margin:0;font-size:0.9rem;opacity:0.7;">Tất cả khoản chi</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
                <div class="card-amount"><?php echo formatMoney($totalExpense); ?></div>
                <div class="trend-indicator">
                    <i class="fas fa-calendar"></i>
                    <span>Tháng này: <?php echo formatMoney($monthlyExpense); ?></span>
                </div>
            </div>
        </div>

        <!-- Savings Goal Card -->
        <div class="finance-card savings-card">
            <div class="card-content">
                <div class="card-header">
                    <div>
                        <h3 style="margin:0;font-size:1.1rem;opacity:0.9;font-weight:500;">Mục tiêu tiết kiệm</h3>
                        <p style="margin:0;font-size:0.9rem;opacity:0.7;">20% thu nhập tháng</p>
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                </div>
                <?php 
                $savingsGoal = $monthlyIncome * 0.2;
                $actualSavings = $monthlyIncome - $monthlyExpense;
                $savingsProgress = $savingsGoal > 0 ? min(($actualSavings / $savingsGoal) * 100, 100) : 0;
                ?>
                <div class="card-amount"><?php echo formatMoney($actualSavings); ?></div>
                <div style="margin-bottom:1rem;">
                    <div class="progress-bar" style="background:rgba(255,255,255,0.2);height:8px;">
                        <div class="progress-fill" style="background:rgba(255,255,255,0.8);width:<?php echo $savingsProgress; ?>%;"></div>
                    </div>
                </div>
                <div class="trend-indicator">
                    <i class="fas fa-target"></i>
                    <span><?php echo round($savingsProgress, 1); ?>% hoàn thành mục tiêu</span>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <?php echo displayFlashMessages(); ?>
        
        <!-- Advanced Tabs -->
        <div class="advanced-tabs">
            <nav class="tab-nav">
                <button class="tab-button active" data-tab="overview">
                    <i class="fas fa-tachometer-alt"></i>
                    Tổng quan
                </button>
                <button class="tab-button" data-tab="transactions">
                    <i class="fas fa-exchange-alt"></i>
                    Giao dịch
                </button>
                <button class="tab-button" data-tab="analytics">
                    <i class="fas fa-chart-line"></i>
                    Phân tích
                </button>
                <button class="tab-button" data-tab="budget">
                    <i class="fas fa-calculator"></i>
                    Ngân sách
                </button>
                <button class="tab-button" data-tab="ai">
                    <i class="fas fa-robot"></i>
                    Trợ lý AI
                </button>
            </nav>

            <div class="tab-content">
                <!-- Overview Tab -->
                <div id="overview" class="tab-pane active">
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <button class="quick-action-btn" style="background:var(--info-gradient);" onclick="document.getElementById('addTransactionBtn').click()">
                            <i class="fas fa-plus-circle"></i>
                            <div style="font-size:1.1rem;font-weight:700;">Thêm giao dịch</div>
                            <div style="font-size:0.9rem;opacity:0.8;">Nhanh chóng</div>
                        </button>
                        <button class="quick-action-btn" style="background:var(--success-gradient);" onclick="window.location.href='transactions.php'">
                            <i class="fas fa-chart-bar"></i>
                            <div style="font-size:1.1rem;font-weight:700;">Xem báo cáo</div>
                            <div style="font-size:0.9rem;opacity:0.8;">Chi tiết</div>
                        </button>
                        <button class="quick-action-btn" style="background:var(--warning-gradient);" onclick="window.location.href='settings.php'">
                            <i class="fas fa-cog"></i>
                            <div style="font-size:1.1rem;font-weight:700;">Cài đặt</div>
                            <div style="font-size:0.9rem;opacity:0.8;">Tùy chỉnh</div>
                        </button>
                        <button class="quick-action-btn" style="background:var(--purple-gradient);" onclick="window.location.href='transactions.php?export=excel'">
                            <i class="fas fa-download"></i>
                            <div style="font-size:1.1rem;font-weight:700;">Xuất dữ liệu</div>
                            <div style="font-size:0.9rem;opacity:0.8;">Excel/PDF</div>
                        </button>
                    </div>

                    <!-- Overview Grid -->
                    <div class="overview-grid">
                        <!-- Recent Activity -->
                        <div class="overview-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
                                <h3 style="margin:0;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Hoạt động gần đây</h3>
                                <a href="transactions.php" style="color:var(--accent-color);text-decoration:none;font-weight:600;">Xem tất cả →</a>
                            </div>
                            <?php if (count($recentTransactions) > 0): ?>
                                <div class="recent-list">
                                    <?php foreach (array_slice($recentTransactions, 0, 6) as $transaction): ?>
                                        <div class="recent-item">
                                            <div style="display:flex;align-items:center;gap:16px;">
                                                <div class="transaction-icon" style="background:<?php echo $transaction['type'] === 'income' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                                    <i class="fas fa-<?php echo $transaction['type'] === 'income' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight:600;color:var(--primary-color);margin-bottom:4px;"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                                    <div style="font-size:0.9rem;color:var(--secondary-color);"><?php echo date('d/m/Y', strtotime($transaction['date'])); ?> • <?php echo htmlspecialchars($transaction['category'] ?? 'Chung'); ?></div>
                                                </div>
                                            </div>
                                            <div style="font-weight:700;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                                <?php echo formatMoney($transaction['amount']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state" style="padding:2rem;">
                                    <i class="fas fa-receipt" style="font-size:3rem;color:var(--secondary-color);opacity:0.5;margin-bottom:1rem;"></i>
                                    <p style="color:var(--secondary-color);margin:0;">Chưa có giao dịch nào</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Mini Chart -->
                        <div class="overview-card">
                            <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Xu hướng 6 tháng</h3>
                            <canvas id="miniTrendChart" width="300" height="250"></canvas>
                        </div>
                    </div>

                    <!-- Financial Health Score -->
                    <div class="health-score-card">
                        <div style="display:grid;grid-template-columns:1fr auto;gap:3rem;align-items:center;">
                            <div style="position:relative;z-index:2;">
                                <h3 style="margin:0 0 1.5rem;font-size:2rem;font-weight:700;">Điểm số sức khỏe tài chính</h3>
                                <?php 
                                $healthScore = 0;
                                if ($totalIncome > 0) {
                                    $savingsRate = (($totalIncome - $totalExpense) / $totalIncome) * 100;
                                    $healthScore = min(100, max(0, $savingsRate * 2 + 40));
                                }
                                ?>
                                <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;">
                                    <div style="font-size:4rem;font-weight:800;"><?php echo round($healthScore); ?></div>
                                    <div>
                                        <div style="font-size:1.5rem;font-weight:600;">
                                            <?php 
                                            if ($healthScore >= 80) echo "Xuất sắc 🎉";
                                            elseif ($healthScore >= 60) echo "Tốt 👍";
                                            elseif ($healthScore >= 40) echo "Trung bình ⚠️";
                                            else echo "Cần cải thiện 🚨";
                                            ?>
                                        </div>
                                        <div style="font-size:1rem;opacity:0.8;">Trên thang điểm 100</div>
                                    </div>
                                </div>
                                <div style="font-size:1.1rem;opacity:0.9;line-height:1.6;">
                                    <?php 
                                    if ($healthScore >= 80) {
                                        echo "Tuyệt vời! Bạn đang quản lý tài chính rất tốt. Hãy tiếp tục duy trì thói quen này.";
                                    } elseif ($healthScore >= 60) {
                                        echo "Khá tốt! Bạn đang trên đường đúng. Hãy tiếp tục cải thiện tỷ lệ tiết kiệm.";
                                    } elseif ($healthScore >= 40) {
                                        echo "Cần chú ý hơn đến việc cân bằng thu chi. Hãy xem xét cắt giảm chi tiêu không cần thiết.";
                                    } else {
                                        echo "Nên xem xét lại kế hoạch tài chính. Tập trung vào việc tăng thu nhập và giảm chi tiêu.";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="score-circle" style="background:conic-gradient(from 0deg, #4ade80 0deg <?php echo ($healthScore/100)*360; ?>deg, rgba(255,255,255,0.2) <?php echo ($healthScore/100)*360; ?>deg 360deg);">
                                <div style="width:100px;height:100px;border-radius:50%;background:#1e293b;display:flex;align-items:center;justify-content:center;">
                                    <?php echo round($healthScore); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Tab -->
                <div id="transactions" class="tab-pane">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
                        <div>
                            <h2 style="margin:0;color:var(--primary-color);font-size:2rem;font-weight:700;">Quản lý giao dịch</h2>
                            <p style="margin:8px 0 0;color:var(--secondary-color);font-size:1.1rem;">Theo dõi và quản lý tất cả giao dịch của bạn</p>
                        </div>
                        <div style="display:flex;gap:12px;">
                            <button class="glass-button" style="background:var(--hover-color);color:var(--primary-color);border:1px solid var(--border-color);">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <a href="transactions.php" class="glass-button" style="background:var(--accent-color);color:white;">
                                <i class="fas fa-external-link-alt"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>

                    <?php if (count($recentTransactions) > 0): ?>
                        <div class="transactions-grid">
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <div class="transaction-card">
                                    <div style="display:grid;grid-template-columns:auto 1fr auto auto;gap:1.5rem;align-items:center;">
                                        <div class="transaction-icon" style="width:56px;height:56px;border-radius:16px;background:<?php echo $transaction['type'] === 'income' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                            <i class="fas fa-<?php echo $transaction['type'] === 'income' ? 'arrow-up' : 'arrow-down'; ?>" style="font-size:1.4rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;color:var(--primary-color);margin-bottom:6px;font-size:1.2rem;"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                            <div style="display:flex;gap:20px;font-size:1rem;color:var(--secondary-color);">
                                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($transaction['date'])); ?></span>
                                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($transaction['category'] ?? 'Chung'); ?></span>
                                            </div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-weight:700;font-size:1.3rem;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                                <?php echo formatMoney($transaction['amount']); ?>
                                            </div>
                                            <div style="font-size:0.9rem;color:var(--secondary-color);margin-top:4px;">
                                                <?php echo $transaction['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu'; ?>
                                            </div>
                                        </div>
                                        <div style="display:flex;gap:8px;">
                                            <button class="edit-transaction-btn" 
                                                data-id="<?php echo $transaction['id']; ?>"
                                                data-amount="<?php echo $transaction['amount']; ?>"
                                                data-description="<?php echo htmlspecialchars($transaction['description']); ?>"
                                                data-type="<?php echo $transaction['type']; ?>"
                                                data-category="<?php echo htmlspecialchars($transaction['category'] ?? ''); ?>"
                                                data-date="<?php echo $transaction['date']; ?>"
                                                style="background:var(--accent-color);color:white;border:none;border-radius:8px;padding:10px;transition:all 0.2s ease;cursor:pointer;" 
                                                title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="transactions.php?delete=<?php echo $transaction['id']; ?>" style="background:#ef4444;color:white;border-radius:8px;padding:10px;text-decoration:none;transition:all 0.2s ease;" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3 style="margin:0 0 1rem;color:var(--primary-color);font-size:1.8rem;font-weight:600;">Chưa có giao dịch nào</h3>
                            <p style="margin:0 0 2rem;color:var(--secondary-color);font-size:1.1rem;">Hãy thêm giao dịch đầu tiên để bắt đầu theo dõi tài chính của bạn.</p>
                            <button id="emptyAddTransactionBtn" class="glass-button" style="background:var(--accent-color);color:white;padding:16px 32px;font-size:1.1rem;">
                                <i class="fas fa-plus"></i> Thêm giao dịch đầu tiên
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics" class="tab-pane">
                    <div style="margin-bottom:3rem;">
                        <h2 style="margin:0;color:var(--primary-color);font-size:2rem;font-weight:700;">Phân tích tài chính nâng cao</h2>
                        <p style="margin:8px 0 0;color:var(--secondary-color);font-size:1.1rem;">Thông tin chi tiết về tình hình tài chính và xu hướng</p>
                    </div>
                    
                    <!-- Advanced Stats -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:3rem;">
                        <div class="overview-card" style="text-align:center;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                                <h4 style="margin:0;font-size:1.1rem;color:var(--secondary-color);font-weight:500;">Tổng giao dịch</h4>
                                <i class="fas fa-exchange-alt" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                            </div>
                            <div style="font-size:2.5rem;font-weight:800;color:var(--primary-color);margin-bottom:0.5rem;"><?php echo getTotalTransactionCount($conn, $user_id); ?></div>
                            <div style="font-size:1rem;color:var(--secondary-color);">Tất cả thời gian</div>
                        </div>
                        
                        <div class="overview-card" style="text-align:center;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                                <h4 style="margin:0;font-size:1.1rem;color:var(--secondary-color);font-weight:500;">Chi tiêu TB/ngày</h4>
                                <i class="fas fa-calendar-day" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                            </div>
                            <div style="font-size:2.5rem;font-weight:800;color:var(--primary-color);margin-bottom:0.5rem;"><?php echo formatMoney($totalExpense / max(1, (time() - strtotime('-30 days')) / 86400)); ?></div>
                            <div style="font-size:1rem;color:var(--secondary-color);">30 ngày qua</div>
                        </div>
                        
                        <div class="overview-card" style="text-align:center;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                                <h4 style="margin:0;font-size:1.1rem;color:var(--secondary-color);font-weight:500;">Tỷ lệ tiết kiệm</h4>
                                <i class="fas fa-piggy-bank" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                            </div>
                            <div style="font-size:2.5rem;font-weight:800;color:var(--primary-color);margin-bottom:0.5rem;"><?php echo $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1) : 0; ?>%</div>
                            <div style="font-size:1rem;color:var(--secondary-color);">Của tổng thu nhập</div>
                        </div>
                        
                        <div class="overview-card" style="text-align:center;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                                <h4 style="margin:0;font-size:1.1rem;color:var(--secondary-color);font-weight:500;">Danh mục chi nhiều nhất</h4>
                                <i class="fas fa-chart-pie" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                            </div>
                            <div style="font-size:1.8rem;font-weight:800;color:var(--primary-color);margin-bottom:0.5rem;">
                                <?php 
                                $topCategory = '';
                                $maxAmount = 0;
                                foreach ($expense_categories as $cat) {
                                    $amount = getFilteredTotal($conn, $user_id, 'expense', null, null, $cat['name']);
                                    if ($amount > $maxAmount) {
                                        $maxAmount = $amount;
                                        $topCategory = $cat['name'];
                                    }
                                }
                                echo $topCategory ?: 'Chưa có';
                                ?>
                            </div>
                            <div style="font-size:1rem;color:var(--secondary-color);"><?php echo formatMoney($maxAmount); ?></div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="charts-container">
                        <!-- Income vs Expense Pie Chart -->
                        <div class="chart-card" style="text-align:center;">
                            <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:600;">Tỷ lệ Thu nhập/Chi tiêu</h3>
                            <canvas id="pieIncomeExpense" width="400" height="400"></canvas>
                            <div style="margin-top:1.5rem;display:flex;justify-content:space-around;font-size:1rem;">
                                <div style="text-align:center;">
                                    <div style="color:#22c55e;font-weight:700;font-size:1.2rem;"><?php echo formatMoney($totalIncome); ?></div>
                                    <div style="color:var(--secondary-color);">Thu nhập</div>
                                </div>
                                <div style="text-align:center;">
                                    <div style="color:#ef4444;font-weight:700;font-size:1.2rem;"><?php echo formatMoney($totalExpense); ?></div>
                                    <div style="color:var(--secondary-color);">Chi tiêu</div>
                                </div>
                            </div>
                        </div>

                        <!-- 6-Month Trend Line Chart -->
                        <div class="chart-card">
                            <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:600;">Xu hướng 6 tháng</h3>
                            <canvas id="lineIncomeExpense" width="500" height="350"></canvas>
                        </div>
                    </div>

                    <!-- Additional Charts -->
                    <div class="charts-container">
                        <!-- Expense by Category -->
                        <div class="chart-card">
                            <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:600;">Chi tiêu theo danh mục</h3>
                            <canvas id="barExpenseCategory" width="450" height="350"></canvas>
                        </div>

                        <!-- Top Transactions -->
                        <div class="chart-card">
                            <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:600;">Top 5 giao dịch lớn nhất</h3>
                            <div>
                                <?php 
                                $topTransactions = getFilteredTransactions($conn, $user_id, 'all', null, null, 'all');
                                usort($topTransactions, function($a, $b) { return $b['amount'] - $a['amount']; });
                                $topTransactions = array_slice($topTransactions, 0, 5);
                                foreach ($topTransactions as $index => $trans): 
                                ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid var(--border-color);">
                                    <div style="display:flex;align-items:center;gap:16px;">
                                        <div style="width:40px;height:40px;border-radius:50%;background:<?php echo $trans['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1rem;">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;color:var(--primary-color);font-size:1.1rem;"><?php echo htmlspecialchars($trans['description']); ?></div>
                                            <div style="font-size:0.9rem;color:var(--secondary-color);"><?php echo date('d/m/Y', strtotime($trans['date'])); ?></div>
                                        </div>
                                    </div>
                                    <div style="font-weight:700;color:<?php echo $trans['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;font-size:1.2rem;">
                                        <?php echo formatMoney($trans['amount']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget Tab -->
                <div id="budget" class="tab-pane">
                    <div style="margin-bottom:3rem;">
                        <h2 style="margin:0;color:var(--primary-color);font-size:2rem;font-weight:700;">Quản lý ngân sách</h2>
                        <p style="margin:8px 0 0;color:var(--secondary-color);font-size:1.1rem;">Thiết lập và theo dõi ngân sách cho từng danh mục</p>
                    </div>

                    <!-- Budget Overview -->
                    <div class="budget-overview">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:2rem;">
                            <div class="budget-stat">
                                <div style="font-size:2.5rem;font-weight:800;color:var(--primary-color);margin-bottom:0.5rem;">
                                    <?php echo formatMoney($monthlyIncome); ?>
                                </div>
                                <div style="color:var(--secondary-color);font-weight:600;font-size:1.1rem;">Thu nhập tháng này</div>
                            </div>
                            <div class="budget-stat">
                                <div style="font-size:2.5rem;font-weight:800;color:#ef4444;margin-bottom:0.5rem;">
                                    <?php echo formatMoney($monthlyExpense); ?>
                                </div>
                                <div style="color:var(--secondary-color);font-weight:600;font-size:1.1rem;">Chi tiêu tháng này</div>
                            </div>
                            <div class="budget-stat">
                                <div style="font-size:2.5rem;font-weight:800;color:<?php echo ($monthlyIncome - $monthlyExpense) >= 0 ? '#22c55e' : '#ef4444'; ?>;margin-bottom:0.5rem;">
                                    <?php echo formatMoney($monthlyIncome - $monthlyExpense); ?>
                                </div>
                                <div style="color:var(--secondary-color);font-weight:600;font-size:1.1rem;">Còn lại</div>
                            </div>
                            <div class="budget-stat">
                                <div style="font-size:2.5rem;font-weight:800;color:var(--accent-color);margin-bottom:0.5rem;">
                                    <?php echo $monthlyIncome > 0 ? round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1) : 0; ?>%
                                </div>
                                <div style="color:var(--secondary-color);font-weight:600;font-size:1.1rem;">Tỷ lệ tiết kiệm</div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Categories -->
                    <div>
                        <h3 style="margin:0 0 2rem;color:var(--primary-color);font-size:1.5rem;font-weight:600;">Ngân sách theo danh mục</h3>
                        
                        <?php foreach ($expense_categories as $category): ?>
                            <?php 
                            $categoryExpense = getFilteredTotal($conn, $user_id, 'expense', $currentMonth, $currentYear, $category['name']);
                            $budgetLimit = $monthlyIncome * 0.15; // 15% thu nhập cho mỗi danh mục
                            $budgetUsed = $budgetLimit > 0 ? ($categoryExpense / $budgetLimit) * 100 : 0;
                            ?>
                            <div class="budget-item">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                                    <div>
                                        <h4 style="margin:0;color:var(--primary-color);font-weight:600;font-size:1.3rem;"><?php echo htmlspecialchars($category['name']); ?></h4>
                                        <p style="margin:4px 0 0;color:var(--secondary-color);font-size:1rem;">
                                            <?php echo formatMoney($categoryExpense); ?> / <?php echo formatMoney($budgetLimit); ?>
                                        </p>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:700;font-size:1.5rem;color:<?php echo $budgetUsed > 100 ? '#ef4444' : ($budgetUsed > 80 ? '#f59e0b' : '#22c55e'); ?>;">
                                            <?php echo round($budgetUsed, 1); ?>%
                                        </div>
                                        <div style="font-size:0.9rem;color:var(--secondary-color);">đã sử dụng</div>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="background:<?php echo $budgetUsed > 100 ? '#ef4444' : ($budgetUsed > 80 ? '#f59e0b' : '#22c55e'); ?>;width:<?php echo min($budgetUsed, 100); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- AI Assistant Tab -->
                <div id="ai" class="tab-pane">
                    <div style="margin-bottom:3rem;">
                        <h2 style="margin:0;color:var(--primary-color);font-size:2rem;font-weight:700;">Trợ lý AI tài chính</h2>
                        <p style="margin:8px 0 0;color:var(--secondary-color);font-size:1.1rem;">Nhận tư vấn và phân tích thông minh về tài chính cá nhân</p>
                    </div>

                    <!-- AI Features -->
                    <div class="ai-features">
                        <div class="ai-feature-card" style="background:var(--info-gradient);">
                            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                                <i class="fas fa-camera" style="font-size:2.5rem;"></i>
                                <h3 style="margin:0;font-size:1.4rem;font-weight:600;">Quét hóa đơn</h3>
                            </div>
                            <p style="margin:0;opacity:0.9;font-size:1rem;">Chụp ảnh hóa đơn để tự động nhập giao dịch với AI</p>
                        </div>
                        
                        <div class="ai-feature-card" style="background:var(--success-gradient);">
                            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                                <i class="fas fa-brain" style="font-size:2.5rem;"></i>
                                <h3 style="margin:0;font-size:1.4rem;font-weight:600;">Tư vấn thông minh</h3>
                            </div>
                            <p style="margin:0;opacity:0.9;font-size:1rem;">Nhận lời khuyên tài chính dựa trên dữ liệu của bạn</p>
                        </div>
                        
                        <div class="ai-feature-card" style="background:var(--purple-gradient);">
                            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                                <i class="fas fa-chart-line" style="font-size:2.5rem;"></i>
                                <h3 style="margin:0;font-size:1.4rem;font-weight:600;">Dự đoán xu hướng</h3>
                            </div>
                            <p style="margin:0;opacity:0.9;font-size:1rem;">Phân tích và dự báo tình hình tài chính tương lai</p>
                        </div>
                    </div>

                    <!-- Chat Interface -->
                    <div class="chat-container">
                        <div class="chat-header">
                            <div style="display:flex;align-items:center;gap:1rem;">
                                <div style="width:48px;height:48px;border-radius:50%;background:var(--accent-color);display:flex;align-items:center;justify-content:center;color:white;">
                                    <i class="fas fa-robot" style="font-size:1.2rem;"></i>
                                </div>
                                <div>
                                    <h4 style="margin:0;color:var(--primary-color);font-weight:600;font-size:1.2rem;">Trợ lý AI tài chính</h4>
                                    <p style="margin:0;color:var(--secondary-color);font-size:0.9rem;">Sẵn sàng hỗ trợ bạn 24/7</p>
                                </div>
                            </div>
                        </div>

                        <div id="chatMessages" class="chat-messages">
                            <div class="message ai-message">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--accent-color);display:flex;align-items:center;justify-content:center;color:white;">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="message-content">
                                    <p style="margin:0 0 1rem;color:var(--primary-color);font-weight:600;">Xin chào! Tôi là trợ lý AI tài chính của bạn. 🤖</p>
                                    <p style="margin:0;color:var(--primary-color);">Tôi có thể giúp bạn:</p>
                                    <ul style="margin:0.5rem 0 0;padding-left:1.5rem;color:var(--secondary-color);">
                                        <li>📸 Phân tích hóa đơn từ ảnh</li>
                                        <li>💡 Tư vấn về tài chính cá nhân</li>
                                        <li>❓ Trả lời câu hỏi về chi tiêu</li>
                                        <li>💰 Đưa ra lời khuyên tiết kiệm</li>
                                        <li>📊 Phân tích xu hướng tài chính</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="chat-input">
                            <div style="display:flex;gap:1rem;align-items:end;">
                                <div style="flex:1;">
                                    <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                                        <select id="roleSelect" style="background:white;border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;font-size:0.9rem;color:var(--primary-color);">
                                            <option value="assistant">Trợ lý tài chính</option>
                                            <option value="advisor">Cố vấn đầu tư</option>
                                            <option value="analyst">Chuyên gia phân tích</option>
                                        </select>
                                        <input type="file" id="imageInput" accept="image/*" style="display:none;">
                                        <button onclick="document.getElementById('imageInput').click()" style="background:var(--accent-color);color:white;border:none;border-radius:8px;padding:6px 12px;font-size:0.9rem;cursor:pointer;" title="Tải ảnh hóa đơn">
                                            <i class="fas fa-camera"></i> Tải ảnh
                                        </button>
                                    </div>
                                    <textarea id="chatInput" placeholder="Nhập câu hỏi hoặc tải ảnh hóa đơn để phân tích..." style="width:100%;border:1px solid var(--border-color);border-radius:12px;padding:1rem;resize:none;background:white;color:var(--primary-color);font-family:inherit;font-size:1rem;" rows="3"></textarea>
                                </div>
                                <button id="sendChatBtn" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:1rem 1.5rem;cursor:pointer;transition:all 0.2s ease;font-size:1rem;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/main.js"></script>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('vi-VN');
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

setInterval(updateTime, 1000);
updateTime();

// Tab functionality
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        // Remove active class from all tabs and buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        
        // Add active class to clicked button and corresponding tab
        this.classList.add('active');
        
        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.classList.add('active');
        }
    });
});

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Mini trend chart
    const miniCtx = document.getElementById('miniTrendChart');
    if (miniCtx) {
        new Chart(miniCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Thu nhập',
                    data: <?php echo json_encode(array_column($monthlyData, 'income')); ?>,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }, {
                    label: 'Chi tiêu',
                    data: <?php echo json_encode(array_column($monthlyData, 'expense')); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
    }

    // Income vs Expense Pie Chart
    const pieCtx = document.getElementById('pieIncomeExpense');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Thu nhập', 'Chi tiêu'],
                datasets: [{
                    data: [<?php echo $totalIncome; ?>, <?php echo $totalExpense; ?>],
                    backgroundColor: ['#22c55e', '#ef4444'],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
    }

    // 6-Month Trend Line Chart
    const lineCtx = document.getElementById('lineIncomeExpense');
    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Thu nhập',
                    data: <?php echo json_encode(array_column($monthlyData, 'income')); ?>,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.1)',
                    tension: 0.4,
                    fill: false,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Chi tiêu',
                    data: <?php echo json_encode(array_column($monthlyData, 'expense')); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    tension: 0.4,
                    fill: false,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
    }

    // Expense by Category Bar Chart
    const barCtx = document.getElementById('barExpenseCategory');
    if (barCtx) {
        const categoryNames = <?php echo json_encode(array_column($expense_categories, 'name')); ?>;
        const categoryAmounts = [
            <?php 
            foreach ($expense_categories as $cat) {
                echo getFilteredTotal($conn, $user_id, 'expense', null, null, $cat['name']) . ',';
            }
            ?>
        ];

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: categoryNames,
                datasets: [{
                    label: 'Chi tiêu',
                    data: categoryAmounts,
                    backgroundColor: '#ef4444',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});

// Add transaction button functionality
document.getElementById('addTransactionBtn').addEventListener('click', function() {
    // This would open the add transaction modal
    console.log('Add transaction clicked');
});

document.getElementById('emptyAddTransactionBtn')?.addEventListener('click', function() {
    document.getElementById('addTransactionBtn').click();
});
</script>

<?php include 'includes/footer.php'; ?> 