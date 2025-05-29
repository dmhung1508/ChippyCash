<?php
// Turn off error reporting để tránh HTML output trong JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Lấy danh sách users với thống kê
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            
            $sql = "SELECT u.*, 
                           COUNT(t.id) as transaction_count,
                           SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as total_income,
                           SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as total_expense,
                           SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as balance
                    FROM users u 
                    LEFT JOIN transactions t ON u.id = t.user_id";
            
            $params = [];
            $whereConditions = [];
            
            if ($search) {
                $whereConditions[] = "(u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($role) {
                $whereConditions[] = "u.role = ?";
                $params[] = $role;
            }
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ẩn password
            foreach ($users as &$user) {
                unset($user['password']);
                $user['transaction_count'] = (int)$user['transaction_count'];
                $user['total_income'] = (float)($user['total_income'] ?? 0);
                $user['total_expense'] = (float)($user['total_expense'] ?? 0);
                $user['balance'] = (float)($user['balance'] ?? 0);
            }
            
            // Count total users
            $countSql = "SELECT COUNT(*) as total FROM users u";
            $countParams = [];
            
            if (!empty($whereConditions)) {
                $countSql .= " WHERE " . implode(" AND ", $whereConditions);
                // Remove LIMIT and OFFSET params, keep only search/filter params
                $countParams = array_slice($params, 0, count($params) - 2);
            }
            
            $stmt = $conn->prepare($countSql);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true, 
                'users' => $users,
                'total' => (int)$total,
                'limit' => (int)$limit,
                'offset' => (int)$offset
            ]);
            break;
            
        case 'PUT':
            // Cập nhật user (promote to admin, demote, etc.)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            $userId = $input['user_id'];
            $action = $input['action'] ?? '';
            
            // Không cho phép tự sửa role của chính mình
            if ($userId == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot modify your own role']);
                break;
            }
            
            // Get current user info
            $stmt = $conn->prepare("SELECT name, username, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            
            switch ($action) {
                case 'promote_to_admin':
                    if ($targetUser['role'] === 'admin') {
                        http_response_code(400);
                        echo json_encode(['error' => 'User is already admin']);
                        break 2;
                    }
                    
                    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $actionDetails = "Promoted user '{$targetUser['username']}' to admin";
                    $message = 'User promoted to admin successfully';
                    break;
                    
                case 'demote_to_user':
                    if ($targetUser['role'] === 'user') {
                        http_response_code(400);
                        echo json_encode(['error' => 'User is already regular user']);
                        break 2;
                    }
                    
                    $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $actionDetails = "Demoted admin '{$targetUser['username']}' to user";
                    $message = 'Admin demoted to user successfully';
                    break;
                    
                case 'toggle_status':
                    // Toggle active/inactive status (nếu có field status)
                    $newStatus = $input['status'] ?? 1;
                    
                    // Nếu chưa có cột status, tạm thời skip
                    $message = 'Status toggle not implemented yet';
                    $actionDetails = "Attempted to toggle status for user '{$targetUser['username']}'";
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
                    break 2;
            }
            
            // Log admin activity
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $action,
                'user',
                $userId,
                $actionDetails,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode(['success' => true, 'message' => $message]);
            break;
            
        case 'DELETE':
            // Xóa user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            $userId = $input['user_id'];
            
            // Không cho phép xóa chính mình
            if ($userId == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete your own account']);
                break;
            }
            
            // Get user info before deleting
            $stmt = $conn->prepare("SELECT name, username, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            
            // Không cho phép xóa admin khác (tùy chọn bảo mật)
            if ($targetUser['role'] === 'admin') {
                $confirmDelete = $input['confirm_admin_delete'] ?? false;
                if (!$confirmDelete) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Cannot delete admin user. Set confirm_admin_delete to true if you really want to delete.']);
                    break;
                }
            }
            
            $conn->beginTransaction();
            
            try {
                // Xóa các dữ liệu liên quan (transactions, notifications, etc.)
                $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                // Xóa user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Log admin activity
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    'delete_user',
                    'user',
                    $userId,
                    "Deleted user '{$targetUser['username']}' ({$targetUser['role']})",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 