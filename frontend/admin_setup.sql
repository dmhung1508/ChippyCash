-- Admin Panel Setup SQL
-- Chạy file này để setup đầy đủ admin panel

-- Thêm cột role vào bảng users nếu chưa có
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- Tạo bảng admin_logs để theo dõi hoạt động admin
CREATE TABLE IF NOT EXISTS admin_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng system_settings cho cấu hình hệ thống
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng notifications cho thông báo hệ thống
CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng backup_history để tracking backup
CREATE TABLE IF NOT EXISTS backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size BIGINT DEFAULT NULL,
    backup_type ENUM('manual', 'scheduled') DEFAULT 'manual',
    status ENUM('success', 'failed', 'in_progress') DEFAULT 'success',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng user_sessions để tracking user sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    logout_time DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm indices để tối ưu performance
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_action ON admin_logs(action);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_is_global ON notifications(is_global);
CREATE INDEX idx_backup_history_admin_id ON backup_history(admin_id);
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session_id ON user_sessions(session_id);

-- Cập nhật user demo thành admin (nếu có)
UPDATE users SET role = 'admin' WHERE username = 'demo' OR id = 1;

-- Tạo admin user mặc định (username: admin, password: admin123)
INSERT IGNORE INTO users (name, username, email, password, role) VALUES 
('Administrator', 'admin', 'admin@chippy.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Thêm các cài đặt hệ thống mặc định
INSERT IGNORE INTO system_settings (setting_key, setting_value, description, setting_type, is_public) VALUES
('site_name', 'Quản Lý Thu Chi', 'Tên website', 'string', 1),
('site_description', 'Hệ thống quản lý tài chính cá nhân', 'Mô tả website', 'string', 1),
('maintenance_mode', '0', 'Chế độ bảo trì hệ thống', 'boolean', 0),
('user_registration', '1', 'Cho phép đăng ký user mới', 'boolean', 0),
('max_transactions_per_user', '10000', 'Số giao dịch tối đa mỗi user', 'number', 0),
('backup_frequency', '24', 'Tần suất backup tự động (giờ)', 'number', 0),
('session_timeout', '1440', 'Thời gian hết hạn session (phút)', 'number', 0),
('max_file_upload_size', '10', 'Kích thước file upload tối đa (MB)', 'number', 0),
('email_notifications', '1', 'Bật thông báo email', 'boolean', 0),
('admin_email', 'admin@chippy.local', 'Email admin chính', 'string', 0),
('timezone', 'Asia/Ho_Chi_Minh', 'Múi giờ hệ thống', 'string', 1),
('currency', 'VND', 'Đơn vị tiền tệ', 'string', 1),
('date_format', 'd/m/Y', 'Định dạng ngày tháng', 'string', 1),
('items_per_page', '20', 'Số items mỗi trang', 'number', 0),
('enable_bank_sync', '1', 'Bật đồng bộ ngân hàng', 'boolean', 0);

-- Thêm một số thông báo mẫu
INSERT IGNORE INTO notifications (user_id, title, message, type, is_global) VALUES
(NULL, 'Chào mừng đến với Admin Panel', 'Hệ thống admin đã được cài đặt thành công. Bạn có thể quản lý users, cài đặt và giám sát hệ thống tại đây.', 'success', 1),
(NULL, 'Bảo mật hệ thống', 'Vui lòng đổi mật khẩu admin mặc định để đảm bảo bảo mật.', 'warning', 1);

-- Log setup completion
INSERT INTO admin_logs (admin_id, action, details, ip_address) 
SELECT id, 'admin_setup', 'Admin panel setup completed', '127.0.0.1' 
FROM users WHERE role = 'admin' LIMIT 1; 