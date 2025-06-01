<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
   redirectTo('index.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Update username in session
$_SESSION['user_name'] = $user['name'];

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    // Đảm bảo UTF-8 encoding cho name
    if ($name) {
        $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
    }
    
    if (!$name || strlen($name) < 3) {
        setFlashMessage('error', 'Tên phải có ít nhất 3 ký tự');
    } elseif (!$username || strlen($username) < 3) {
        setFlashMessage('error', 'Tên đăng nhập phải có ít nhất 3 ký tự');
    } elseif (strpos($username, ' ') !== false) {
        setFlashMessage('error', 'Tên đăng nhập không được chứa khoảng trắng');
    } elseif ($username !== $user['username'] && isUsernameExists($conn, $username)) {
        setFlashMessage('error', 'Tên đăng nhập đã được sử dụng bởi tài khoản khác');
    } elseif (!$email) {
        setFlashMessage('error', 'Email không hợp lệ');
    } elseif ($email !== $user['email'] && isEmailExists($conn, $email)) {
        setFlashMessage('error', 'Email đã được sử dụng bởi tài khoản khác');
    } else {
        if (updateUserProfile($conn, $user_id, $name, $username, $email)) {
            setFlashMessage('success', 'Cập nhật thông tin thành công!');
            // Update user info
            $user = getUserById($conn, $user_id);
            // Update session
            $_SESSION['user_name'] = $name;
        } else {
            setFlashMessage('error', 'Đã xảy ra lỗi khi cập nhật thông tin.');
        }
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
   $current_password = $_POST['current_password'] ?? '';
   $new_password = $_POST['new_password'] ?? '';
   $confirm_password = $_POST['confirm_password'] ?? '';
   
   if (!password_verify($current_password, $user['password'])) {
       setFlashMessage('error', 'Mật khẩu hiện tại không đúng');
   } elseif (strlen($new_password) < 6) {
       setFlashMessage('error', 'Mật khẩu mới phải có ít nhất 6 ký tự');
   } elseif ($new_password !== $confirm_password) {
       setFlashMessage('error', 'Mật khẩu xác nhận không khớp');
   } else {
       $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
       if (updateUserPassword($conn, $user_id, $hashed_password)) {
           setFlashMessage('success', 'Đổi mật khẩu thành công!');
       } else {
           setFlashMessage('error', 'Đã xảy ra lỗi khi đổi mật khẩu.');
       }
   }
}

// Get account statistics
$totalTransactions = getTotalTransactionCount($conn, $user_id);
$totalIncome = getTotalByType($conn, $user_id, 'income');
$totalExpense = getTotalByType($conn, $user_id, 'expense');
$accountCreated = $user['created_at'];
$lastLogin = $user['last_login'] ?? 'Chưa có thông tin';

$pageTitle = "Hồ sơ cá nhân";
include 'includes/header.php';
?>

<div class="app-container">
    <header class="page-header">
        <div class="header-content">
            <div class="header-left">
                <h1>👤 Hồ sơ cá nhân</h1>
                <p class="subtitle">Quản lý thông tin tài khoản của bạn</p>
            </div>
            <div class="header-right">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php echo displayFlashMessages(); ?>
        
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng giao dịch</div>
                                <div class="stat-value"><?php echo $totalTransactions; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item income">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng thu nhập</div>
                                <div class="stat-value"><?php echo formatMoney($totalIncome); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item expense">
                            <div class="stat-icon">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Tổng chi tiêu</div>
                                <div class="stat-value"><?php echo formatMoney($totalExpense); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Ngày tạo tài khoản</div>
                                <div class="stat-value"><?php echo date('d/m/Y', strtotime($accountCreated)); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Đăng nhập gần nhất</div>
                                <div class="stat-value"><?php echo is_string($lastLogin) ? $lastLogin : date('d/m/Y H:i', strtotime($lastLogin)); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="profile-content">
                <div class="tabs-container">
                    <nav class="tab-nav">
                        <button class="tab-button active" data-tab="profile-info">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </button>
                        <button class="tab-button" data-tab="change-password">
                            <i class="fas fa-lock"></i> Đổi mật khẩu
                        </button>
                    </nav>
                    
                    <div class="tab-content">
                        <!-- Tab Thông tin cá nhân -->
                        <div id="profile-info" class="tab-pane active">
                            <div class="form-header">
                                <h3><i class="fas fa-edit"></i> Cập nhật thông tin cá nhân</h3>
                                <p>Chỉnh sửa thông tin tài khoản của bạn</p>
                            </div>
                            
                            <form method="POST" action="" class="profile-form">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-user"></i> Họ tên
                                    </label>
                                    <input type="text" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" 
                                           required class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-at"></i> Tên đăng nhập
                                    </label>
                                    <input type="text" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           required class="form-input">
                                    <p class="form-hint">
                                        <i class="fas fa-info-circle"></i>
                                        Tên đăng nhập phải có ít nhất 3 ký tự và không chứa khoảng trắng
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           required class="form-input">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn-primary">
                                        <i class="fas fa-save"></i> Cập nhật thông tin
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Tab Đổi mật khẩu -->
                        <div id="change-password" class="tab-pane">
                            <div class="form-header">
                                <h3><i class="fas fa-shield-alt"></i> Đổi mật khẩu</h3>
                                <p>Thay đổi mật khẩu để bảo mật tài khoản</p>
                            </div>
                            
                            <form method="POST" action="" class="profile-form">
                                <div class="form-group">
                                    <label for="current_password">
                                        <i class="fas fa-lock"></i> Mật khẩu hiện tại
                                    </label>
                                    <input type="password" id="current_password" name="current_password" 
                                           required class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">
                                        <i class="fas fa-key"></i> Mật khẩu mới
                                    </label>
                                    <input type="password" id="new_password" name="new_password" 
                                           required class="form-input">
                                    <p class="form-hint">
                                        <i class="fas fa-info-circle"></i>
                                        Mật khẩu phải có ít nhất 6 ký tự
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">
                                        <i class="fas fa-check-circle"></i> Xác nhận mật khẩu mới
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           required class="form-input">
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="change_password" class="btn-danger">
                                        <i class="fas fa-key"></i> Đổi mật khẩu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
/* Profile Page Clean Styles */
.page-header {
    background: var(--card-background);
    border-bottom: 1px solid var(--border-color);
    padding: 2rem 0;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left h1 {
    margin: 0 0 8px 0;
    color: var(--primary-color);
    font-size: 1.8rem;
    font-weight: 600;
}

.header-left .subtitle {
    margin: 0;
    color: var(--secondary-color);
    font-size: 1rem;
}

.btn-back {
    background: var(--hover-color);
    color: var(--primary-color);
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-back:hover {
    background: var(--border-color);
    transform: translateY(-1px);
}

.main-content {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.profile-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
}

/* Profile Card */
.profile-card {
    background: var(--card-background);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
}

.profile-avatar {
    font-size: 4rem;
    color: var(--accent-color);
    margin-bottom: 1.5rem;
}

.profile-name {
    margin: 0 0 8px 0;
    color: var(--primary-color);
    font-size: 1.3rem;
    font-weight: 600;
}

.profile-username {
    margin: 0 0 6px 0;
    color: var(--accent-color);
    font-size: 1rem;
    font-weight: 500;
}

.profile-email {
    margin: 0 0 2rem 0;
    color: var(--secondary-color);
    font-size: 0.9rem;
}

/* Profile Stats */
.profile-stats {
    border-top: 1px solid var(--border-color);
    padding-top: 1.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 1rem;
    background: var(--hover-color);
    border-radius: 8px;
    text-align: left;
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: var(--card-background);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: var(--accent-color);
    font-size: 1.1rem;
}

.stat-item.income .stat-icon {
    color: var(--positive-color);
    background: rgba(34, 197, 94, 0.1);
}

.stat-item.expense .stat-icon {
    color: var(--negative-color);
    background: rgba(239, 68, 68, 0.1);
}

.stat-content {
    flex: 1;
}

.stat-label {
    color: var(--secondary-color);
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.stat-value {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 1rem;
}

.stat-item.income .stat-value {
    color: var(--positive-color);
}

.stat-item.expense .stat-value {
    color: var(--negative-color);
}

/* Tabs */
.tabs-container {
    background: var(--card-background);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.tab-nav {
    background: var(--hover-color);
    padding: 0.5rem;
    display: flex;
    gap: 0.5rem;
}

.tab-button {
    flex: 1;
    padding: 1rem 1.5rem;
    border: none;
    background: transparent;
    color: var(--secondary-color);
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.tab-button.active {
    background: var(--card-background);
    color: var(--primary-color);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tab-button:hover:not(.active) {
    background: rgba(255, 255, 255, 0.5);
    color: var(--primary-color);
}

.tab-content {
    padding: 2rem;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Form Styles */
.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h3 {
    margin: 0 0 8px 0;
    color: var(--primary-color);
    font-size: 1.2rem;
    font-weight: 600;
}

.form-header p {
    margin: 0;
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.profile-form {
    max-width: 500px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--primary-color);
    font-weight: 500;
    font-size: 0.9rem;
}

.form-group label i {
    color: var(--accent-color);
    margin-right: 6px;
    width: 16px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--card-background);
    color: var(--primary-color);
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    margin: 8px 0 0 0;
    color: var(--secondary-color);
    font-size: 0.8rem;
}

.form-hint i {
    color: var(--accent-color);
    margin-right: 4px;
}

.form-actions {
    text-align: center;
    margin-top: 2rem;
}

.btn-primary, .btn-danger {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.btn-primary {
    background: var(--accent-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-color);
    transform: translateY(-1px);
}

.btn-danger {
    background: var(--negative-color);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .profile-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .tab-nav {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .tab-button {
        justify-content: flex-start;
    }
    
    .main-content {
        padding: 1rem;
    }
    
    .profile-card {
        padding: 1.5rem;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button and target pane
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
