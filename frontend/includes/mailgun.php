<?php
/**
 * Mailgun Email Helper
 * Sử dụng cấu hình từ database.php
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gửi email qua Mailgun API
 * 
 * @param string $to Email người nhận
 * @param string $subject Tiêu đề email
 * @param string $message Nội dung email (HTML)
 * @param string $from_name Tên người gửi (optional)
 * @return array Kết quả gửi email
 */
function sendMailgunEmail($to, $subject, $message, $from_name = null) {
    global $mailgun_config;
    
    // Kiểm tra cấu hình
    if (empty($mailgun_config['api_key']) || $mailgun_config['api_key'] === 'your-mailgun-api-key-here') {
        return [
            'success' => false,
            'error' => 'Mailgun API key chưa được cấu hình'
        ];
    }
    
    // Chuẩn bị dữ liệu
    $from_name = $from_name ?: $mailgun_config['from_name'];
    $from = $from_name . ' <' . $mailgun_config['from_email'] . '>';
    
    $postData = [
        'from' => $from,
        'to' => $to,
        'subject' => $subject,
        'html' => $message
    ];
    
    // Gửi request đến Mailgun
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $mailgun_config['api_url']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $mailgun_config['api_key']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Xử lý kết quả
    if ($error) {
        return [
            'success' => false,
            'error' => 'Curl error: ' . $error
        ];
    }
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Email đã được gửi thành công',
            'data' => $responseData
        ];
    } else {
        return [
            'success' => false,
            'error' => 'HTTP Error ' . $httpCode . ': ' . $response
        ];
    }
}

/**
 * Gửi email reset password
 */
function sendPasswordResetEmail($email, $resetToken, $userName) {
    $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password.php?token=' . $resetToken;
    
    $subject = 'Đặt lại mật khẩu - Quản Lý Thu Chi';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔐 Đặt lại mật khẩu</h1>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản Quản Lý Thu Chi.</p>
                <p>Nhấp vào nút bên dưới để đặt lại mật khẩu:</p>
                <p style="text-align: center;">
                    <a href="' . $resetLink . '" class="button">Đặt lại mật khẩu</a>
                </p>
                <p><strong>Lưu ý:</strong> Link này sẽ hết hạn sau 1 giờ.</p>
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Quản Lý Thu Chi. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendMailgunEmail($email, $subject, $message);
}

/**
 * Gửi email thông báo đăng ký thành công
 */
function sendWelcomeEmail($email, $userName) {
    $subject = 'Chào mừng đến với Quản Lý Thu Chi!';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #22c55e; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .feature { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3b82f6; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Chào mừng bạn!</h1>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                <p>Cảm ơn bạn đã đăng ký tài khoản Quản Lý Thu Chi!</p>
                
                <h3>🚀 Bạn có thể bắt đầu:</h3>
                <div class="feature">
                    <strong>💰 Theo dõi thu chi:</strong> Ghi lại mọi giao dịch hàng ngày
                </div>
                <div class="feature">
                    <strong>📊 Phân tích tài chính:</strong> Xem báo cáo chi tiết
                </div>
                <div class="feature">
                    <strong>🏷️ Quản lý danh mục:</strong> Tổ chức giao dịch theo loại
                </div>
                <div class="feature">
                    <strong>🤖 Trợ lý AI:</strong> Nhận tư vấn tài chính thông minh
                </div>
                
                <p style="text-align: center; margin-top: 30px;">
                    <a href="http://' . $_SERVER['HTTP_HOST'] . '" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">
                        Bắt đầu sử dụng ngay
                    </a>
                </p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Quản Lý Thu Chi. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendMailgunEmail($email, $subject, $message);
}

/**
 * Test gửi email
 */
function testMailgunConnection($testEmail = null) {
    $testEmail = $testEmail ?: 'test@example.com';
    
    $subject = 'Test Email - Quản Lý Thu Chi';
    $message = '
    <h2>🧪 Test Email</h2>
    <p>Đây là email test để kiểm tra kết nối Mailgun.</p>
    <p><strong>Thời gian:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p>Nếu bạn nhận được email này, Mailgun đã hoạt động tốt!</p>';
    
    return sendMailgunEmail($testEmail, $subject, $message);
}
?> 