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

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_month = $_GET['month'] ?? date('m');
$filter_year = $_GET['year'] ?? date('Y');
$filter_category = $_GET['category'] ?? 'all';

// Get filtered transactions
$transactions = getFilteredTransactions($conn, $user_id, $filter_type, $filter_month, $filter_year, $filter_category);

// Calculate totals
$filteredIncome = getFilteredTotal($conn, $user_id, 'income', $filter_month, $filter_year, $filter_category);
$filteredExpense = getFilteredTotal($conn, $user_id, 'expense', $filter_month, $filter_year, $filter_category);
$filteredBalance = $filteredIncome - $filteredExpense;

// Generate filename with CSV extension
$filename = 'bao_cao_giao_dich_' . $filter_year . '_' . $filter_month . '_' . date('YmdHis') . '.csv';

// Set headers for CSV download with UTF-8 BOM
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Create file handle
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write report header
fputcsv($output, ['BÁO CÁO GIAO DỊCH']);
fputcsv($output, ['']); // Empty row

// Write filter information
fputcsv($output, ['Thông tin bộ lọc:']);
fputcsv($output, ['Loại giao dịch', $filter_type === 'all' ? 'Tất cả' : ($filter_type === 'income' ? 'Thu nhập' : 'Chi tiêu')]);
fputcsv($output, ['Tháng/Năm', 'Tháng ' . $filter_month . '/' . $filter_year]);
fputcsv($output, ['Danh mục', $filter_category === 'all' ? 'Tất cả danh mục' : $filter_category]);
fputcsv($output, ['Ngày xuất', date('d/m/Y H:i:s')]);
fputcsv($output, ['']); // Empty row

// Write summary
fputcsv($output, ['TỔNG KẾT']);
fputcsv($output, ['Tổng thu nhập', number_format($filteredIncome, 0, ',', '.') . ' VND']);
fputcsv($output, ['Tổng chi tiêu', number_format($filteredExpense, 0, ',', '.') . ' VND']);
fputcsv($output, ['Số dư', number_format($filteredBalance, 0, ',', '.') . ' VND']);
fputcsv($output, ['Số giao dịch', count($transactions)]);
fputcsv($output, ['']); // Empty row

// Write table headers
fputcsv($output, ['CHI TIẾT GIAO DỊCH']);
fputcsv($output, ['STT', 'Ngày', 'Mô tả', 'Danh mục', 'Loại', 'Số tiền (VND)']);

// Write transaction data
$stt = 1;
foreach ($transactions as $transaction) {
    $type_text = $transaction['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu';
    $amount_formatted = number_format($transaction['amount'], 0, ',', '.');
    
    fputcsv($output, [
        $stt,
        date('d/m/Y', strtotime($transaction['date'])),
        $transaction['description'],
        $transaction['category'] ?? 'Chung',
        $type_text,
        $amount_formatted
    ]);
    
    $stt++;
}

// Add summary footer
fputcsv($output, ['']); // Empty row
fputcsv($output, ['']); // Empty row
fputcsv($output, ['Xuất bởi', $_SESSION['user_name'] ?? 'Người dùng']);
fputcsv($output, ['Hệ thống', 'Quản lý tài chính']);

fclose($output);
exit;
?> 