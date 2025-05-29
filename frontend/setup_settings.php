<?php
/**
 * Script Setup System Settings
 * Chạy file này để tự động tạo bảng và cài đặt mặc định
 */

require_once 'config/database.php';

try {
    echo "🚀 Bắt đầu setup System Settings...\n\n";

    // Kiểm tra và tạo bảng system_settings
    $stmt = $conn->query("SHOW TABLES LIKE 'system_settings'");
    
    if ($stmt->rowCount() == 0) {
        echo "📊 Tạo bảng system_settings...\n";
        
        $createTable = "
        CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            is_public TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->exec($createTable);
        echo "✅ Tạo bảng system_settings thành công!\n\n";
    } else {
        echo "✅ Bảng system_settings đã tồn tại.\n\n";
    }

    // Kiểm tra và thêm settings mặc định
    $defaultSettings = [
        [
            'key' => 'site_name',
            'value' => 'Quản Lý Thu Chi Chippy',
            'description' => 'Tên website',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'site_description',
            'value' => 'Hệ thống quản lý tài chính cá nhân',
            'description' => 'Mô tả website',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'maintenance_mode',
            'value' => '0',
            'description' => 'Chế độ bảo trì hệ thống',
            'type' => 'boolean',
            'is_public' => 0
        ],
        [
            'key' => 'user_registration',
            'value' => '1',
            'description' => 'Cho phép đăng ký user mới',
            'type' => 'boolean',
            'is_public' => 0
        ],
        [
            'key' => 'max_transactions_per_user',
            'value' => '10000',
            'description' => 'Số giao dịch tối đa mỗi user',
            'type' => 'number',
            'is_public' => 0
        ],
        [
            'key' => 'backup_frequency',
            'value' => '24',
            'description' => 'Tần suất backup tự động (giờ)',
            'type' => 'number',
            'is_public' => 0
        ],
        [
            'key' => 'session_timeout',
            'value' => '1440',
            'description' => 'Thời gian hết hạn session (phút)',
            'type' => 'number',
            'is_public' => 0
        ],
        [
            'key' => 'max_file_upload_size',
            'value' => '10',
            'description' => 'Kích thước file upload tối đa (MB)',
            'type' => 'number',
            'is_public' => 0
        ],
        [
            'key' => 'email_notifications',
            'value' => '1',
            'description' => 'Bật thông báo email',
            'type' => 'boolean',
            'is_public' => 0
        ],
        [
            'key' => 'admin_email',
            'value' => 'admin@chippy.local',
            'description' => 'Email admin chính',
            'type' => 'string',
            'is_public' => 0
        ],
        [
            'key' => 'timezone',
            'value' => 'Asia/Ho_Chi_Minh',
            'description' => 'Múi giờ hệ thống',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'currency',
            'value' => 'VND',
            'description' => 'Đơn vị tiền tệ',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'date_format',
            'value' => 'd/m/Y',
            'description' => 'Định dạng ngày tháng',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'items_per_page',
            'value' => '20',
            'description' => 'Số items mỗi trang',
            'type' => 'number',
            'is_public' => 0
        ],
        [
            'key' => 'enable_bank_sync',
            'value' => '1',
            'description' => 'Bật đồng bộ ngân hàng',
            'type' => 'boolean',
            'is_public' => 0
        ],
        [
            'key' => 'theme_mode',
            'value' => 'auto',
            'description' => 'Chế độ giao diện (light/dark/auto)',
            'type' => 'string',
            'is_public' => 1
        ],
        [
            'key' => 'auto_backup',
            'value' => '1',
            'description' => 'Tự động backup database',
            'type' => 'boolean',
            'is_public' => 0
        ],
        [
            'key' => 'security_level',
            'value' => 'medium',
            'description' => 'Mức độ bảo mật (low/medium/high)',
            'type' => 'string',
            'is_public' => 0
        ]
    ];

    echo "⚙️ Thêm settings mặc định...\n";
    
    $insertStmt = $conn->prepare("
        INSERT IGNORE INTO system_settings (setting_key, setting_value, description, setting_type, is_public) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $addedCount = 0;
    foreach ($defaultSettings as $setting) {
        $result = $insertStmt->execute([
            $setting['key'],
            $setting['value'],
            $setting['description'],
            $setting['type'],
            $setting['is_public']
        ]);
        
        if ($insertStmt->rowCount() > 0) {
            echo "  ➕ Thêm setting: {$setting['key']}\n";
            $addedCount++;
        }
    }

    if ($addedCount > 0) {
        echo "✅ Đã thêm {$addedCount} settings mới!\n\n";
    } else {
        echo "✅ Tất cả settings đã tồn tại.\n\n";
    }

    // Kiểm tra và tạo bảng admin_logs nếu chưa có
    $stmt = $conn->query("SHOW TABLES LIKE 'admin_logs'");
    
    if ($stmt->rowCount() == 0) {
        echo "📊 Tạo bảng admin_logs...\n";
        
        $createAdminLogs = "
        CREATE TABLE admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) DEFAULT NULL,
            target_id INT DEFAULT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->exec($createAdminLogs);
        echo "✅ Tạo bảng admin_logs thành công!\n\n";
    } else {
        echo "✅ Bảng admin_logs đã tồn tại.\n\n";
    }

    // Kiểm tra và tạo bảng notifications nếu chưa có
    $stmt = $conn->query("SHOW TABLES LIKE 'notifications'");
    
    if ($stmt->rowCount() == 0) {
        echo "📊 Tạo bảng notifications...\n";
        
        $createNotifications = "
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read TINYINT(1) DEFAULT 0,
            is_global TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->exec($createNotifications);
        echo "✅ Tạo bảng notifications thành công!\n\n";
    } else {
        echo "✅ Bảng notifications đã tồn tại.\n\n";
    }

    // Cập nhật user đầu tiên thành admin nếu chưa có admin
    $stmt = $conn->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetch()['admin_count'];

    if ($adminCount == 0) {
        echo "👑 Cập nhật user đầu tiên thành admin...\n";
        $conn->exec("UPDATE users SET role = 'admin' WHERE id = (SELECT id FROM (SELECT id FROM users ORDER BY id LIMIT 1) as tmp)");
        echo "✅ Đã tạo admin user!\n\n";
    } else {
        echo "✅ Đã có {$adminCount} admin trong hệ thống.\n\n";
    }

    echo "🎉 Setup hoàn thành!\n\n";
    echo "📋 Tóm tắt:\n";
    echo "- ✅ Bảng system_settings: OK\n";
    echo "- ✅ Bảng admin_logs: OK\n";
    echo "- ✅ Bảng notifications: OK\n";
    echo "- ✅ Settings mặc định: OK\n";
    echo "- ✅ Admin user: OK\n\n";
    
    echo "🔗 Bạn có thể truy cập Admin Panel tại: admin.php\n";
    echo "⚙️ Các settings sẽ được áp dụng tự động vào hệ thống.\n\n";

} catch (Exception $e) {
    echo "❌ Lỗi setup: " . $e->getMessage() . "\n";
    echo "🔍 Chi tiết: " . $e->getTraceAsString() . "\n";
}
?> 