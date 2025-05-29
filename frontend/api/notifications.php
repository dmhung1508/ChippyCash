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
            // Lấy danh sách notifications
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            $type = $_GET['type'] ?? null;
            
            $sql = "SELECT n.*, u.name as user_name, u.username 
                    FROM notifications n 
                    LEFT JOIN users u ON n.user_id = u.id";
            
            $params = [];
            
            if ($type) {
                $sql .= " WHERE n.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count total notifications
            $countSql = "SELECT COUNT(*) as total FROM notifications n";
            if ($type) {
                $countSql .= " WHERE n.type = ?";
                $stmt = $conn->prepare($countSql);
                $stmt->execute([$type]);
            } else {
                $stmt = $conn->query($countSql);
            }
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true, 
                'notifications' => $notifications,
                'total' => (int)$total,
                'limit' => (int)$limit,
                'offset' => (int)$offset
            ]);
            break;
            
        case 'POST':
            // Gửi notification mới
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['title']) || !isset($input['message'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and message are required']);
                break;
            }
            
            $title = $input['title'];
            $message = $input['message'];
            $type = $input['type'] ?? 'info';
            $userId = $input['user_id'] ?? null;
            $isGlobal = isset($input['is_global']) ? (bool)$input['is_global'] : false;
            
            // Validate type
            $validTypes = ['info', 'success', 'warning', 'error'];
            if (!in_array($type, $validTypes)) {
                $type = 'info';
            }
            
            if ($isGlobal) {
                // Global notification - send to all users
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, is_global) VALUES (NULL, ?, ?, ?, 1)");
                $stmt->execute([$title, $message, $type]);
                $targetText = 'all users (global)';
            } else if ($userId) {
                // Specific user notification
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, is_global) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$userId, $title, $message, $type]);
                $targetText = "user ID {$userId}";
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Either user_id or is_global must be specified']);
                break;
            }
            
            // Log admin activity
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'send_notification',
                "Sent {$type} notification to {$targetText}: {$title}",
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
            break;
            
        case 'PUT':
            // Đánh dấu notification đã đọc
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['notification_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                break;
            }
            
            $notificationId = $input['notification_id'];
            $isRead = isset($input['is_read']) ? (bool)$input['is_read'] : true;
            
            $stmt = $conn->prepare("UPDATE notifications SET is_read = ?, read_at = ? WHERE id = ?");
            $stmt->execute([
                $isRead ? 1 : 0,
                $isRead ? date('Y-m-d H:i:s') : null,
                $notificationId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Notification updated successfully']);
            break;
            
        case 'DELETE':
            // Xóa notification
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['notification_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                break;
            }
            
            $notificationId = $input['notification_id'];
            
            // Get notification info before deleting
            $stmt = $conn->prepare("SELECT title, user_id, is_global FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            $notification = $stmt->fetch();
            
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['error' => 'Notification not found']);
                break;
            }
            
            // Delete notification
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            
            // Log admin activity
            $targetText = $notification['is_global'] ? 'global' : "user ID {$notification['user_id']}";
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'delete_notification',
                "Deleted notification ({$targetText}): {$notification['title']}",
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 