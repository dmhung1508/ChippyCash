<?php
// ===== USER MANAGEMENT =====
function getUserById($conn, $user_id) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  return $stmt->fetch();
}

function getUserByUsername($conn, $username) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$username]);
  return $stmt->fetch();
}

function getUserByEmail($conn, $email) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  return $stmt->fetch();
}

function isUsernameExists($conn, $username, $exclude_id = null) {
  $sql = "SELECT id FROM users WHERE username = ?";
  $params = [$username];
  
  if ($exclude_id) {
    $sql .= " AND id != ?";
    $params[] = $exclude_id;
  }
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->rowCount() > 0;
}

function isEmailExists($conn, $email, $exclude_id = null) {
  $sql = "SELECT id FROM users WHERE email = ?";
  $params = [$email];
  
  if ($exclude_id) {
    $sql .= " AND id != ?";
    $params[] = $exclude_id;
  }
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->rowCount() > 0;
}

function registerUser($conn, $name, $email, $password) {
  // Start transaction
  $conn->beginTransaction();
  
  try {
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
    $userInserted = $stmt->execute([$name, $email, $password]);
    
    if (!$userInserted) {
      throw new Exception("Failed to create user account");
    }
    
    // Get the new user ID
    $userId = $conn->lastInsertId();
    
    // Add default income categories
    $incomeCategories = [
      ['Lương', 'Thu nhập từ lương hàng tháng hoặc công việc chính', 'income'],
      ['Tiền thưởng', 'Tiền thưởng từ công việc hoặc dự án', 'income'],
      ['Tiền lãi', 'Tiền lãi từ các khoản đầu tư', 'income'],
      ['Tiền bán hàng', 'Tiền từ việc bán hàng hóa hoặc dịch vụ', 'income']
    ];
    
    // Add default expense categories
    $expenseCategories = [
      ['Tiền ăn uống', 'Tiền ăn uống hàng ngày', 'expense'],
      ['Tiền đi lại', 'Tiền đi lại hàng ngày', 'expense'],
      ['Tiền học tập', 'Tiền học tập hàng tháng', 'expense'],
      ['Tiền giải trí', 'Tiền giải trí hàng tháng', 'expense'],
      ['Tiền xăng', 'Tiền xăng hàng tháng', 'expense'],
      ['Tiền nước', 'Tiền nước hàng tháng', 'expense'],
      ['Tiền điện', 'Tiền điện hàng tháng', 'expense'],
      ['Tiền mua sắm', 'Tiền mua sắm hàng tháng', 'expense'],
      ['Tiền khác', 'Tiền khác hàng tháng', 'expense']
    ];
    
    // Prepare statement for inserting categories
    $categoryStmt = $conn->prepare("INSERT INTO categories (user_id, name, description, type, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
    
    // Insert income categories
    foreach ($incomeCategories as $category) {
      $categoryInserted = $categoryStmt->execute([$userId, $category[0], $category[1], $category[2]]);
      if (!$categoryInserted) {
        throw new Exception("Failed to create default income categories");
      }
    }
    
    // Insert expense categories
    foreach ($expenseCategories as $category) {
      $categoryInserted = $categoryStmt->execute([$userId, $category[0], $category[1], $category[2]]);
      if (!$categoryInserted) {
        throw new Exception("Failed to create default expense categories");
      }
    }
    
    // Commit transaction
    $conn->commit();
    return true;
    
  } catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Registration error: " . $e->getMessage());
    return false;
  }
}

function updateUserProfile($conn, $user_id, $name, $username, $email) {
  $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, updated_at = NOW() WHERE id = ?");
  return $stmt->execute([$name, $username, $email, $user_id]);
}

function updateUserPassword($conn, $user_id, $password) {
  $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
  return $stmt->execute([$password, $user_id]);
}

function updateLastLogin($conn, $user_id) {
  $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
  return $stmt->execute([$user_id]);
}

// ===== TRANSACTION MANAGEMENT =====
function getTotalIncome($conn, $user_id, $month = null, $year = null) {
  return getFilteredTotal($conn, $user_id, 'income', $month, $year);
}

function getTotalExpense($conn, $user_id, $month = null, $year = null) {
  return getFilteredTotal($conn, $user_id, 'expense', $month, $year);
}

function getRecentTransactions($conn, $user_id, $limit = 5) {
  $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT ?");
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->bindParam(2, $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function addTransaction($conn, $user_id, $amount, $description, $type, $category, $date) {
  $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, description, type, category, date, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())");
  return $stmt->execute([$user_id, $amount, $description, $type, $category, $date]);
}

function getTransactionById($conn, $transaction_id, $user_id) {
  $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
  $stmt->execute([$transaction_id, $user_id]);
  return $stmt->fetch();
}

function updateTransaction($conn, $transaction_id, $user_id, $amount, $description, $type, $category, $date) {
  $stmt = $conn->prepare("UPDATE transactions 
                         SET amount = ?, description = ?, type = ?, category = ?, date = ?, updated_at = NOW() 
                         WHERE id = ? AND user_id = ?");
  return $stmt->execute([$amount, $description, $type, $category, $date, $transaction_id, $user_id]);
}

function deleteTransaction($conn, $transaction_id, $user_id) {
  $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
  return $stmt->execute([$transaction_id, $user_id]);
}

function getFilteredTransactions($conn, $user_id, $type = 'all', $month = null, $year = null, $category = 'all') {
  $sql = "SELECT * FROM transactions WHERE user_id = ?";
  $params = [$user_id];
  
  if ($type !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $type;
  }
  
  if ($month && $year) {
    $sql .= " AND MONTH(date) = ? AND YEAR(date) = ?";
    $params[] = $month;
    $params[] = $year;
  }
  
  if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
  }
  
  $sql .= " ORDER BY date DESC, id DESC";
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

function getFilteredTotal($conn, $user_id, $type, $month = null, $year = null, $category = 'all') {
  $sql = "SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = ?";
  $params = [$user_id, $type];
  
  if ($month && $year) {
    $sql .= " AND MONTH(date) = ? AND YEAR(date) = ?";
    $params[] = $month;
    $params[] = $year;
  }
  
  if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
  }
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchColumn() ?: 0;
}

function getTotalTransactionCount($conn, $user_id) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
  $stmt->execute([$user_id]);
  return $stmt->fetchColumn() ?: 0;
}

// ===== CATEGORY MANAGEMENT =====
function getUserCategories($conn, $user_id, $type = null) {
  $sql = "SELECT * FROM categories WHERE user_id = ?";
  $params = [$user_id];
  
  if ($type) {
    $sql .= " AND type = ?";
    $params[] = $type;
  }
  
  $sql .= " ORDER BY name ASC";
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

function getCategoryById($conn, $category_id, $user_id) {
  $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
  $stmt->execute([$category_id, $user_id]);
  return $stmt->fetch();
}

function addCategory($conn, $user_id, $name, $description, $type) {
  $stmt = $conn->prepare("INSERT INTO categories (user_id, name, description, type, created_at) 
                         VALUES (?, ?, ?, ?, NOW())");
  return $stmt->execute([$user_id, $name, $description, $type]);
}

function updateCategory($conn, $category_id, $user_id, $name, $description, $type) {
  // Get current category name
  $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
  $stmt->execute([$category_id, $user_id]);
  $current = $stmt->fetch();
  
  // Update category
  $stmt = $conn->prepare("UPDATE categories 
                         SET name = ?, description = ?, type = ?, updated_at = NOW() 
                         WHERE id = ? AND user_id = ?");
  $result = $stmt->execute([$name, $description, $type, $category_id, $user_id]);
  
  // If name changed, update transactions using this category
  if ($result && $current && $current['name'] !== $name) {
    $stmt = $conn->prepare("UPDATE transactions SET category = ? WHERE user_id = ? AND category = ?");
    $stmt->execute([$name, $user_id, $current['name']]);
  }
  
  return $result;
}

function deleteCategory($conn, $category_id, $user_id) {
  // Get category name
  $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
  $stmt->execute([$category_id, $user_id]);
  $category = $stmt->fetch();
  
  if ($category) {
    // Set category to NULL in transactions
    $stmt = $conn->prepare("UPDATE transactions SET category = NULL WHERE user_id = ? AND category = ?");
    $stmt->execute([$user_id, $category['name']]);
    
    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    return $stmt->execute([$category_id, $user_id]);
  }
  
  return false;
}

function isCategoryNameExists($conn, $user_id, $name, $type, $exclude_id = null) {
  $sql = "SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?";
  $params = [$user_id, $name, $type];
  
  if ($exclude_id) {
    $sql .= " AND id != ?";
    $params[] = $exclude_id;
  }
  
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt->rowCount() > 0;
}

function getCategoryUsageCount($conn, $user_id, $category_name) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND category = ?");
  $stmt->execute([$user_id, $category_name]);
  return $stmt->fetchColumn();
}

// ===== FINANCIAL ANALYSIS =====
function getIncomeExpenseRatio($conn, $user_id) {
  $totalIncome = getTotalIncome($conn, $user_id);
  $totalExpense = getTotalExpense($conn, $user_id);
  
  if ($totalIncome > 0) {
    return round(($totalExpense / $totalIncome) * 100, 1);
  }
  
  return 0;
}

function getMonthlyExpenseTrend($conn, $user_id, $months = 3) {
  $sql = "SELECT MONTH(date) as month, YEAR(date) as year, SUM(amount) as total 
          FROM transactions 
          WHERE user_id = ? AND type = 'expense' 
          GROUP BY YEAR(date), MONTH(date) 
          ORDER BY YEAR(date) DESC, MONTH(date) DESC 
          LIMIT ?";
  
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
  $stmt->bindParam(2, $months, PDO::PARAM_INT);
  $stmt->execute();
  
  return $stmt->fetchAll();
}

// ===== CHATBOT =====
function saveChatMessage($conn, $user_id, $message, $is_bot = false) {
  $stmt = $conn->prepare("INSERT INTO chat_history (user_id, message, is_bot, created_at) VALUES (?, ?, ?, NOW())");
  return $stmt->execute([$user_id, $message, $is_bot ? 1 : 0]);
}

// ===== UTILITY FUNCTIONS =====
function redirectTo($location) {
  header("Location: $location");
  exit;
}

function setFlashMessage($type, $message) {
  $_SESSION[$type . '_message'] = $message;
}

function getFlashMessage($type) {
  $message = $_SESSION[$type . '_message'] ?? null;
  unset($_SESSION[$type . '_message']);
  return $message;
}

function displayFlashMessages() {
  $types = ['success', 'error', 'warning', 'info'];
  $output = '';
  
  foreach ($types as $type) {
    $message = getFlashMessage($type);
    if ($message) {
      $icon = $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle');
      $output .= "<div class='alert alert-{$type}'><i class='fas fa-{$icon}'></i>{$message}</div>";
    }
  }
  
  return $output;
}

function formatMoney($amount) {
  return number_format($amount, 0, ',', '.') . '₫';
}

function validateRequiredFields($fields) {
  foreach ($fields as $value) {
    if (empty($value)) {
      return false;
    }
  }
  return true;
}
?>
