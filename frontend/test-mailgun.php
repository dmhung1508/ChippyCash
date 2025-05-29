<?php
/**
 * Test Mailgun Configuration
 * Truy cập: http://localhost:8000/test-mailgun.php
 */

require_once 'includes/mailgun.php';

// Kiểm tra nếu có POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['email'] ?? '';
    $testType = $_POST['type'] ?? 'basic';
    
    if (empty($testEmail)) {
        $error = 'Vui lòng nhập email để test';
    } else {
        switch ($testType) {
            case 'basic':
                $result = testMailgunConnection($testEmail);
                break;
            case 'welcome':
                $result = sendWelcomeEmail($testEmail, 'Test User');
                break;
            case 'reset':
                $result = sendPasswordResetEmail($testEmail, 'test-token-123', 'Test User');
                break;
            default:
                $result = ['success' => false, 'error' => 'Loại test không hợp lệ'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mailgun - Quản Lý Thu Chi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .content {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .btn {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .config-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .config-info h3 {
            margin-top: 0;
            color: #374151;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .config-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-configured {
            background: #d1fae5;
            color: #065f46;
        }
        .status-not-configured {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> Test Mailgun</h1>
            <p>Kiểm tra cấu hình và gửi email test</p>
        </div>
        
        <div class="content">
            <!-- Hiển thị cấu hình hiện tại -->
            <div class="config-info">
                <h3><i class="fas fa-cog"></i> Cấu hình hiện tại</h3>
                <?php
                global $mailgun_config;
                $isConfigured = !empty($mailgun_config['api_key']) && $mailgun_config['api_key'] !== 'your-mailgun-api-key-here';
                ?>
                <div class="config-item">
                    <span>API Key:</span>
                    <span class="status-badge <?php echo $isConfigured ? 'status-configured' : 'status-not-configured'; ?>">
                        <?php echo $isConfigured ? 'Đã cấu hình' : 'Chưa cấu hình'; ?>
                    </span>
                </div>
                <div class="config-item">
                    <span>Domain:</span>
                    <span><?php echo htmlspecialchars($mailgun_config['domain']); ?></span>
                </div>
                <div class="config-item">
                    <span>From Email:</span>
                    <span><?php echo htmlspecialchars($mailgun_config['from_email']); ?></span>
                </div>
                <div class="config-item">
                    <span>From Name:</span>
                    <span><?php echo htmlspecialchars($mailgun_config['from_name']); ?></span>
                </div>
            </div>

            <!-- Hiển thị kết quả -->
            <?php if (isset($result)): ?>
                <div class="alert <?php echo $result['success'] ? 'alert-success' : 'alert-error'; ?>">
                    <?php if ($result['success']): ?>
                        <i class="fas fa-check-circle"></i> <?php echo $result['message']; ?>
                        <?php if (isset($result['data']['id'])): ?>
                            <br><small>Message ID: <?php echo $result['data']['id']; ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $result['error']; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form test -->
            <form method="POST">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email nhận test
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="your-email@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="type">
                        <i class="fas fa-list"></i> Loại email test
                    </label>
                    <select id="type" name="type">
                        <option value="basic" <?php echo ($_POST['type'] ?? '') === 'basic' ? 'selected' : ''; ?>>
                            📧 Email test cơ bản
                        </option>
                        <option value="welcome" <?php echo ($_POST['type'] ?? '') === 'welcome' ? 'selected' : ''; ?>>
                            🎉 Email chào mừng
                        </option>
                        <option value="reset" <?php echo ($_POST['type'] ?? '') === 'reset' ? 'selected' : ''; ?>>
                            🔐 Email reset password
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Gửi Email Test
                </button>
            </form>

            <!-- Hướng dẫn cấu hình -->
            <?php if (!$isConfigured): ?>
                <div style="margin-top: 30px; padding: 20px; background: #fef3c7; border-radius: 8px; border: 1px solid #f59e0b;">
                    <h3 style="color: #92400e; margin-top: 0;">
                        <i class="fas fa-info-circle"></i> Cách cấu hình Mailgun
                    </h3>
                    <ol style="color: #92400e;">
                        <li>Đăng ký tài khoản tại <a href="https://mailgun.com" target="_blank">mailgun.com</a></li>
                        <li>Lấy API Key từ dashboard</li>
                        <li>Mở file <code>config/database.php</code></li>
                        <li>Thay đổi các giá trị sau:
                            <pre style="background: #374151; color: #f3f4f6; padding: 10px; border-radius: 4px; margin: 10px 0;">
define('MAILGUN_API_KEY', 'your-actual-api-key');
define('MAILGUN_DOMAIN', 'your-domain.mailgun.org');
define('MAILGUN_FROM_EMAIL', 'noreply@your-domain.com');</pre>
                        </li>
                        <li>Lưu file và test lại</li>
                    </ol>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" style="color: #3b82f6; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html> 