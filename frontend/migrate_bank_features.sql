-- Migration script để thêm bank features
-- Chạy script này nếu database đã tồn tại

-- Thêm bảng bank_accounts nếu chưa có
CREATE TABLE IF NOT EXISTS bank_accounts (
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

-- Thêm cột bank_transaction_id nếu chưa có
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS bank_transaction_id VARCHAR(255) DEFAULT NULL;

-- Thêm cột is_bank_import nếu chưa có
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS is_bank_import TINYINT(1) DEFAULT 0;

-- Thêm index cho bank_transaction_id
CREATE INDEX IF NOT EXISTS idx_bank_transaction_id ON transactions(bank_transaction_id);
CREATE INDEX IF NOT EXISTS idx_is_bank_import ON transactions(is_bank_import);
CREATE INDEX IF NOT EXISTS idx_bank_accounts_user_id ON bank_accounts(user_id); 