<?php
// Turn off error reporting để tránh HTML output trong JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

require_once '../includes/db.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stats = [];
    
    // Thống kê cơ bản
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
    $stats['total_admins'] = $stmt->fetch()['total_admins'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_transactions FROM transactions");
    $stats['total_transactions'] = $stmt->fetch()['total_transactions'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_categories FROM categories");
    $stats['total_categories'] = $stmt->fetch()['total_categories'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_bank_accounts FROM bank_accounts");
    $stats['total_bank_accounts'] = $stmt->fetch()['total_bank_accounts'];
    
    // Thống kê tài chính
    $stmt = $conn->query("SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net_balance
        FROM transactions");
    $financial = $stmt->fetch();
    $stats['total_income'] = $financial['total_income'] ?? 0;
    $stats['total_expense'] = $financial['total_expense'] ?? 0;
    $stats['net_balance'] = $financial['net_balance'] ?? 0;
    
    // Thống kê trong tháng này
    $stmt = $conn->query("SELECT 
        COUNT(*) as monthly_transactions,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as monthly_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as monthly_expense
        FROM transactions 
        WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
    $monthly = $stmt->fetch();
    $stats['monthly_transactions'] = $monthly['monthly_transactions'] ?? 0;
    $stats['monthly_income'] = $monthly['monthly_income'] ?? 0;
    $stats['monthly_expense'] = $monthly['monthly_expense'] ?? 0;
    
    // Thống kê người dùng mới trong 30 ngày
    $stmt = $conn->query("SELECT COUNT(*) as new_users_30d FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_users_30d'] = $stmt->fetch()['new_users_30d'] ?? 0;
    
    // Thống kê hôm nay
    $stmt = $conn->query("SELECT COUNT(*) as new_users_today FROM users WHERE DATE(created_at) = CURRENT_DATE()");
    $stats['new_users_today'] = $stmt->fetch()['new_users_today'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as transactions_today FROM transactions WHERE DATE(date) = CURRENT_DATE()");
    $stats['transactions_today'] = $stmt->fetch()['transactions_today'] ?? 0;
    
    // Thống kê giao dịch trong 7 ngày qua
    $stmt = $conn->query("SELECT COUNT(*) as transactions_7d FROM transactions WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)");
    $stats['transactions_7d'] = $stmt->fetch()['transactions_7d'] ?? 0;
    
    // Thống kê chi tiết giao dịch
    $stmt = $conn->query("SELECT 
        COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
        COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count
        FROM transactions");
    $transactionStats = $stmt->fetch();
    $stats['transaction_stats'] = [
        'total_income' => $financial['total_income'] ?? 0,
        'total_expense' => $financial['total_expense'] ?? 0,
        'income_count' => $transactionStats['income_count'] ?? 0,
        'expense_count' => $transactionStats['expense_count'] ?? 0
    ];
    
    // Top 5 người dùng có nhiều giao dịch nhất
    $stmt = $conn->query("SELECT u.name, u.username, COUNT(t.id) as transaction_count,
        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as balance
        FROM users u 
        LEFT JOIN transactions t ON u.id = t.user_id 
        WHERE u.role = 'user'
        GROUP BY u.id 
        ORDER BY transaction_count DESC 
        LIMIT 5");
    $stats['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 5 danh mục được sử dụng nhiều nhất
    $stmt = $conn->query("SELECT t.category, COUNT(t.id) as usage_count 
        FROM transactions t 
        WHERE t.category IS NOT NULL AND t.category != ''
        GROUP BY t.category 
        ORDER BY usage_count DESC 
        LIMIT 5");
    $stats['top_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thống kê theo ngày trong 7 ngày qua
    $stmt = $conn->query("SELECT 
        DATE(date) as day,
        COUNT(*) as count,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM transactions 
        WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        GROUP BY DATE(date)
        ORDER BY day");
    $stats['daily_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thống kê admin logs gần đây
    $stmt = $conn->query("SELECT al.*, u.name as admin_name 
        FROM admin_logs al 
        LEFT JOIN users u ON al.admin_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10");
    $stats['recent_admin_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // System health checks
    $stats['system_status'] = [
        'database_connected' => true,
        'maintenance_mode' => false,
        'disk_space' => disk_free_space('.') / (1024 * 1024 * 1024), // GB
        'php_version' => phpversion(),
        'server_time' => date('Y-m-d H:i:s'),
        'uptime' => function_exists('sys_getloadavg') ? (sys_getloadavg()[0] ?? 'N/A') : 'N/A (Windows)'
    ];
    
    // Check if tables exist
    $required_tables = ['users', 'transactions', 'categories', 'bank_accounts', 'admin_logs', 'system_settings', 'notifications'];
    $stats['tables_status'] = [];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $conn->query("SELECT 1 FROM $table LIMIT 1");
            $stats['tables_status'][$table] = true;
        } catch (Exception $e) {
            $stats['tables_status'][$table] = false;
        }
    }
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 