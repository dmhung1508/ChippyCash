<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    // Nhận dữ liệu từ client
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        throw new Exception('Username và password là bắt buộc');
    }
    
    $username = $input['username'];
    $password = $input['password'];
    
    // Tạo URL với parameters
    $bankApiUrl = 'http://127.0.0.1:8506/bank?' . http_build_query([
        'username' => $username,
        'password' => $password
    ]);
    
    // Khởi tạo cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $bankApiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Lỗi kết nối: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('API trả về lỗi: HTTP ' . $httpCode);
    }
    
    $bankData = json_decode($response, true);
    
    if (!$bankData) {
        throw new Exception('Không thể parse dữ liệu từ API');
    }
    
    // Lưu thông tin bank vào session để sử dụng lại
    $_SESSION['bank_data'] = $bankData;
    $_SESSION['bank_data_timestamp'] = time();
    
    // Trả về dữ liệu
    echo json_encode([
        'success' => true,
        'data' => $bankData
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 