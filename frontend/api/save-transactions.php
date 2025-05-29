<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
   http_response_code(401);
   echo json_encode(['success' => false, 'message' => 'Unauthorized']);
   exit;
}

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
   exit;
}

// Lấy dữ liệu yêu cầu
$input = file_get_contents('php://input');
if (empty($input)) {
   http_response_code(400);
   echo json_encode(['success' => false, 'message' => 'No input data provided']);
   exit;
}

try {
  // Phân tích JSON
  $data = json_decode($input, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON: ' . json_last_error_msg());
  }
  
  $transactions = $data['transactions'] ?? [];
  
  if (empty($transactions)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'No transactions provided']);
      exit;
  }
  
  $user_id = $_SESSION['user_id'];
  $today = date('Y-m-d');
  $success_count = 0;
  $error_count = 0;
  
  // Kiểm tra dữ liệu đầu vào
  if (!is_array($transactions)) {
      throw new Exception('Transactions must be an array');
  }
  
  // Bắt đầu giao dịch
  $conn->beginTransaction();
  
  foreach ($transactions as $index => $transaction) {
      // Debug logging
      error_log("Processing transaction " . ($index + 1) . ": " . json_encode($transaction));
      
      // Kiểm tra dữ liệu giao dịch
      if (!isset($transaction['type']) || !isset($transaction['name']) || !isset($transaction['amount'])) {
          $error_count++;
          continue;
      }
      
      $type = $transaction['type'];
      $description = $transaction['name'];
      $amount = floatval($transaction['amount']);
      $category = $transaction['category'] ?? null;
      
      // Debug category
      error_log("Category from AI: " . ($category ?? 'null'));
      
      // Xác thực dữ liệu
      if (empty($type) || empty($description) || $amount <= 0) {
          $error_count++;
          continue;
      }
      
      // Chuyển đổi 'outcome' thành 'expense' để nhất quán
      if ($type === 'outcome') {
          $type = 'expense';
      }
      
      // Sử dụng category từ AI, nếu không có thì tìm tự động
      if (empty($category)) {
          $categories = getUserCategories($conn, $user_id, $type);
          
          // Cố gắng tìm danh mục phù hợp theo tên
          foreach ($categories as $cat) {
              if (stripos($description, $cat['name']) !== false) {
                  $category = $cat['name'];
                  break;
              }
          }
          
          // Nếu không tìm thấy danh mục, sử dụng danh mục đầu tiên hoặc "Chung"
          if (!$category && !empty($categories)) {
              $category = $categories[0]['name'];
          } else if (!$category) {
              $category = 'Chung';
          }
      }
      
      // Debug final category before save
      error_log("Final category to save: " . ($category ?? 'null'));
      
      // Thêm giao dịch
      $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, description, type, category, date, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())");
      $result = $stmt->execute([$user_id, $amount, $description, $type, $category, $today]);
      
      if ($result) {
          $success_count++;
      } else {
          $error_count++;
      }
  }
  
  // Commit giao dịch nếu ít nhất một giao dịch thành công
  if ($success_count > 0) {
      $conn->commit();
      echo json_encode([
          'success' => true, 
          'message' => "Đã lưu $success_count giao dịch thành công" . ($error_count > 0 ? ", $error_count giao dịch thất bại" : "")
      ]);
  } else {
      $conn->rollBack();
      echo json_encode(['success' => false, 'message' => 'Không thể lưu giao dịch']);
  }
  
} catch (Exception $e) {
  // Rollback khi có lỗi
  if ($conn->inTransaction()) {
      $conn->rollBack();
  }
  
  http_response_code(500);
  echo json_encode([
      'success' => false, 
      'message' => 'Lỗi hệ thống: ' . $e->getMessage()
  ]);
}
?>
