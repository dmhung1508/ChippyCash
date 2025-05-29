<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><?php 
                if ($auth_mode === 'register') {
                    echo 'Đăng ký tài khoản';
                } elseif ($auth_mode === 'admin_login') {
                    echo '🛡️ Đăng nhập Admin';
                } else {
                    echo 'Đăng nhập';
                }
            ?></h1>
            <p class="auth-subtitle">
                <?php 
                if ($auth_mode === 'register') {
                    echo 'Tạo tài khoản mới để quản lý tài chính cá nhân';
                } elseif ($auth_mode === 'admin_login') {
                    echo 'Truy cập hệ thống quản trị trong thời gian bảo trì';
                } else {
                    echo 'Đăng nhập để quản lý tài chính cá nhân của bạn';
                }
                ?>
            </p>
        </div>
        
        <?php echo displayFlashMessages(); ?>
        
        <?php if ($auth_mode === 'login'): ?>
            <!-- Login Form -->
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="login" class="btn-primary btn-block">Đăng nhập</button>
                </div>
                
                <div class="auth-links">
                    <p>Chưa có tài khoản? <a href="index.php?register">Đăng ký ngay</a></p>
                </div>
            </form>
        <?php elseif ($auth_mode === 'admin_login'): ?>
            <!-- Admin Login Form -->
            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="admin_login" value="1">
                
                <div class="admin-notice" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <i class="fas fa-crown" style="margin-right: 8px;"></i>
                    <strong>Đăng nhập Admin</strong>
                    <p style="margin: 0.5rem 0 0; font-size: 0.9rem;">Chỉ dành cho quản trị viên hệ thống</p>
                </div>
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập Admin</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu Admin</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="login" class="btn-primary btn-block" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                        <i class="fas fa-shield-alt" style="margin-right: 8px;"></i>
                        Đăng nhập Admin
                    </button>
                </div>
                
                <div class="auth-links">
                    <p><a href="index.php" style="color: #6b7280;">← Quay lại trang chính</a></p>
                </div>
            </form>
        <?php else: ?>
            <!-- Registration Form -->
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="name">Họ tên</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" required>
                    <p class="form-hint">Tên đăng nhập phải có ít nhất 3 ký tự và không chứa khoảng trắng</p>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                    <p class="form-hint">Mật khẩu phải có ít nhất 6 ký tự</p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="register" class="btn-primary btn-block">Đăng ký</button>
                </div>
                
                <div class="auth-links">
                    <p>Đã có tài khoản? <a href="index.php">Đăng nhập</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: var(--background-color);
        padding: 2rem;
    }
    
    .auth-card {
        background-color: var(--card-background);
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        padding: 2.5rem;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .auth-header h1 {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .auth-subtitle {
        color: var(--text-secondary);
    }
    
    .auth-form {
        margin-top: 1.5rem;
    }
    
    .btn-block {
        width: 100%;
    }
    
    .auth-links {
        margin-top: 1.5rem;
        text-align: center;
    }
    
    .auth-links a {
        color: var(--accent-color);
        text-decoration: none;
    }
    
    .auth-links a:hover {
        text-decoration: underline;
    }
</style>
