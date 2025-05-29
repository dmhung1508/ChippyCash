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
            // Lấy tất cả settings
            $stmt = $conn->query("SELECT * FROM system_settings ORDER BY setting_key");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Chuyển về dạng key-value object
            $settingsObj = [];
            foreach ($settings as $setting) {
                $settingsObj[$setting['setting_key']] = [
                    'value' => $setting['setting_value'],
                    'description' => $setting['description'],
                    'type' => $setting['setting_type'],
                    'is_public' => (bool)$setting['is_public']
                ];
            }
            
            echo json_encode(['success' => true, 'settings' => $settingsObj]);
            break;
            
        case 'POST':
        case 'PUT':
            // Cập nhật settings
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['settings'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                break;
            }
            
            $conn->beginTransaction();
            
            try {
                foreach ($input['settings'] as $key => $value) {
                    // Validate setting value based on type
                    $stmt = $conn->prepare("SELECT setting_type FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $setting = $stmt->fetch();
                    
                    if (!$setting) {
                        continue; // Skip unknown settings
                    }
                    
                    // Type validation
                    switch ($setting['setting_type']) {
                        case 'boolean':
                            $value = $value ? '1' : '0';
                            break;
                        case 'number':
                            if (!is_numeric($value)) {
                                throw new Exception("Invalid number value for {$key}");
                            }
                            break;
                        case 'json':
                            if (!is_array($value)) {
                                $value = json_encode($value);
                            } else {
                                $value = json_encode($value);
                            }
                            break;
                        default:
                            $value = (string)$value;
                    }
                    
                    // Update setting
                    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                
                // Log admin activity
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    'update_settings',
                    'Updated system settings: ' . implode(', ', array_keys($input['settings'])),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
                
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;
            
        case 'DELETE':
            // Xóa một setting (nếu cần)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['key'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting key required']);
                break;
            }
            
            $stmt = $conn->prepare("DELETE FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$input['key']]);
            
            // Log admin activity
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'delete_setting',
                'Deleted setting: ' . $input['key'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Setting deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 