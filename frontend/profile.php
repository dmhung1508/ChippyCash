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
   $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
   $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
   $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
   
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
$accountCreated = $user['created_at'];
$lastLogin = $user['last_login'] ?? 'Chưa có thông tin';

$pageTitle = "Hồ sơ cá nhân";
include 'includes/header.php';
?>

<div class="app-container">
   <header class="page-header">
       <div class="header-content">
           <div class="header-left">
               <h1>Hồ sơ cá nhân</h1>
               <p class="subtitle">Quản lý thông tin tài khoản của bạn</p>
           </div>
           <div class="header-right">
               <a href="index.php" class="btn-link">
                   <i class="fas fa-arrow-left"></i> Quay lại
               </a>
           </div>
       </div>
   </header>

   <main class="main-content">
       <?php echo displayFlashMessages(); ?>
       
       <div class="profile-container">
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
                           <div class="stat-label">Tổng giao dịch</div>
                           <div class="stat-value"><?php echo $totalTransactions; ?></div>
                       </div>
                       <div class="stat-item">
                           <div class="stat-label">Ngày tạo tài khoản</div>
                           <div class="stat-value"><?php echo date('d/m/Y', strtotime($accountCreated)); ?></div>
                       </div>
                       <div class="stat-item">
                           <div class="stat-label">Đăng nhập gần nhất</div>
                           <div class="stat-value"><?php echo is_string($lastLogin) ? $lastLogin : date('d/m/Y H:i', strtotime($lastLogin)); ?></div>
                       </div>
                   </div>
               </div>
           </div>
           
           <div class="profile-content">
               <div class="content-tabs">
                   <nav class="tab-nav">
                       <button class="tab-button active" data-tab="profile-info">Thông tin cá nhân</button>
                       <button class="tab-button" data-tab="change-password">Đổi mật khẩu</button>
                   </nav>
                   
                   <div class="tab-content">
                       <!-- Tab Thông tin cá nhân -->
                       <div id="profile-info" class="tab-pane active">
                           <form method="POST" action="" class="profile-form">
                               <div class="form-group">
                                   <label for="name">Họ tên</label>
                                   <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                               </div>
                               
                               <div class="form-group">
                                   <label for="username">Tên đăng nhập</label>
                                   <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                   <p class="form-hint">Tên đăng nhập phải có ít nhất 3 ký tự và không chứa khoảng trắng</p>
                               </div>
                               
                               <div class="form-group">
                                   <label for="email">Email</label>
                                   <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                               </div>
                               
                               <div class="form-actions">
                                   <button type="submit" name="update_profile" class="btn-primary">Cập nhật thông tin</button>
                               </div>
                           </form>
                       </div>
                       
                       <!-- Tab Đổi mật khẩu -->
                       <div id="change-password" class="tab-pane">
                           <form method="POST" action="" class="profile-form">
                               <div class="form-group">
                                   <label for="current_password">Mật khẩu hiện tại</label>
                                   <input type="password" id="current_password" name="current_password" required>
                               </div>
                               
                               <div class="form-group">
                                   <label for="new_password">Mật khẩu mới</label>
                                   <input type="password" id="new_password" name="new_password" required>
                                   <p class="form-hint">Mật khẩu phải có ít nhất 6 ký tự</p>
                               </div>
                               
                               <div class="form-group">
                                   <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                   <input type="password" id="confirm_password" name="confirm_password" required>
                               </div>
                               
                               <div class="form-actions">
                                   <button type="submit" name="change_password" class="btn-primary">Đổi mật khẩu</button>
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
   .profile-container {
       display: grid;
       grid-template-columns: 300px 1fr;
       gap: 2rem;
   }
   
   .profile-card {
       background-color: var(--card-background);
       border-radius: 1rem;
       padding: 2rem;
       box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
       border: 1px solid var(--border-color);
       text-align: center;
   }
   
   .profile-avatar {
       font-size: 5rem;
       color: var(--accent-color);
       margin-bottom: 1rem;
   }
   
   .profile-name {
       font-size: 1.5rem;
       font-weight: 600;
       margin-bottom: 0.25rem;
   }
   
   .profile-username {
       color: var(--accent-color);
       font-weight: 500;
       margin-bottom: 0.5rem;
   }
   
   .profile-email {
       color: var(--text-secondary);
       margin-bottom: 1.5rem;
   }
   
   .profile-stats {
       border-top: 1px solid var(--border-color);
       padding-top: 1.5rem;
   }
   
   .stat-item {
       margin-bottom: 1rem;
       text-align: left;
   }
   
   .stat-label {
       color: var(--text-secondary);
       font-size: 0.875rem;
   }
   
   .stat-value {
       font-weight: 600;
       margin-top: 0.25rem;
   }
   
   .profile-form {
       max-width: 600px;
   }
   
   .form-hint {
       color: var(--text-secondary);
       font-size: 0.875rem;
       margin-top: 0.25rem;
   }
   
   @media (max-width: 768px) {
       .profile-container {
           grid-template-columns: 1fr;
       }
   }
</style>

<?php include 'includes/footer.php'; ?>

