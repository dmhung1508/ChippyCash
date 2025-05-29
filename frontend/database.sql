-- Tạo lại database với UTF-8
DROP DATABASE IF EXISTS chippy;
CREATE DATABASE chippy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE chippy;

-- Tạo bảng users
CREATE TABLE users (
   id INT AUTO_INCREMENT PRIMARY KEY,
   name VARCHAR(100) NOT NULL,
   username VARCHAR(50) NOT NULL UNIQUE,
   email VARCHAR(100) NOT NULL UNIQUE,
   password VARCHAR(255) NOT NULL,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   updated_at DATETIME DEFAULT NULL,
   last_login DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng categories (thể loại tùy chỉnh)
CREATE TABLE categories (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   name VARCHAR(100) NOT NULL,
   description TEXT,
   type ENUM('income', 'expense') NOT NULL,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   updated_at DATETIME DEFAULT NULL,
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng transactions
CREATE TABLE transactions (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   amount DECIMAL(15,2) NOT NULL,
   description VARCHAR(255) NOT NULL,
   type ENUM('income', 'expense') NOT NULL,
   category VARCHAR(50) DEFAULT NULL,
   date DATE NOT NULL,
   bank_transaction_id VARCHAR(255) DEFAULT NULL,
   is_bank_import TINYINT(1) DEFAULT 0,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   updated_at DATETIME DEFAULT NULL,
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng chat_history
CREATE TABLE chat_history (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   message TEXT NOT NULL,
   is_bot TINYINT(1) DEFAULT 0,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng bank_accounts
CREATE TABLE bank_accounts (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   bank_username VARCHAR(100) NOT NULL,
   bank_password VARCHAR(255) NOT NULL,
   account_name VARCHAR(255) DEFAULT NULL,
   last_sync DATETIME DEFAULT NULL,
   is_active TINYINT(1) DEFAULT 1,
   created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   updated_at DATETIME DEFAULT NULL,
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
   UNIQUE KEY unique_user_bank (user_id, bank_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm chỉ mục để tối ưu truy vấn
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_chat_history_user_id ON chat_history(user_id);
CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_categories_type ON categories(type);

-- Sample data
INSERT IGNORE INTO users (name, username, password, email) VALUES 
('Demo User', 'demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'demo@example.com');

-- Sample categories cho user demo (id = 1)
INSERT IGNORE INTO categories (user_id, name, description, type) VALUES
(1, 'Lương', 'Lương hàng tháng', 'income'),
(1, 'Thưởng', 'Thưởng thành tích', 'income'),
(1, 'Đầu tư', 'Thu nhập từ đầu tư', 'income'),
(1, 'Ăn uống', 'Chi phí ăn uống hàng ngày', 'expense'),
(1, 'Di chuyển', 'Chi phí xe bus, taxi, xăng xe', 'expense'),
(1, 'Mua sắm', 'Quần áo, đồ dùng cá nhân', 'expense'),
(1, 'Giải trí', 'Xem phim, du lịch, vui chơi', 'expense'),
(1, 'Hóa đơn', 'Điện, nước, internet, điện thoại', 'expense');

-- Sample transactions
INSERT IGNORE INTO transactions (user_id, amount, description, category, type, date) VALUES
(1, 15000000, 'Lương tháng 12', 'Lương', 'income', '2024-12-01'),
(1, 50000, 'Ăn trưa', 'Ăn uống', 'expense', '2024-12-01'),
(1, 30000, 'Taxi về nhà', 'Di chuyển', 'expense', '2024-12-01'),
(1, 200000, 'Mua áo sơ mi', 'Mua sắm', 'expense', '2024-12-02'),
(1, 150000, 'Xem phim', 'Giải trí', 'expense', '2024-12-02');
