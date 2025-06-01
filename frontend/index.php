<?php
// Enable error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/settings.php';

// Initialize and apply system settings
$systemSettings = getSystemSettingsManager($conn);
$systemSettings->applyToEnvironment();

// Handle authentication
$auth_mode = null;
if (!isset($_SESSION['user_id'])) {
   // Kiểm tra nếu đang ở maintenance mode với admin access
   if (isset($_GET['admin_access']) && $_GET['admin_access'] == '1') {
       $auth_mode = 'admin_login';
       $pageTitle = 'Đăng nhập Admin';
   } else {
       // Check maintenance mode chỉ khi không phải admin access
       checkMaintenanceMode($conn);
       $auth_mode = isset($_GET['register']) ? 'register' : 'login';
       $pageTitle = $auth_mode === 'register' ? 'Đăng ký' : 'Đăng nhập';
   }
   $hideNavbar = true;
   
   // Process login
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
               $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $password = $_POST['password'] ?? '';
       $isAdminLogin = isset($_POST['admin_login']) && $_POST['admin_login'] == '1';
       
       if (empty($username)) {
           setFlashMessage('error', 'Vui lòng nhập tên đăng nhập');
       } elseif (empty($password)) {
           setFlashMessage('error', 'Vui lòng nhập mật khẩu');
       } else {
           $user = getUserByUsername($conn, $username);
           
           if ($user && password_verify($password, $user['password'])) {
               // Nếu là admin login, kiểm tra quyền admin
               if ($isAdminLogin && $user['role'] !== 'admin') {
                   setFlashMessage('error', 'Bạn không có quyền admin để truy cập hệ thống trong thời gian bảo trì');
               } else {
                   // Set session variables
                   $_SESSION['user_id'] = $user['id'];
                   $_SESSION['user_name'] = $user['name'];
                   $_SESSION['role'] = $user['role'] ?? 'user';  // Thêm role vào session
                   
                   // Update last login time
                   updateLastLogin($conn, $user['id']);
                   
                   // Redirect based on login type
                   if ($isAdminLogin) {
                       redirectTo('admin.php');
                   } else {
                       redirectTo('index.php');
                   }
               }
           } else {
               setFlashMessage('error', 'Tên đăng nhập hoặc mật khẩu không đúng');
           }
       }
   }
   
   // Process registration
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
       // Check if registration is allowed
       if (!canUserRegister($conn)) {
           setFlashMessage('error', 'Hệ thống hiện tại không cho phép đăng ký user mới.');
       } else {
           $name = trim($_POST['name'] ?? '');
           $username = trim($_POST['username'] ?? '');
           $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
           $password = $_POST['password'] ?? '';
           $confirm_password = $_POST['confirm_password'] ?? '';
           
           if (empty($name) || strlen($name) < 3) {
               setFlashMessage('error', 'Tên phải có ít nhất 3 ký tự');
           } elseif (empty($username) || strlen($username) < 3) {
               setFlashMessage('error', 'Tên đăng nhập phải có ít nhất 3 ký tự');
           } elseif (strpos($username, ' ') !== false) {
               setFlashMessage('error', 'Tên đăng nhập không được chứa khoảng trắng');
           } elseif (isUsernameExists($conn, $username)) {
               setFlashMessage('error', 'Tên đăng nhập đã được sử dụng');
           } elseif (!$email) {
               setFlashMessage('error', 'Email không hợp lệ');
           } elseif (isEmailExists($conn, $email)) {
               setFlashMessage('error', 'Email đã được sử dụng');
           } elseif (strlen($password) < 6) {
               setFlashMessage('error', 'Mật khẩu phải có ít nhất 6 ký tự');
           } elseif ($password !== $confirm_password) {
               setFlashMessage('error', 'Mật khẩu xác nhận không khớp');
           } else {
               $hashed_password = password_hash($password, PASSWORD_DEFAULT);
               
               if (registerUser($conn, $name, $username, $email, $hashed_password)) {
                   setFlashMessage('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
                   redirectTo('index.php');
               } else {
                   setFlashMessage('error', 'Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại sau.');
               }
           }
       }
   }
   
   include 'includes/header.php';
   include 'includes/auth.php';
   include 'includes/footer.php';
   exit;
}

// Main application for authenticated users
$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Update username in session
$_SESSION['user_name'] = $user['name'];

// Get financial data
$totalIncome = getTotalIncome($conn, $user_id);
$totalExpense = getTotalExpense($conn, $user_id);
$balance = $totalIncome - $totalExpense;
$ratio = getIncomeExpenseRatio($conn, $user_id);

// Get recent transactions
$recentTransactions = getRecentTransactions($conn, $user_id, 5);

// Get categories
$income_categories = getUserCategories($conn, $user_id, 'income');
$expense_categories = getUserCategories($conn, $user_id, 'expense');

$pageTitle = "Quản lý Thu Chi";
include 'includes/header.php';
?>

<div class="app-container">
   <!-- Enhanced Magical Dashboard Header -->
   <header class="magical-dashboard-header" style="background:var(--card-background);border-bottom:1px solid var(--border-color);padding:2.5rem 0;position:relative;overflow:hidden;">
       <!-- Animated Background Pattern -->
       <div class="header-pattern" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.04;background:radial-gradient(circle at 15% 35%, var(--accent-color) 2px, transparent 2px), radial-gradient(circle at 85% 65%, var(--primary-color) 1.5px, transparent 1.5px), radial-gradient(circle at 50% 15%, var(--positive-color) 1px, transparent 1px);background-size:50px 50px, 80px 80px, 60px 60px;animation:patternMegaFloat 25s ease-in-out infinite;"></div>
       
       <!-- Floating Magical Particles -->
       <div class="floating-particles" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;">
           <div class="particle" style="position:absolute;width:8px;height:8px;background:linear-gradient(45deg,var(--accent-color),var(--primary-color));border-radius:50%;opacity:0.6;top:20%;left:15%;animation:floatDashboard1 12s ease-in-out infinite;box-shadow:0 0 10px rgba(102,126,234,0.3);"></div>
           <div class="particle" style="position:absolute;width:6px;height:6px;background:linear-gradient(45deg,var(--positive-color),var(--accent-color));border-radius:50%;opacity:0.5;top:70%;left:85%;animation:floatDashboard2 16s ease-in-out infinite 2s;box-shadow:0 0 8px rgba(34,197,94,0.3);"></div>
           <div class="particle" style="position:absolute;width:5px;height:5px;background:linear-gradient(45deg,var(--primary-color),var(--negative-color));border-radius:50%;opacity:0.4;top:15%;left:75%;animation:floatDashboard3 14s ease-in-out infinite 1s;box-shadow:0 0 6px rgba(239,68,68,0.2);"></div>
           <div class="particle" style="position:absolute;width:4px;height:4px;background:linear-gradient(45deg,var(--accent-color),var(--positive-color));border-radius:50%;opacity:0.7;top:60%;left:25%;animation:floatDashboard4 10s ease-in-out infinite 3s;box-shadow:0 0 8px rgba(102,126,234,0.4);"></div>
           <div class="particle" style="position:absolute;width:7px;height:7px;background:linear-gradient(45deg,var(--negative-color),var(--primary-color));border-radius:50%;opacity:0.3;top:40%;left:60%;animation:floatDashboard5 18s ease-in-out infinite 0.5s;box-shadow:0 0 12px rgba(239,68,68,0.3);"></div>
       </div>
       
       <!-- Hero Glow Effect -->
       <div class="hero-glow" style="position:absolute;top:50%;left:50%;width:800px;height:400px;background:radial-gradient(ellipse,rgba(102,126,234,0.08) 0%,transparent 70%);transform:translate(-50%,-50%);animation:heroGlowPulse 8s ease-in-out infinite;pointer-events:none;"></div>
       
       <div class="header-content" style="max-width:1400px;margin:0 auto;padding:0 2rem;position:relative;z-index:3;">
           <div class="header-left" style="animation:slideInLeft 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
               <div class="title-container" style="position:relative;margin-bottom:16px;">
                   <h1 class="magical-dashboard-title" style="font-size:2.8rem;font-weight:800;margin:0;color:var(--primary-color);position:relative;display:inline-block;background:linear-gradient(135deg,var(--primary-color),var(--accent-color),var(--positive-color));background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                       💰 Quản lý Thu Chi
                       <span class="mega-title-glow" style="position:absolute;top:-10px;left:-10px;right:-10px;bottom:-10px;background:linear-gradient(45deg,transparent,rgba(102,126,234,0.15),transparent,rgba(34,197,94,0.1),transparent);animation:megaTitleGlow 4s ease-in-out infinite;z-index:-1;border-radius:12px;"></span>
                   </h1>
                   <div class="title-decoration" style="position:absolute;top:-5px;right:-20px;width:20px;height:20px;background:linear-gradient(45deg,var(--accent-color),var(--positive-color));border-radius:50%;animation:decorationSpin 6s linear infinite;box-shadow:0 0 15px rgba(102,126,234,0.4);"></div>
               </div>
               <p class="magical-subtitle" style="font-size:1.1rem;color:var(--secondary-color);margin:0 0 16px;animation:fadeInUp 1.2s ease-out 0.3s both;font-weight:500;">Kiểm soát hoàn toàn tài chính cá nhân của bạn với giao diện thông minh</p>
               <div class="user-info-enhanced" style="display:flex;align-items:center;gap:20px;font-size:0.95rem;color:var(--secondary-color);animation:fadeInUp 1.2s ease-out 0.6s both;">
                   <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(102,126,234,0.05);border:1px solid rgba(102,126,234,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(102,126,234,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)'" onmouseout="this.style.background='rgba(102,126,234,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                       <i class="fas fa-user-circle" style="color:var(--accent-color);animation:userPulse 3s ease-in-out infinite;"></i> 
                       <span style="font-weight:600;color:var(--primary-color);">Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                   </div>
                   <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(34,197,94,0.05);border:1px solid rgba(34,197,94,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(34,197,94,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(34,197,94,0.15)'" onmouseout="this.style.background='rgba(34,197,94,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                       <i class="fas fa-calendar-check" style="color:var(--positive-color);animation:calendarPulse 3s ease-in-out infinite 0.5s;"></i> 
                       <span style="font-weight:500;"><?php echo date('d/m/Y'); ?></span>
                   </div>
                   <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(239,68,68,0.15)'" onmouseout="this.style.background='rgba(239,68,68,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                       <i class="fas fa-clock" style="color:var(--negative-color);animation:clockTick 2s ease-in-out infinite;"></i> 
                       <span style="font-weight:500;"><?php echo date('H:i'); ?></span>
                   </div>
               </div>
           </div>
           <div class="header-right" style="display:flex;gap:16px;align-items:center;animation:slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
               <button id="darkModeToggle" class="btn-icon" title="Chuyển đổi chế độ">
                   <i class="fas fa-moon"></i>
               </button>
               <!-- <button id="addTransactionBtn" class="magical-header-button primary" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:16px;padding:14px 24px;font-weight:700;font-size:1rem;transition:all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;display:flex;align-items:center;gap:10px;" onmouseover="this.style.background='linear-gradient(135deg,var(--primary-color),var(--positive-color))';this.style.transform='translateY(-4px) scale(1.05)';this.style.boxShadow='0 12px 40px rgba(102,126,234,0.5)'" onmouseout="this.style.background='linear-gradient(135deg,var(--accent-color),var(--primary-color))';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                   <i class="fas fa-plus" style="font-size:1.1rem;animation:addIconBounce 3s ease-in-out infinite;"></i>
                   <span style="position:relative;z-index:2;">Thêm giao dịch</span>
                   <span class="button-shine-mega" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:buttonShineMega 4s ease-in-out infinite;"></span>
               </button> -->
           </div>
       </div>
   </header>

   <main class="main-content" style="padding:2rem;max-width:1400px;margin:0 auto;">
       <?php echo displayFlashMessages(); ?>
       
       <!-- Enhanced Animated Finance Cards -->
       <div class="finance-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:32px;">
           <!-- Balance Card -->
           <div class="finance-card magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:24px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
               <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(102,126,234,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
               <div class="card-header" style="position:relative;z-index:2;">
                   <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                       <div>
                           <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Số dư hiện tại</h3>
                           <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Tổng số dư tài chính</p>
                       </div>
                       <div class="card-icon" style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                           <i class="fas fa-wallet" style="color:var(--accent-color);font-size:1.2rem;"></i>
                       </div>
                   </div>
               </div>
               <div class="card-amount" style="font-size:2rem;font-weight:800;margin-top:12px;color:<?php echo $balance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite;">
                   <?php echo formatMoney($balance); ?>
               </div>
               <div class="card-trend" style="margin-top:12px;display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--secondary-color);position:relative;z-index:2;">
                   <i class="fas fa-<?php echo $balance >= 0 ? 'trending-up' : 'trending-down'; ?>" style="color:<?php echo $balance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;animation:iconBounce 2s ease-in-out infinite;"></i>
                   <span><?php echo $balance >= 0 ? 'Tài chính ổn định' : 'Cần cân bằng'; ?></span>
               </div>
           </div>

           <!-- Income Card -->
           <div class="finance-card magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:24px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out 0.1s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(34,197,94,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
               <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(34,197,94,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
               <div class="card-header" style="position:relative;z-index:2;">
                   <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                       <div>
                           <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tổng thu nhập</h3>
                           <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Tổng tiền đã nhận</p>
                       </div>
                       <div class="card-icon" style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(34,197,94,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite 0.5s;">
                           <i class="fas fa-arrow-up" style="color:var(--positive-color);font-size:1.2rem;"></i>
                       </div>
                   </div>
               </div>
               <div class="card-amount" style="font-size:2rem;font-weight:800;margin-top:12px;color:var(--positive-color);position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite 0.3s;">
                   <?php echo formatMoney($totalIncome); ?>
               </div>
               <div class="card-trend" style="margin-top:12px;display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--secondary-color);position:relative;z-index:2;">
                   <i class="fas fa-chart-line" style="color:var(--positive-color);animation:iconBounce 2s ease-in-out infinite 0.2s;"></i>
                   <span>Xu hướng tích cực</span>
               </div>
           </div>

           <!-- Expense Card -->
           <div class="finance-card magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:24px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out 0.2s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(239,68,68,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
               <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(239,68,68,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
               <div class="card-header" style="position:relative;z-index:2;">
                   <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                       <div>
                           <h3 style="margin:0 0 4px;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tổng chi tiêu</h3>
                           <p style="margin:0;font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Tổng tiền đã chi</p>
                       </div>
                       <div class="card-icon" style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite 1s;">
                           <i class="fas fa-arrow-down" style="color:var(--negative-color);font-size:1.2rem;"></i>
                       </div>
                   </div>
               </div>
               <div class="card-amount" style="font-size:2rem;font-weight:800;margin-top:12px;color:var(--negative-color);position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite 0.6s;">
                   <?php echo formatMoney($totalExpense); ?>
               </div>
               <div class="card-trend" style="margin-top:12px;display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--secondary-color);position:relative;z-index:2;">
                   <i class="fas fa-chart-bar" style="color:var(--negative-color);animation:iconBounce 2s ease-in-out infinite 0.4s;"></i>
                   <span>Theo dõi chi tiêu</span>
               </div>
           </div>
       </div>

       <!-- Enhanced Magical Tab Navigation -->
       <div class="content-tabs" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.12);position:relative;animation:tabsSlideUp 1s ease-out 0.4s both;">
           <div class="tabs-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(45deg,rgba(102,126,234,0.05),rgba(102,126,234,0.1),rgba(102,126,234,0.05));opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
           <nav class="tab-nav" style="background:linear-gradient(135deg,var(--hover-color),rgba(248,250,252,0.8));padding:8px;display:flex;gap:6px;position:relative;z-index:2;">
               <button class="tab-button active magical-tab" data-tab="transactions" style="flex:1;padding:16px 24px;border:none;background:var(--card-background);color:var(--primary-color);border-radius:12px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 16px rgba(0,0,0,0.15);position:relative;overflow:hidden;" onmouseover="this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.25)'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.15)'">
                   <span class="tab-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:tabShine 3s ease-in-out infinite;"></span>
                   <i class="fas fa-exchange-alt" style="margin-right:8px;animation:iconSpin 4s ease-in-out infinite;"></i> 
                   <span style="position:relative;z-index:2;">Giao dịch</span>
               </button>
               <button class="tab-button magical-tab" data-tab="analytics" style="flex:1;padding:16px 24px;border:none;background:transparent;color:var(--secondary-color);border-radius:12px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;" onmouseover="this.style.background='rgba(255,255,255,0.7)';this.style.color='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.background='transparent';this.style.color='var(--secondary-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                   <span class="tab-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(102,126,234,0.2);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                   <i class="fas fa-chart-line" style="margin-right:8px;animation:iconBounce 3s ease-in-out infinite;"></i> 
                   <span style="position:relative;z-index:2;">Phân tích</span>
               </button>
               <button class="tab-button magical-tab" data-tab="qa" style="flex:1;padding:16px 24px;border:none;background:transparent;color:var(--secondary-color);border-radius:12px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;" onmouseover="this.style.background='rgba(255,255,255,0.7)';this.style.color='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.background='transparent';this.style.color='var(--secondary-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                   <span class="tab-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(102,126,234,0.2);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                   <i class="fas fa-robot" style="margin-right:8px;animation:iconFloat 3s ease-in-out infinite;"></i> 
                   <span style="position:relative;z-index:2;">Trợ lý AI</span>
               </button>
           </nav>

           <div class="tab-content" style="padding:24px;">
               <!-- Simplified Transactions Tab -->
               <div id="transactions" class="tab-pane active" style="animation:fadeInUp 0.4s ease-out;">
                   <div class="section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                       <div>
                           <h2 style="margin:0;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Giao dịch gần đây</h2>
                           <p style="margin:4px 0 0;color:var(--secondary-color);font-size:0.9rem;">5 giao dịch mới nhất</p>
                       </div>
                       <a href="transactions.php" class="btn-link" style="background:var(--accent-color);color:white;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:500;font-size:0.9rem;transition:all 0.2s ease;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">
                           <i class="fas fa-external-link-alt"></i> Xem tất cả
                       </a>
                   </div>

                   <?php if (count($recentTransactions) > 0): ?>
                       <div class="transactions-table" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:8px;overflow:hidden;">
                           <table style="width:100%;border-collapse:collapse;">
                               <thead style="background:var(--hover-color);">
                                   <tr>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Ngày</th>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Mô tả</th>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Danh mục</th>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Loại</th>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Số tiền</th>
                                       <th style="padding:16px 20px;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Thao tác</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($recentTransactions as $index => $transaction): ?>
                                       <tr style="border-bottom:1px solid var(--border-color);transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                                           <td style="padding:16px 20px;border:none;color:var(--secondary-color);font-size:0.9rem;"><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                                           <td style="padding:16px 20px;border:none;color:var(--primary-color);font-weight:500;font-size:0.9rem;"><?php echo htmlspecialchars($transaction['description']); ?></td>
                                           <td style="padding:16px 20px;border:none;color:var(--secondary-color);font-size:0.9rem;"><?php echo htmlspecialchars($transaction['category'] ?? 'Chung'); ?></td>
                                           <td style="padding:16px 20px;border:none;">
                                               <span class="badge" style="padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:500;background:<?php echo $transaction['type'] === 'income' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'; ?>;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                                   <?php echo $transaction['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu'; ?>
                                               </span>
                                           </td>
                                           <td style="padding:16px 20px;border:none;font-weight:600;font-size:0.95rem;color:<?php echo $transaction['type'] === 'income' ? '#22c55e' : '#ef4444'; ?>;">
                                               <?php echo formatMoney($transaction['amount']); ?>
                                           </td>
                                           <td style="padding:16px 20px;border:none;">
                                               <div style="display:flex;gap:6px;">
                                                   <button class="btn-icon edit-transaction-btn" 
                                                       data-id="<?php echo $transaction['id']; ?>"
                                                       data-amount="<?php echo $transaction['amount']; ?>"
                                                       data-description="<?php echo htmlspecialchars($transaction['description']); ?>"
                                                       data-type="<?php echo $transaction['type']; ?>"
                                                       data-category="<?php echo htmlspecialchars($transaction['category'] ?? ''); ?>"
                                                       data-date="<?php echo $transaction['date']; ?>"
                                                       style="background:var(--accent-color);color:white;padding:8px 12px;border:none;border-radius:4px;transition:all 0.2s ease;font-size:0.8rem;cursor:pointer;position:relative;z-index:10;pointer-events:auto;min-width:32px;min-height:32px;" 
                                                       title="Chỉnh sửa" 
                                                       onmouseover="this.style.background='var(--primary-color)'" 
                                                       onmouseout="this.style.background='var(--accent-color)'"
                                                       onclick="handleEditTransaction(this)">
                                                       <i class="fas fa-edit"></i>
                                                   </button>
                                                   <a href="transactions.php?delete=<?php echo $transaction['id']; ?>" class="btn-icon" style="background:#ef4444;color:white;padding:6px;border-radius:4px;text-decoration:none;transition:all 0.2s ease;font-size:0.8rem;" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa giao dịch này?');" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                                       <i class="fas fa-trash"></i>
                                                   </a>
                                               </div>
                                           </td>
                                       </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                   <?php else: ?>
                       <div class="empty-state" style="text-align:center;padding:60px 40px;background:var(--card-background);border:1px solid var(--border-color);border-radius:8px;">
                           <div class="empty-icon" style="font-size:3rem;color:var(--secondary-color);margin-bottom:16px;opacity:0.6;">
                               <i class="fas fa-receipt"></i>
                           </div>
                           <h3 style="margin:0 0 8px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Chưa có giao dịch nào</h3>
                           <p style="margin:0 0 24px;color:var(--secondary-color);font-size:0.95rem;">Hãy thêm giao dịch đầu tiên để bắt đầu theo dõi tài chính.</p>
                           <button id="emptyAddTransactionBtn" class="btn-primary" style="background:var(--accent-color);color:white;border:none;padding:12px 24px;border-radius:6px;font-weight:600;transition:all 0.2s ease;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">
                               <i class="fas fa-plus"></i> Thêm giao dịch
                           </button>
                       </div>
                   <?php endif; ?>
               </div>

               <!-- Tab Phân tích -->
               <div id="analytics" class="tab-pane">
                   <div class="section-header">
                       <h2>Phân tích tài chính</h2>
                       <p class="section-subtitle">Tổng quan chi tiết về tình hình tài chính của bạn</p>
                   </div>
                   
                   <!-- Thống kê tổng quan -->
                   <div class="stats-overview" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:32px;">
                       <div class="stat-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                           <div style="display:flex;align-items:center;justify-content:space-between;">
                               <div>
                                   <h4 style="margin:0;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tổng giao dịch</h4>
                                   <p style="margin:8px 0 0;font-size:1.8rem;font-weight:700;color:var(--primary-color);"><?php echo getTotalTransactionCount($conn, $user_id); ?></p>
                               </div>
                               <i class="fas fa-exchange-alt" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                           </div>
                       </div>
                       <div class="stat-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                           <div style="display:flex;align-items:center;justify-content:space-between;">
                               <div>
                                   <h4 style="margin:0;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Chi tiêu trung bình/ngày</h4>
                                   <p style="margin:8px 0 0;font-size:1.8rem;font-weight:700;color:var(--primary-color);"><?php echo formatMoney($totalExpense / max(1, (time() - strtotime('-30 days')) / 86400)); ?></p>
                               </div>
                               <i class="fas fa-calendar-day" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                           </div>
                       </div>
                       <div class="stat-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                           <div style="display:flex;align-items:center;justify-content:space-between;">
                               <div>
                                   <h4 style="margin:0;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Tỷ lệ tiết kiệm</h4>
                                   <p style="margin:8px 0 0;font-size:1.8rem;font-weight:700;color:var(--primary-color);"><?php echo $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1) : 0; ?>%</p>
                               </div>
                               <i class="fas fa-piggy-bank" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                           </div>
                       </div>
                       <div class="stat-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                           <div style="display:flex;align-items:center;justify-content:space-between;">
                               <div>
                                   <h4 style="margin:0;font-size:0.9rem;color:var(--secondary-color);font-weight:500;">Danh mục chi nhiều nhất</h4>
                                   <p style="margin:8px 0 0;font-size:1.2rem;font-weight:700;color:var(--primary-color);">
                                       <?php 
                                       $topCategory = '';
                                       $maxAmount = 0;
                                       foreach ($expense_categories as $cat) {
                                           $amount = getFilteredTotal($conn, $user_id, 'expense', null, null, $cat['name']);
                                           if ($amount > $maxAmount) {
                                               $maxAmount = $amount;
                                               $topCategory = $cat['name'];
                                           }
                                       }
                                       echo $topCategory ?: 'Chưa có';
                                       ?>
                                   </p>
                               </div>
                               <i class="fas fa-chart-pie" style="font-size:2rem;color:var(--accent-color);opacity:0.6;"></i>
                           </div>
                       </div>
                   </div>

                   <!-- Biểu đồ chính -->
                   <div class="charts-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:24px;margin-bottom:24px;">
                       <!-- Biểu đồ tròn tỷ lệ thu/chi -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);text-align:center;">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Tỷ lệ Thu nhập/Chi tiêu</h3>
                           <canvas id="pieIncomeExpense" width="280" height="280"></canvas>
                           <div style="margin-top:16px;display:flex;justify-content:space-around;font-size:0.9rem;">
                               <div style="text-align:center;">
                                   <div style="color:var(--positive-color);font-weight:600;"><?php echo formatMoney($totalIncome); ?></div>
                                   <div style="color:var(--secondary-color);font-size:0.8rem;">Thu nhập</div>
                               </div>
                               <div style="text-align:center;">
                                   <div style="color:var(--negative-color);font-weight:600;"><?php echo formatMoney($totalExpense); ?></div>
                                   <div style="color:var(--secondary-color);font-size:0.8rem;">Chi tiêu</div>
                               </div>
                           </div>
                       </div>

                       <!-- Biểu đồ đường xu hướng 6 tháng -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Xu hướng Thu nhập & Chi tiêu (6 tháng)</h3>
                           <canvas id="lineIncomeExpense" width="450" height="280"></canvas>
                       </div>
                   </div>

                   <!-- Biểu đồ cột và thông tin bổ sung -->
                   <div class="secondary-charts" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px;margin-bottom:24px;">
                       <!-- Biểu đồ cột chi tiêu theo danh mục -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Chi tiêu theo danh mục</h3>
                           <canvas id="barExpenseCategory" width="400" height="280"></canvas>
                       </div>

                       <!-- Top 5 giao dịch lớn nhất -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Top 5 giao dịch lớn nhất</h3>
                           <div class="top-transactions">
                               <?php 
                               $topTransactions = getFilteredTransactions($conn, $user_id, 'all', null, null, 'all');
                               usort($topTransactions, function($a, $b) { return $b['amount'] - $a['amount']; });
                               $topTransactions = array_slice($topTransactions, 0, 5);
                               foreach ($topTransactions as $index => $trans): 
                               ?>
                               <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                                   <div style="display:flex;align-items:center;gap:12px;">
                                       <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $trans['type'] === 'income' ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:0.9rem;">
                                           <?php echo $index + 1; ?>
                                       </div>
                                       <div>
                                           <div style="font-weight:600;color:var(--primary-color);"><?php echo htmlspecialchars($trans['description']); ?></div>
                                           <div style="font-size:0.85rem;color:var(--secondary-color);"><?php echo date('d/m/Y', strtotime($trans['date'])); ?></div>
                                       </div>
                                   </div>
                                   <div style="font-weight:600;color:<?php echo $trans['type'] === 'income' ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;">
                                       <?php echo formatMoney($trans['amount']); ?>
                                   </div>
                               </div>
                               <?php endforeach; ?>
                           </div>
                       </div>
                   </div>

                   <!-- Mục tiêu tiết kiệm và lời khuyên -->
                   <div class="goals-advice" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px;">
                       <!-- Mục tiêu tiết kiệm -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Mục tiêu tiết kiệm tháng này</h3>
                           <?php 
                           $currentMonth = date('n');
                           $currentYear = date('Y');
                           $monthlyIncome = getTotalIncome($conn, $user_id, $currentMonth, $currentYear);
                           $monthlyExpense = getTotalExpense($conn, $user_id, $currentMonth, $currentYear);
                           $savingsGoal = $monthlyIncome * 0.2; // Mục tiêu tiết kiệm 20%
                           $actualSavings = $monthlyIncome - $monthlyExpense;
                           $savingsProgress = $savingsGoal > 0 ? min(($actualSavings / $savingsGoal) * 100, 100) : 0;
                           ?>
                           <div style="margin-bottom:16px;">
                               <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:var(--primary-color);">
                                   <span>Tiến độ: <?php echo round($savingsProgress, 1); ?>%</span>
                                   <span><?php echo formatMoney($actualSavings); ?> / <?php echo formatMoney($savingsGoal); ?></span>
                               </div>
                               <div style="background:var(--hover-color);border-radius:8px;height:10px;overflow:hidden;">
                                   <div style="background:var(--accent-color);height:100%;width:<?php echo $savingsProgress; ?>%;border-radius:8px;transition:width 0.3s ease;"></div>
                               </div>
                           </div>
                           <div style="font-size:0.9rem;color:var(--secondary-color);">
                               <?php if ($savingsProgress >= 100): ?>
                                   🎉 Chúc mừng! Bạn đã đạt mục tiêu tiết kiệm tháng này!
                               <?php elseif ($savingsProgress >= 50): ?>
                                   💪 Bạn đang làm rất tốt! Tiếp tục duy trì nhé!
                               <?php else: ?>
                                   📈 Hãy cố gắng tiết kiệm thêm để đạt mục tiêu 20% thu nhập!
                               <?php endif; ?>
                           </div>
                       </div>

                       <!-- Lời khuyên tài chính thông minh -->
                       <div class="chart-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:24px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                           <h3 style="margin-bottom:20px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">Lời khuyên tài chính</h3>
                           <div class="advice-list">
                               <?php 
                               $advices = [];
                               if ($ratio > 80) {
                                   $advices[] = ['icon' => 'exclamation-triangle', 'color' => '#ef4444', 'text' => 'Chi tiêu của bạn đang cao! Hãy xem xét cắt giảm các khoản chi không cần thiết.'];
                               } elseif ($ratio > 60) {
                                   $advices[] = ['icon' => 'info-circle', 'color' => '#f59e0b', 'text' => 'Tỷ lệ chi tiêu ổn định. Hãy cố gắng tiết kiệm thêm 10-20% thu nhập.'];
                               } else {
                                   $advices[] = ['icon' => 'check-circle', 'color' => '#22c55e', 'text' => 'Tuyệt vời! Bạn đang quản lý tài chính rất tốt.'];
                               }
                               
                               if ($totalIncome > 0) {
                                   $advices[] = ['icon' => 'lightbulb', 'color' => '#3b82f6', 'text' => 'Hãy đầu tư 10-15% thu nhập vào các kênh sinh lời để tăng tài sản.'];
                               }
                               
                               $advices[] = ['icon' => 'shield-alt', 'color' => '#8b5cf6', 'text' => 'Tạo quỹ khẩn cấp bằng 3-6 tháng chi tiêu để đảm bảo an toàn tài chính.'];
                               
                               foreach ($advices as $advice):
                               ?>
                               <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;padding:12px;background:var(--hover-color);border-radius:8px;">
                                   <i class="fas fa-<?php echo $advice['icon']; ?>" style="color:<?php echo $advice['color']; ?>;font-size:1.1rem;margin-top:2px;"></i>
                                   <p style="margin:0;color:var(--text-primary);line-height:1.4;font-size:0.9rem;"><?php echo $advice['text']; ?></p>
                               </div>
                               <?php endforeach; ?>
                           </div>
                       </div>
                   </div>

                   <!-- Chart.js Scripts -->
                   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                   <script>
                   // Biểu đồ tròn tỷ lệ thu/chi
                   new Chart(document.getElementById('pieIncomeExpense').getContext('2d'), {
                       type: 'doughnut',
                       data: {
                           labels: ['Thu nhập', 'Chi tiêu'],
                           datasets: [{
                               data: [<?php echo $totalIncome; ?>, <?php echo $totalExpense; ?>],
                               backgroundColor: ['#22c55e', '#ef4444'],
                               borderWidth: 3,
                               borderColor: '#fff',
                               hoverOffset: 20
                           }]
                       },
                       options: {
                           plugins: { 
                               legend: { 
                                   position: 'bottom', 
                                   labels: { 
                                       font: {size: 16, weight: '600'}, 
                                       padding: 20,
                                       usePointStyle: true,
                                       pointStyle: 'circle'
                                   } 
                               } 
                           },
                           cutout: '65%',
                           responsive: true,
                       }
                   });

                   // Biểu đồ đường xu hướng thu nhập & chi tiêu 6 tháng
                   new Chart(document.getElementById('lineIncomeExpense').getContext('2d'), {
                       type: 'line',
                       data: {
                           labels: [
                               <?php
                               $months = [];
                               $incomeData = [];
                               $expenseData = [];
                               for ($i = 5; $i >= 0; $i--) {
                                   $time = strtotime("-{$i} months");
                                   $m = date('n', $time);
                                   $y = date('Y', $time);
                                   $months[] = date('m/Y', $time);
                                   $incomeData[] = getTotalIncome($conn, $user_id, $m, $y);
                                   $expenseData[] = getTotalExpense($conn, $user_id, $m, $y);
                               }
                               echo "'" . implode("','", $months) . "'";
                               ?>
                           ],
                           datasets: [
                               {
                                   label: 'Thu nhập',
                                   data: [<?php echo implode(',', $incomeData); ?>],
                                   borderColor: '#22c55e',
                                   backgroundColor: 'rgba(34,197,94,0.1)',
                                   fill: true,
                                   tension: 0.4,
                                   pointRadius: 6,
                                   pointBackgroundColor: '#22c55e',
                                   pointBorderColor: '#fff',
                                   pointBorderWidth: 2,
                               },
                               {
                                   label: 'Chi tiêu',
                                   data: [<?php echo implode(',', $expenseData); ?>],
                                   borderColor: '#ef4444',
                                   backgroundColor: 'rgba(239,68,68,0.1)',
                                   fill: true,
                                   tension: 0.4,
                                   pointRadius: 6,
                                   pointBackgroundColor: '#ef4444',
                                   pointBorderColor: '#fff',
                                   pointBorderWidth: 2,
                               }
                           ]
                       },
                       options: {
                           plugins: { 
                               legend: { 
                                   position: 'top', 
                                   labels: { 
                                       font: {size: 16, weight: '600'}, 
                                       padding: 20,
                                       usePointStyle: true 
                                   } 
                               } 
                           },
                           responsive: true,
                           scales: { 
                               y: { 
                                   beginAtZero: true,
                                   grid: { color: 'rgba(0,0,0,0.05)' },
                                   ticks: { font: {size: 14} }
                               },
                               x: {
                                   grid: { color: 'rgba(0,0,0,0.05)' },
                                   ticks: { font: {size: 14} }
                               }
                           }
                       }
                   });

                   // Biểu đồ cột chi tiêu theo danh mục
                   new Chart(document.getElementById('barExpenseCategory').getContext('2d'), {
                       type: 'bar',
                       data: {
                           labels: [
                               <?php
                               $catLabels = [];
                               $catValues = [];
                               $catColors = ['#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b', '#10b981', '#f97316', '#ec4899', '#6366f1', '#84cc16'];
                               foreach ($expense_categories as $index => $cat) {
                                   $catLabels[] = $cat['name'];
                                   $catValues[] = getFilteredTotal($conn, $user_id, 'expense', null, null, $cat['name']);
                               }
                               echo "'" . implode("','", $catLabels) . "'";
                               ?>
                           ],
                           datasets: [{
                               label: 'Chi tiêu',
                               data: [<?php echo implode(',', $catValues); ?>],
                               backgroundColor: [<?php 
                                   $colors = [];
                                   for ($i = 0; $i < count($catLabels); $i++) {
                                       $colors[] = "'" . $catColors[$i % count($catColors)] . "'";
                                   }
                                   echo implode(',', $colors);
                               ?>],
                               borderRadius: 8,
                               maxBarThickness: 50,
                           }]
                       },
                       options: {
                           plugins: { legend: { display: false } },
                           responsive: true,
                           scales: {
                               y: { 
                                   beginAtZero: true,
                                   grid: { color: 'rgba(0,0,0,0.05)' },
                                   ticks: { font: {size: 14} }
                               },
                               x: { 
                                   grid: { display: false },
                                   ticks: { font: { size: 14 } } 
                               }
                           }
                       }
                   });
                   </script>
               </div>

                                             <!-- Tab Q&A -->
               <div id="qa" class="tab-pane">
                   <div class="section-header">
                       <h2>🤖 Trợ lý tài chính AI</h2>
                       <p class="section-subtitle">Nhận lời khuyên thông minh và phân tích tài chính cá nhân</p>
                   </div>
                   
                   <div class="qa-container" style="display:grid;grid-template-columns:1fr 2fr;gap:24px;height:580px;">
                       <div class="common-questions" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:8px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                           <h3 style="margin:0 0 6px;color:var(--primary-color);font-size:1rem;font-weight:600;display:flex;align-items:center;gap:6px;">
                               <i class="fas fa-users" style="color:var(--accent-color);font-size:0.9rem;"></i>
                               Vai trò
                           </h3>
                           <p style="margin:0 0 12px;color:var(--secondary-color);font-size:0.8rem;">Mỗi vai trò có phong cách trả lời khác nhau</p>
           
                           <div class="role-descriptions">
                               <div class="role-description" style="margin-bottom:8px;padding:10px;background:var(--hover-color);border:1px solid var(--border-color);border-radius:6px;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='rgba(102,126,234,0.1)';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.borderColor='var(--border-color)'">
                                   <h4 style="margin:0 0 4px;color:var(--primary-color);font-size:0.85rem;font-weight:600;">🤖 Trợ lý thông minh</h4>
                                   <p style="margin:0;color:var(--secondary-color);font-size:0.75rem;line-height:1.3;">Trả lời chuyên nghiệp, đưa ra lời khuyên tài chính hữu ích</p>
                               </div>
                               
                               <div class="role-description" style="margin-bottom:8px;padding:10px;background:var(--hover-color);border:1px solid var(--border-color);border-radius:6px;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='rgba(245,158,11,0.1)';this.style.borderColor='#f59e0b'" onmouseout="this.style.background='var(--hover-color)';this.style.borderColor='var(--border-color)'">
                                   <h4 style="margin:0 0 4px;color:var(--primary-color);font-size:0.85rem;font-weight:600;">👩‍💼 Mama nóng tính</h4>
                                   <p style="margin:0;color:var(--secondary-color);font-size:0.75rem;line-height:1.3;">Phản ứng như một người mẹ lo lắng về chi tiêu của bạn</p>
                               </div>
                               
                               <div class="role-description" style="margin-bottom:12px;padding:10px;background:var(--hover-color);border:1px solid var(--border-color);border-radius:6px;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='rgba(34,197,94,0.1)';this.style.borderColor='var(--positive-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.borderColor='var(--border-color)'">
                                   <h4 style="margin:0 0 4px;color:var(--primary-color);font-size:0.85rem;font-weight:600;">😎 Homie</h4>
                                   <p style="margin:0;color:var(--secondary-color);font-size:0.75rem;line-height:1.3;">Trò chuyện như một người bạn thân, thân thiện và cởi mở</p>
                               </div>
                           </div>
                           
                           <div style="border-top:1px solid var(--border-color);padding-top:12px;">
                               <h4 style="margin:0 0 8px;color:var(--primary-color);font-size:0.85rem;font-weight:600;">💡 Gợi ý câu hỏi:</h4>
                               <div style="display:flex;flex-direction:column;gap:6px;">
                                   <button class="suggestion-btn" style="background:var(--hover-color);border:1px solid var(--border-color);border-radius:4px;padding:6px 8px;text-align:left;color:var(--secondary-color);font-size:0.75rem;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='var(--accent-color)';this.style.color='white';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.color='var(--secondary-color)';this.style.borderColor='var(--border-color)'">
                                       💰 Phân tích chi tiêu tháng này
                                   </button>
                                   <button class="suggestion-btn" style="background:var(--hover-color);border:1px solid var(--border-color);border-radius:4px;padding:6px 8px;text-align:left;color:var(--secondary-color);font-size:0.75rem;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='var(--accent-color)';this.style.color='white';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.color='var(--secondary-color)';this.style.borderColor='var(--border-color)'">
                                       📊 Lời khuyên tiết kiệm
                                   </button>
                                   <button class="suggestion-btn" style="background:var(--hover-color);border:1px solid var(--border-color);border-radius:4px;padding:6px 8px;text-align:left;color:var(--secondary-color);font-size:0.75rem;transition:all 0.2s ease;cursor:pointer;" onmouseover="this.style.background='var(--accent-color)';this.style.color='white';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.color='var(--secondary-color)';this.style.borderColor='var(--border-color)'">
                                       🎯 Đặt mục tiêu tài chính
                                   </button>
                               </div>
                           </div>
                       </div>
                       
                       <div class="qa-main" style="display:flex;flex-direction:column;height:100%;">
                           <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;">
                               <div>
                                   <h2 style="margin:0 0 4px;color:var(--primary-color);font-size:1.2rem;font-weight:600;">💬 Trợ lý tài chính AI</h2>
                                   <p style="margin:0;color:var(--secondary-color);font-size:0.8rem;">Nhận lời khuyên và phân tích tài chính cá nhân</p>
                               </div>
                               <div class="role-selector" style="max-width:200px;">
                                   <select id="chatRoleSelect" class="role-select" style="width:100%;padding:6px 10px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.85rem;transition:border-color 0.2s ease;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
                                       <option value="Trợ lý thông minh">🤖 Trợ lý thông minh</option>
                                       <option value="Mama nóng tính">👩‍💼 Mama nóng tính</option>
                                       <option value="Homie">😎 Homie</option>
                                   </select>
                               </div>
                           </div>
                           
                           <div class="qa-chat" style="flex:1;display:flex;flex-direction:column;background:var(--card-background);border:1px solid var(--border-color);border-radius:8px;overflow:hidden;height:480px;">
                               <div class="qa-messages" id="qaMessages" style="flex:1;padding:16px;overflow-y:auto;background:var(--hover-color);max-height:400px;min-height:400px;">
                                   <div class="message bot" style="display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
                                       <div class="message-avatar" style="width:32px;height:32px;border-radius:50%;background:var(--accent-color);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                           <i class="fas fa-robot" style="color:white;font-size:0.8rem;"></i>
                                       </div>
                                       <div style="display:flex;flex-direction:column;gap:6px;max-width:80%;">
                                           <div class="message-content" style="background:var(--card-background);padding:12px 16px;border-radius:8px;border-top-left-radius:3px;color:var(--primary-color);line-height:1.4;font-size:0.9rem;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid var(--border-color);">
                                               Xin chào! 👋 Tôi là trợ lý tài chính AI của bạn. Tôi có thể giúp bạn:
                                               <br><br>
                                               • 📊 Phân tích chi tiêu và xu hướng<br>
                                               • 💡 Đưa ra lời khuyên tài chính<br>
                                               • 📸 Phân tích hóa đơn từ ảnh<br>
                                               • 🎯 Lập kế hoạch tiết kiệm
                                               <br><br>
                                               <strong>Bạn muốn tôi hỗ trợ gì hôm nay?</strong>
                                           </div>
                                           <div style="display:flex;gap:6px;align-items:center;">
                                               <button class="speak-button" title="Đọc tin nhắn" style="background:var(--hover-color);border:1px solid var(--border-color);border-radius:6px;padding:6px 8px;cursor:pointer;fontSize:0.8rem;color:var(--secondary-color);transition:all 0.2s ease;display:flex;align-items:center;gap:4px;" onmouseover="this.style.background='var(--accent-color)';this.style.color='white';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.color='var(--secondary-color)';this.style.borderColor='var(--border-color)'" onclick="speakWelcomeMessage(this)">
                                                   <i class="fas fa-volume-up"></i>
                                               </button>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                               
                               <div class="qa-input" style="padding:12px 16px;border-top:1px solid var(--border-color);background:var(--card-background);display:flex;gap:10px;align-items:center;flex-shrink:0;height:60px;">
                                   <div style="flex:1;position:relative;">
                                       <input type="text" id="questionInput" placeholder="Nhập câu hỏi của bạn..." style="width:100%;padding:10px 35px 10px 12px;border:1px solid var(--border-color);border-radius:16px;background:var(--hover-color);color:var(--primary-color);font-size:0.9rem;transition:all 0.2s ease;outline:none;" onfocus="this.style.borderColor='var(--accent-color)';this.style.background='var(--card-background)'" onblur="this.style.borderColor='var(--border-color)';this.style.background='var(--hover-color)'" />
                                       <button type="button" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--secondary-color);padding:4px;cursor:pointer;border-radius:50%;transition:all 0.2s ease;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='none'">
                                           <i class="fas fa-paperclip" style="font-size:0.75rem;"></i>
                                       </button>
                                   </div>
                                   <button id="sendQuestion" class="btn-primary" style="background:var(--accent-color);color:white;border:none;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;flex-shrink:0;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">
                                       <i class="fas fa-paper-plane" style="font-size:0.8rem;"></i>
                                   </button>
                               </div>
                               <input type="hidden" id="user-id" value="<?php echo $_SESSION['user_id']; ?>">
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </main>
</div>

<!-- Enhanced Modals -->
<!-- Modal thêm giao dịch -->
<div id="addTransactionModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;backdrop-filter:blur(4px);">
   <div class="modal-content" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card-background);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.1);max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
       <div class="modal-header" style="padding:20px 24px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
           <h2 style="margin:0;color:var(--primary-color);font-size:1.3rem;font-weight:700;">Thêm giao dịch mới</h2>
           <button class="close-modal" style="background:none;border:none;font-size:1.5rem;color:var(--secondary-color);cursor:pointer;padding:4px;border-radius:4px;transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='none'">&times;</button>
       </div>
       <div class="modal-body" style="padding:24px;">
           <form id="addTransactionForm" method="POST" action="transactions.php">
               <input type="hidden" name="action" value="add">
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="amount" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Số tiền:</label>
                   <input type="number" id="amount" name="amount" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.2s ease;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="description" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Mô tả:</label>
                   <input type="text" id="description" name="description" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.2s ease;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="type" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Loại:</label>
                   <select id="type" name="type" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
                       <option value="income">Thu nhập</option>
                       <option value="expense">Chi tiêu</option>
                   </select>
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="category" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Danh mục:</label>
                   <select id="category" name="category" style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
                       <option value="">Chọn danh mục</option>
                       <optgroup label="Thu nhập" id="income-categories">
                           <?php foreach ($income_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                       <optgroup label="Chi tiêu" id="expense-categories" style="display:none;">
                           <?php foreach ($expense_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                   </select>
                   <p class="form-hint" style="margin:6px 0 0;font-size:0.8rem;color:var(--secondary-color);">
                       <a href="categories.php" style="color:var(--accent-color);text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Quản lý danh mục</a>
                   </p>
               </div>
               
               <div class="form-group" style="margin-bottom:24px;">
                   <label for="date" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Ngày:</label>
                   <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
               </div>
               
               <div class="form-actions" style="display:flex;gap:12px;justify-content:flex-end;">
                   <button type="button" class="btn-secondary cancel-modal" style="background:var(--hover-color);color:var(--primary-color);border:1px solid var(--border-color);border-radius:6px;padding:10px 20px;font-weight:500;transition:all 0.2s ease;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--hover-color)'">Hủy</button>
                   <button type="submit" class="btn-primary" style="background:var(--accent-color);color:white;border:none;border-radius:6px;padding:10px 20px;font-weight:500;transition:all 0.2s ease;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">Lưu giao dịch</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Modal sửa giao dịch đơn -->
<div id="editTransactionModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;backdrop-filter:blur(4px);">
   <div class="modal-content" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card-background);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.1);max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
       <div class="modal-header" style="padding:20px 24px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
           <h2 style="margin:0;color:var(--primary-color);font-size:1.3rem;font-weight:700;">Chỉnh sửa giao dịch</h2>
           <button class="close-modal" style="background:none;border:none;font-size:1.5rem;color:var(--secondary-color);cursor:pointer;padding:4px;border-radius:4px;transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='none'">&times;</button>
       </div>
       <div class="modal-body" style="padding:24px;">
           <form id="editTransactionForm" method="POST" action="transactions.php">
               <input type="hidden" name="action" value="update">
               <input type="hidden" id="edit-transaction-id" name="transaction_id">
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="edit-amount" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Số tiền:</label>
                   <input type="number" id="edit-amount" name="amount" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.2s ease;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="edit-description" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Mô tả:</label>
                   <input type="text" id="edit-description" name="description" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;transition:all 0.2s ease;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="edit-type" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Loại:</label>
                   <select id="edit-type" name="type" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
                       <option value="income">Thu nhập</option>
                       <option value="expense">Chi tiêu</option>
                   </select>
               </div>
               
               <div class="form-group" style="margin-bottom:20px;">
                   <label for="edit-category" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Danh mục:</label>
                   <select id="edit-category" name="category" style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
                       <option value="">Chọn danh mục</option>
                       <optgroup label="Thu nhập" id="edit-income-categories">
                           <?php foreach ($income_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                       <optgroup label="Chi tiêu" id="edit-expense-categories" style="display:none;">
                           <?php foreach ($expense_categories as $category): ?>
                               <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                           <?php endforeach; ?>
                       </optgroup>
                   </select>
                   <p class="form-hint" style="margin:6px 0 0;font-size:0.8rem;color:var(--secondary-color);">
                       <a href="categories.php" style="color:var(--accent-color);text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Quản lý danh mục</a>
                   </p>
               </div>
               
               <div class="form-group" style="margin-bottom:24px;">
                   <label for="edit-date" style="display:block;margin-bottom:6px;color:var(--primary-color);font-weight:500;font-size:0.9rem;">Ngày:</label>
                   <input type="date" id="edit-date" name="date" required style="width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--card-background);color:var(--primary-color);font-size:0.9rem;">
               </div>
               
               <div class="form-actions" style="display:flex;gap:12px;justify-content:flex-end;">
                   <button type="button" class="btn-secondary cancel-modal" style="background:var(--hover-color);color:var(--primary-color);border:1px solid var(--border-color);border-radius:6px;padding:10px 20px;font-weight:500;transition:all 0.2s ease;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--hover-color)'">Hủy</button>
                   <button type="submit" class="btn-primary" style="background:var(--accent-color);color:white;border:none;border-radius:6px;padding:10px 20px;font-weight:500;transition:all 0.2s ease;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">Cập nhật giao dịch</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Modal sửa nhiều giao dịch từ AI Chat - Compact -->
<div id="editMultipleTransactionsModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;backdrop-filter:blur(4px);">
   <div class="modal-content" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card-background);border-radius:8px;box-shadow:0 8px 32px rgba(0,0,0,0.1);max-width:600px;width:90%;max-height:85vh;overflow-y:auto;">
       <div class="modal-header" style="padding:16px 20px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
           <h2 style="margin:0;color:var(--primary-color);font-size:1.1rem;font-weight:600;">
               Chỉnh sửa giao dịch
           </h2>
           <button class="close-modal" style="background:none;border:none;font-size:1.3rem;color:var(--secondary-color);cursor:pointer;padding:4px;border-radius:4px;transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='none'">&times;</button>
       </div>
       <div class="modal-body" style="padding:16px 20px;">
           <div id="transactions-list" style="max-height:350px;overflow-y:auto;">
               <!-- Transactions will be populated here -->
           </div>
           
           <div class="form-actions" style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:12px;border-top:1px solid var(--border-color);">
               <button type="button" class="btn-secondary cancel-modal" style="background:none;color:var(--secondary-color);border:1px solid var(--border-color);border-radius:4px;padding:8px 16px;font-size:0.9rem;transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='none'">
                   Hủy
               </button>
               <button id="saveAllTransactionsBtn" type="button" class="btn-primary" style="background:var(--accent-color);color:white;border:none;border-radius:4px;padding:8px 16px;font-size:0.9rem;transition:all 0.2s ease;" onmouseover="this.style.background='var(--primary-color)'" onmouseout="this.style.background='var(--accent-color)'">
                   <i class="fas fa-save"></i> Lưu tất cả
               </button>
           </div>
       </div>
   </div>
</div>

<!-- Enhanced Chat Messages Styling -->
<style>
/* Chat Message Styles */
.message.user {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    flex-direction: row-reverse;
}

.message.user .message-content {
    background: #4299e1;
    color: white;
    padding: 14px 18px;
    border-radius: 12px;
    border-top-right-radius: 4px;
    max-width: 80%;
    line-height: 1.5;
    font-size: 0.95rem;
    border: 1px solid #4299e1;
}

.message.user .message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #2d3748;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: white;
    font-size: 0.9rem;
}

.message.bot .message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #4299e1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: white;
    font-size: 0.9rem;
}

.message.bot .message-content {
    background: white;
    color: #2d3748;
    padding: 14px 18px;
    border-radius: 12px;
    border-top-left-radius: 4px;
    max-width: 80%;
    line-height: 1.5;
    font-size: 0.95rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
}

/* Suggestion buttons functionality */
.suggestion-btn {
    cursor: pointer;
    transition: all 0.2s ease;
}

.suggestion-btn:hover {
    background: #4299e1 !important;
    color: white !important;
    border-color: #4299e1 !important;
}

/* Tab switching */
.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Enhanced Tab Button Styling */
.tab-button {
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-button:not(.active):hover {
    background: white !important;
    color: #4299e1 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
}

.tab-button.active {
    background: white !important;
    color: #2d3748 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
}

/* Modal Force Display */
.modal-open {
    display: block !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 9999 !important;
    background: rgba(0,0,0,0.5) !important;
    backdrop-filter: blur(4px) !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Debug styles for modal */
#editTransactionModal {
    pointer-events: auto !important;
}

#editTransactionModal.modal-open .modal-content {
    pointer-events: auto !important;
    transform: translate(-50%, -50%) !important;
    position: relative !important;
}

/* Button debug styles */
.edit-transaction-btn {
    pointer-events: auto !important;
    cursor: pointer !important;
    z-index: 10 !important;
}

/* Responsive Chat Design */
@media (max-width: 1200px) {
    .qa-container {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
        height: auto !important;
    }
    
    .common-questions {
        order: 2;
    }
    
    .qa-main {
        order: 1;
        width: 100% !important;
    }
    
    .qa-chat {
        height: 500px !important;
        width: 100% !important;
    }
    
    .qa-messages {
        max-height: 420px !important;
        min-height: 420px !important;
    }
}

@media (max-width: 768px) {
    .qa-container {
        height: auto !important;
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    .qa-main {
        order: 1 !important;
    }
    
    .ai-sidebar {
        order: 2 !important;
        margin-top: 0 !important;
        padding: 16px !important;
    }
    
    .qa-chat {
        height: 450px !important;
    }
    
    .qa-messages {
        padding: 16px !important;
        max-height: 370px !important;
        min-height: 370px !important;
    }
    
    .qa-input {
        padding: 12px 16px !important;
        height: 60px !important;
        gap: 10px !important;
    }
    
    .message.user .message-content,
    .message.bot .message-content {
        max-width: 85% !important;
        font-size: 0.85rem !important;
        padding: 12px 16px !important;
    }
    
    .message.user .message-avatar,
    .message.bot .message-avatar {
        width: 32px !important;
        height: 32px !important;
        font-size: 0.8rem !important;
    }
    
    .role-selector {
        max-width: 100% !important;
    }
    
    .ai-sidebar {
        padding: 16px !important;
    }
    
    .magical-role-card {
        padding: 12px !important;
        margin-bottom: 10px !important;
    }
    
    .suggestions-section {
        padding-top: 12px !important;
    }
    
    .magical-suggestion {
        padding: 8px 10px !important;
        font-size: 0.75rem !important;
    }
    
    .ai-header {
        padding: 16px !important;
        margin-bottom: 12px !important;
    }
    
    .sidebar-header {
        margin-bottom: 16px !important;
    }
    
    .sidebar-icon {
        width: 40px !important;
        height: 40px !important;
        margin: 0 auto 10px !important;
    }
    
    /* Mobile Sidebar Controls */
    .sidebar-toggle {
        display: block !important;
    }
    
    .show-sidebar-btn {
        display: inline-flex !important;
        align-items: center;
        gap: 4px;
    }
    
    .ai-sidebar.hidden {
        display: none !important;
    }
}

@media (max-width: 480px) {
    .qa-container {
        height: auto !important;
    }
    
    .qa-chat {
        height: 320px !important;
    }
    
    .qa-messages {
        padding: 10px !important;
        max-height: 240px !important;
        min-height: 240px !important;
    }
    
    .qa-input {
        padding: 8px 10px !important;
        gap: 8px !important;
        height: 50px !important;
    }
    
    .message.user .message-content,
    .message.bot .message-content {
        font-size: 0.8rem !important;
        padding: 8px 12px !important;
    }
    
    .message.user .message-avatar,
    .message.bot .message-avatar {
        width: 24px !important;
        height: 24px !important;
        font-size: 0.7rem !important;
    }
    
    #sendQuestion {
        width: 32px !important;
        height: 32px !important;
    }
    
    #questionInput {
        padding: 8px 30px 8px 10px !important;
        font-size: 0.85rem !important;
    }
    
    .common-questions {
        padding: 10px !important;
    }
    
    .role-description {
        padding: 6px !important;
        margin-bottom: 4px !important;
    }
    
    .suggestion-btn {
        padding: 4px 6px !important;
        font-size: 0.7rem !important;
    }
}

/* Loading animation for messages */
.message-loading {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 0;
}

.message-loading span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #a0aec0;
    animation: bounce 1.4s ease-in-out infinite both;
}

.message-loading span:nth-child(1) { animation-delay: -0.32s; }
.message-loading span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0);
    } 40% {
        transform: scale(1);
    }
}

/* Scroll to bottom animation */
.qa-messages {
    scroll-behavior: smooth;
}

/* Enhanced focus states for chat input */
#questionInput:focus {
    border-color: #4299e1 !important;
    background: white !important;
}

/* Send button simple hover effect */
#sendQuestion:hover {
    background: #3182ce !important;
}

/* Speak button styling */
.speak-button {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 8px;
    cursor: pointer;
    font-size: 0.8rem;
    color: #4a5568;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.speak-button:hover {
    background: #4299e1;
    color: white;
    border-color: #4299e1;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(66, 153, 225, 0.3);
}

.speak-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(66, 153, 225, 0.2);
}

.speak-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

.speak-button .fas {
    transition: all 0.2s ease;
}

.speak-button:hover .fas {
    transform: scale(1.1);
}
</style>

<!-- Enhanced Magical JavaScript -->
<script>
// Hàm đọc tin nhắn chào mặc định với streaming API
function speakWelcomeMessage(buttonElement) {
    const welcomeText = "Xin chào! Tôi là trợ lý tài chính AI của bạn. Tôi có thể giúp bạn: Phân tích chi tiêu và xu hướng, Đưa ra lời khuyên tài chính, Phân tích hóa đơn từ ảnh, Lập kế hoạch tiết kiệm. Bạn muốn tôi hỗ trợ gì hôm nay?"
    const roleSelect = document.getElementById('chatRoleSelect')
    const selectedRole = roleSelect ? roleSelect.value : "Trợ lý thông minh"
    
    // Gọi hàm speakText từ main.js (streaming version)
    if (typeof speakText === 'function') {
        speakText(welcomeText, selectedRole, buttonElement)
    } else {
        console.error('speakText streaming function not found')
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Tab switching with magical effects
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const tabsContainer = document.querySelector('.content-tabs');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Add magical glow to tabs container
            if (tabsContainer) {
                const tabsGlow = tabsContainer.querySelector('.tabs-glow');
                if (tabsGlow) {
                    tabsGlow.style.opacity = '1';
                    setTimeout(() => {
                        tabsGlow.style.opacity = '0';
                    }, 800);
                }
            }
            
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = 'var(--secondary-color)';
                btn.style.boxShadow = 'none';
                btn.style.transform = 'translateY(0) scale(1)';
            });
            
            tabPanes.forEach(pane => {
                pane.classList.remove('active');
                pane.style.animation = 'none';
            });
            
            // Add active class to clicked button with magical effects
            this.classList.add('active');
            this.style.background = 'var(--card-background)';
            this.style.color = 'var(--primary-color)';
            this.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
            this.style.transform = 'translateY(-2px) scale(1.02)';
            
            // Activate target pane with animation
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.add('active');
                targetPane.style.animation = 'fadeInUp 0.5s ease-out';
            }
            
            // Trigger ripple effect
            const ripple = this.querySelector('.tab-ripple');
            if (ripple) {
                ripple.style.width = '80px';
                ripple.style.height = '80px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 600);
            }
        });
        
        // Enhanced hover effects for tabs
        button.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.background = 'rgba(255,255,255,0.7)';
                this.style.color = 'var(--primary-color)';
                this.style.transform = 'translateY(-2px) scale(1.02)';
                this.style.boxShadow = '0 4px 16px rgba(0,0,0,0.1)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.background = 'transparent';
                this.style.color = 'var(--secondary-color)';
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = 'none';
            }
        });
    });
    
    // Enhanced button interactions
    const magicButtons = document.querySelectorAll('.magic-button');
    magicButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = this.querySelector('.button-ripple');
            if (ripple) {
                ripple.style.width = '100px';
                ripple.style.height = '100px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 600);
            }
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Magical card interactions
    const magicalCards = document.querySelectorAll('.magical-card');
    magicalCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // Trigger glow effect
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '1';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '0';
            }
        });
    });
    
    // Staggered animation for cards on load
    const financeCards = document.querySelectorAll('.finance-card');
    financeCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Enhanced floating action button
    const floatingBtn = document.getElementById('floatingActionBtn');
    if (floatingBtn) {
        floatingBtn.addEventListener('mouseenter', function() {
            this.style.animation = 'none';
            this.style.transform = 'scale(1.15) translateY(-5px)';
            this.style.boxShadow = '0 15px 50px rgba(102,126,234,0.8)';
        });
        
        floatingBtn.addEventListener('mouseleave', function() {
            this.style.animation = 'floatingPulse 3s ease-in-out infinite';
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        floatingBtn.addEventListener('click', function() {
            // Add magical click effect
            this.style.transform = 'scale(1.3) translateY(-10px)';
            setTimeout(() => {
                this.style.transform = 'scale(1.15) translateY(-5px)';
            }, 200);
        });
    }
    
    // Enhanced AI Chat Interactions
    const sendBtn = document.getElementById('sendQuestion');
    const questionInput = document.getElementById('questionInput');
    const qaMessages = document.getElementById('qaMessages');
    
    if (sendBtn) {
        sendBtn.addEventListener('click', function(e) {
            // Trigger ripple effect
            const ripple = this.querySelector('.btn-ripple');
            if (ripple) {
                ripple.style.width = '60px';
                ripple.style.height = '60px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 600);
            }
            
            // Add sending animation
            this.style.transform = 'scale(0.9) translateY(-2px)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
            
            // Show typing indicator
            showTypingIndicator();
        });
    }
    
    if (questionInput) {
        questionInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendBtn.click();
            }
        });
        
        // Enhanced focus effect
        questionInput.addEventListener('focus', function() {
            this.style.animation = 'inputGlow 0.6s ease-out';
        });
    }
    
    // Typing indicator functions
    function showTypingIndicator() {
        const typingIndicator = document.querySelector('.typing-indicator');
        const onlineStatus = document.querySelector('.online-status');
        
        if (typingIndicator && onlineStatus) {
            onlineStatus.style.display = 'none';
            typingIndicator.style.display = 'block';
            
            // Hide after 2 seconds (simulate AI thinking)
            setTimeout(() => {
                hideTypingIndicator();
            }, 2000);
        }
    }
    
    function hideTypingIndicator() {
        const typingIndicator = document.querySelector('.typing-indicator');
        const onlineStatus = document.querySelector('.online-status');
        
        if (typingIndicator && onlineStatus) {
            typingIndicator.style.display = 'none';
            onlineStatus.style.display = 'flex';
        }
    }
    
    // Add message with animation
    function addMessageWithAnimation(content, isUser = false) {
        if (!qaMessages) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user' : 'bot'} magical-message`;
        messageDiv.style.animation = isUser ? 'userMessageSlideIn 0.6s ease-out' : 'messageSlideIn 0.6s ease-out';
        
        const avatarClass = isUser ? 'user-avatar' : 'ai-avatar';
        const messageClass = isUser ? 'user-message' : 'ai-message';
        
        messageDiv.innerHTML = `
            <div class="message-avatar ${avatarClass}" style="width:36px;height:36px;border-radius:50%;background:${isUser ? 'var(--primary-color)' : 'linear-gradient(135deg,var(--accent-color),var(--primary-color))'};display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;${!isUser ? 'animation:avatarGlow 3s ease-in-out infinite;' : ''}">
                <i class="fas fa-${isUser ? 'user' : 'robot'}" style="color:white;font-size:0.9rem;"></i>
                ${!isUser ? '<div class="avatar-ring" style="position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;border:2px solid var(--accent-color);border-radius:50%;opacity:0;animation:avatarRing 2s ease-in-out infinite;"></div>' : ''}
            </div>
            <div class="message-content ${messageClass}" style="background:var(--card-background);padding:16px 20px;border-radius:18px;border-top-${isUser ? 'right' : 'left'}-radius:6px;max-width:85%;color:var(--primary-color);line-height:1.5;font-size:0.95rem;box-shadow:0 4px 16px rgba(0,0,0,0.08);border:1px solid var(--border-color);position:relative;overflow:hidden;">
                ${!isUser ? '<div class="message-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:messageShine 3s ease-in-out infinite;"></div>' : ''}
                <div style="position:relative;z-index:2;">${content}</div>
            </div>
        `;
        
        if (isUser) {
            messageDiv.style.flexDirection = 'row-reverse';
            messageDiv.style.marginBottom = '20px';
        } else {
            messageDiv.style.marginBottom = '20px';
        }
        
        qaMessages.appendChild(messageDiv);
        qaMessages.scrollTop = qaMessages.scrollHeight;
    }
    
    // Enhanced role selector
    const roleSelect = document.getElementById('chatRoleSelect');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            // Add selection animation
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
            
            // Add a message about role change
            const roleName = this.options[this.selectedIndex].text;
            addMessageWithAnimation(`🔄 Đã chuyển sang phong cách: <strong>${roleName}</strong>`);
        });
    }
    
    // Enhanced hover effects for AI elements
    const aiMessages = document.querySelectorAll('.magical-message');
    aiMessages.forEach(message => {
        message.addEventListener('mouseenter', function() {
            const messageContent = this.querySelector('.message-content');
            if (messageContent) {
                messageContent.style.transform = 'translateY(-1px)';
                messageContent.style.boxShadow = '0 8px 25px rgba(0,0,0,0.12)';
            }
        });
        
        message.addEventListener('mouseleave', function() {
            const messageContent = this.querySelector('.message-content');
            if (messageContent) {
                messageContent.style.transform = '';
                messageContent.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
            }
        });
    });
    
    // Smooth scroll behavior for better UX
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Performance optimization: Pause animations when tab is not visible
    document.addEventListener('visibilitychange', function() {
        const animatedElements = document.querySelectorAll('[style*="animation"]');
        if (document.hidden) {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'paused';
            });
        } else {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'running';
            });
        }
    });
    
    // Chat functionality is handled by main.js
});

// Sidebar Toggle Functions for Mobile
function toggleSidebar() {
    const sidebar = document.getElementById('aiSidebar');
    const showBtn = document.getElementById('showSidebarBtn');
    
    if (sidebar && showBtn) {
        sidebar.classList.add('hidden');
        showBtn.style.display = 'inline-flex';
    }
}

function showSidebar() {
    const sidebar = document.getElementById('aiSidebar');
    const showBtn = document.getElementById('showSidebarBtn');
    
    if (sidebar && showBtn) {
        sidebar.classList.remove('hidden');
        showBtn.style.display = 'none';
    }
}

// Add magical loading effect
window.addEventListener('load', function() {
    // Remove any loading states and trigger entrance animations
    document.body.style.opacity = '1';
    
    // Trigger staggered animations for main elements
    const mainElements = document.querySelectorAll('.main-header, .finance-cards, .content-tabs');
    mainElements.forEach((element, index) => {
        element.style.animation = `fadeInUp 0.8s ease-out ${index * 0.2}s both`;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
<!-- Floating Action Button -->
<!-- <div id="floatingActionBtn" style="position:fixed;bottom:32px;right:32px;width:64px;height:64px;background:var(--accent-color);border-radius:50%;box-shadow:0 8px 32px rgba(102,126,234,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:1000;transition:all 0.3s ease;animation:float 3s ease-in-out infinite;" onclick="document.getElementById('addTransactionBtn').click()" onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 12px 40px rgba(102,126,234,0.6)'" onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 8px 32px rgba(102,126,234,0.4)'">
    <i class="fas fa-plus" style="color:white;font-size:1.5rem;"></i>
</div> -->

<!-- Enhanced CSS Animations -->
<style>
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Enhanced Tab Switching */
.tab-button:hover {
    background: var(--card-background) !important;
    color: var(--primary-color) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}

.tab-button.active {
    background: var(--card-background) !important;
    color: var(--primary-color) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}

/* Smooth transitions for all interactive elements */
* {
    transition: all 0.2s ease;
}

/* Enhanced scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: var(--hover-color);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: var(--accent-color);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}

/* Enhanced Tab Hover Effects */
.tab-button:not(.active):hover {
    background: var(--card-background) !important;
    color: var(--accent-color) !important;
}

/* Enhanced Card Hover Effects */
.finance-card:hover,
.category-card:hover,
.stat-card:hover,
.chart-card:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

/* Enhanced Form Styling */
.form-group {
    position: relative;
}

.form-group input:focus + label,
.form-group select:focus + label {
    color: var(--accent-color);
}

/* Enhanced Focus States */
input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--accent-color) !important;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1) !important;
}

/* Enhanced Button States */
button:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Enhanced Table Styling */
.transactions-table tbody tr:hover {
    background: var(--hover-color) !important;
}

.transactions-table tbody tr:nth-child(even) {
    background: rgba(0, 0, 0, 0.01);
}

/* Enhanced Modal Animations */
.modal {
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    animation: slideInUp 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translate(-50%, -40%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* Responsive enhancements */
@media (max-width: 768px) {
    .finance-cards {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    .header-content {
        flex-direction: column !important;
        gap: 20px !important;
        text-align: center !important;
    }
    
    .tab-nav {
        flex-direction: column !important;
        gap: 4px !important;
    }
    
    .section-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 16px !important;
    }
    
    #floatingActionBtn {
        bottom: 20px !important;
        right: 20px !important;
        width: 56px !important;
        height: 56px !important;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 1rem !important;
    }
    
    .header-content {
        padding: 0 1rem !important;
    }
    
    .tab-content {
        padding: 16px !important;
    }
    
    .finance-card {
        padding: 16px !important;
    }
    
    .modal-content {
        width: 95% !important;
        margin: 20px !important;
    }
    
    .modal-body {
        padding: 16px !important;
    }
    
    .form-actions {
        flex-direction: column !important;
        gap: 8px !important;
    }
    
    .form-actions button {
        width: 100% !important;
    }
}

/* Print Styles */
@media print {
    .header-right,
    .tab-nav,
    #floatingActionBtn,
    .category-actions,
    .form-actions {
        display: none !important;
    }
    
    .main-content {
        padding: 0 !important;
    }
    
    .finance-cards {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* ===== MAGICAL ANIMATIONS ===== */

/* Header Animations */
@keyframes patternFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-10px) rotate(1deg); }
    50% { transform: translateY(-5px) rotate(-1deg); }
    75% { transform: translateY(-15px) rotate(0.5deg); }
}

@keyframes floatParticle1 {
    0%, 100% { transform: translate(20px, 20px) scale(1); opacity: 0.6; }
    25% { transform: translate(100px, 10px) scale(1.2); opacity: 0.8; }
    50% { transform: translate(200px, 30px) scale(0.8); opacity: 0.4; }
    75% { transform: translate(150px, 5px) scale(1.1); opacity: 0.7; }
}

@keyframes floatParticle2 {
    0%, 100% { transform: translate(80%, 60px) scale(1); opacity: 0.4; }
    33% { transform: translate(60%, 20px) scale(1.3); opacity: 0.6; }
    66% { transform: translate(90%, 40px) scale(0.9); opacity: 0.3; }
}

@keyframes floatParticle3 {
    0%, 100% { transform: translate(50%, 80px) scale(1); opacity: 0.5; }
    50% { transform: translate(70%, 10px) scale(1.4); opacity: 0.8; }
}

@keyframes titleGlow {
    0%, 100% { transform: translateX(-100%); opacity: 0; }
    50% { transform: translateX(100%); opacity: 1; }
}

@keyframes iconSpin {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(90deg) scale(1.1); }
    50% { transform: rotate(180deg) scale(1); }
    75% { transform: rotate(270deg) scale(1.1); }
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0px) scale(1); }
    25% { transform: translateY(-3px) scale(1.05); }
    50% { transform: translateY(-6px) scale(1.1); }
    75% { transform: translateY(-3px) scale(1.05); }
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-4px) rotate(2deg); }
    66% { transform: translateY(-2px) rotate(-2deg); }
}

@keyframes buttonShine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

/* Card Animations */
@keyframes cardSlideIn {
    0% { 
        opacity: 0; 
        transform: translateY(30px) scale(0.95); 
    }
    100% { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

@keyframes numberPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

/* Tab Animations */
@keyframes tabsSlideUp {
    0% { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    100% { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes tabShine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

/* Floating Action Button */
@keyframes floatingPulse {
    0%, 100% { 
        transform: scale(1) translateY(0px); 
        box-shadow: 0 8px 32px rgba(102,126,234,0.4); 
    }
    50% { 
        transform: scale(1.05) translateY(-2px); 
        box-shadow: 0 12px 40px rgba(102,126,234,0.6); 
    }
}

/* Enhanced Hover Effects */
.magical-card:hover .card-glow {
    animation: cardGlowPulse 2s ease-in-out infinite;
}

@keyframes cardGlowPulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.05); }
}

.magic-button:hover .button-ripple {
    width: 100px !important;
    height: 100px !important;
}

.magical-tab:hover .tab-ripple {
    width: 80px !important;
    height: 80px !important;
}

/* Loading Animations */
@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

.loading-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

/* Stagger Animation for Multiple Elements */
.finance-card:nth-child(1) { animation-delay: 0s; }
.finance-card:nth-child(2) { animation-delay: 0.1s; }
.finance-card:nth-child(3) { animation-delay: 0.2s; }
.finance-card:nth-child(4) { animation-delay: 0.3s; }

/* Smooth Transitions for All Interactive Elements */
* {
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Enhanced Focus States */
button:focus,
input:focus,
select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3) !important;
    transform: scale(1.02);
}

/* Magical Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: linear-gradient(180deg, var(--hover-color), rgba(248,250,252,0.5));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, var(--accent-color), var(--primary-color));
    border-radius: 4px;
    transition: all 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, var(--primary-color), var(--accent-color));
    transform: scale(1.1);
}

/* Responsive Magic */
@media (max-width: 768px) {
    .floating-particles {
        display: none; /* Hide particles on mobile for performance */
    }
    
    .magical-card {
        animation-duration: 0.6s; /* Faster animations on mobile */
    }
    
    .header-pattern {
        animation-duration: 15s; /* Slower pattern on mobile */
    }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .card-glow {
        background: radial-gradient(circle, rgba(102,126,234,0.2) 0%, transparent 70%) !important;
    }
    
    .header-pattern {
        opacity: 0.02 !important;
    }
    
    .floating-particles .particle {
        opacity: 0.3 !important;
    }
}

/* Performance Optimizations */
.magical-card,
.magic-button,
.magical-tab {
    will-change: transform, box-shadow;
    backface-visibility: hidden;
    perspective: 1000px;
}

/* Accessibility Enhancements */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Enhanced Floating Action Button */
#floatingActionBtn {
    animation: floatingPulse 3s ease-in-out infinite !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

#floatingActionBtn:hover {
    animation: none !important;
    transform: scale(1.15) translateY(-5px) !important;
    box-shadow: 0 15px 50px rgba(102,126,234,0.8) !important;
}

/* ===== AI ASSISTANT MAGICAL ANIMATIONS ===== */

/* AI Tab Slide In */
@keyframes aiTabSlideIn {
    0% { 
        opacity: 0; 
        transform: translateY(30px) scale(0.95); 
    }
    100% { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

/* AI Particles */
@keyframes aiParticle1 {
    0%, 100% { transform: translate(10px, 20px) scale(1); opacity: 0.4; }
    25% { transform: translate(80px, 10px) scale(1.2); opacity: 0.7; }
    50% { transform: translate(150px, 25px) scale(0.8); opacity: 0.3; }
    75% { transform: translate(120px, 5px) scale(1.1); opacity: 0.6; }
}

@keyframes aiParticle2 {
    0%, 100% { transform: translate(70%, 30px) scale(1); opacity: 0.5; }
    33% { transform: translate(40%, 10px) scale(1.3); opacity: 0.8; }
    66% { transform: translate(80%, 20px) scale(0.9); opacity: 0.3; }
}

/* AI Icon Pulse */
@keyframes aiIconPulse {
    0%, 100% { 
        transform: scale(1); 
        box-shadow: 0 0 0 0 rgba(102,126,234,0.4); 
    }
    50% { 
        transform: scale(1.05); 
        box-shadow: 0 0 0 10px rgba(102,126,234,0); 
    }
}

/* Avatar Glow */
@keyframes avatarGlow {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(102,126,234,0.4); 
    }
    50% { 
        box-shadow: 0 0 0 8px rgba(102,126,234,0); 
    }
}

/* Avatar Ring */
@keyframes avatarRing {
    0% { 
        opacity: 0; 
        transform: scale(0.8); 
    }
    50% { 
        opacity: 0.6; 
        transform: scale(1.1); 
    }
    100% { 
        opacity: 0; 
        transform: scale(1.3); 
    }
}

/* Message Slide In */
@keyframes messageSlideIn {
    0% { 
        opacity: 0; 
        transform: translateX(-20px) scale(0.95); 
    }
    100% { 
        opacity: 1; 
        transform: translateX(0) scale(1); 
    }
}

/* Message Shine */
@keyframes messageShine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

/* Typing Animation */
@keyframes typingBounce {
    0%, 60%, 100% { 
        transform: translateY(0); 
    }
    30% { 
        transform: translateY(-8px); 
    }
}

/* Status Pulse */
@keyframes statusPulse {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1); 
    }
    50% { 
        opacity: 0.6; 
        transform: scale(1.2); 
    }
}

/* Enhanced Message Interactions */
.magical-message:hover {
    transform: translateX(3px);
}

.magical-message:hover .message-content {
    box-shadow: 0 8px 25px rgba(0,0,0,0.12) !important;
}

.ai-message:hover .message-shine {
    animation: messageShine 1.5s ease-in-out;
}

/* Send Button Interactions */
.magical-send-btn:active {
    transform: scale(0.95) translateY(-2px) !important;
}

.magical-send-btn:hover .btn-ripple {
    width: 60px !important;
    height: 60px !important;
}

/* Input Focus Effects */
.magical-input input:focus {
    animation: inputGlow 0.6s ease-out;
}

@keyframes inputGlow {
    0% { box-shadow: 0 0 0 0 rgba(102,126,234,0.4); }
    50% { box-shadow: 0 0 0 8px rgba(102,126,234,0.1); }
    100% { box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
}

/* Select Enhancement */
.magical-select:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

/* Chat Scroll Enhancement */
.magical-messages {
    scroll-behavior: smooth;
}

.magical-messages::-webkit-scrollbar {
    width: 6px;
}

.magical-messages::-webkit-scrollbar-track {
    background: linear-gradient(180deg, var(--hover-color), rgba(248,250,252,0.5));
    border-radius: 3px;
}

.magical-messages::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, var(--accent-color), var(--primary-color));
    border-radius: 3px;
    transition: all 0.3s ease;
}

.magical-messages::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, var(--primary-color), var(--accent-color));
}

/* Loading Message Animation */
@keyframes messageLoading {
    0% { opacity: 0.5; transform: scale(0.98); }
    50% { opacity: 1; transform: scale(1); }
    100% { opacity: 0.5; transform: scale(0.98); }
}

.loading-message {
    animation: messageLoading 1.5s ease-in-out infinite;
}

/* User Message Slide In (from right) */
@keyframes userMessageSlideIn {
    0% { 
        opacity: 0; 
        transform: translateX(20px) scale(0.95); 
    }
    100% { 
        opacity: 1; 
        transform: translateX(0) scale(1); 
    }
}

/* Enhanced Hover States */
.magical-chat-container:hover .ai-header {
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.magical-chat:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important;
}

/* Responsive AI Enhancements */
@media (max-width: 768px) {
    .qa-container {
        grid-template-columns: 1fr !important;
        height: auto !important;
        gap: 16px !important;
    }
    
    .ai-particles {
        display: none; /* Hide particles on mobile */
    }
    
    .magical-messages {
        max-height: 300px !important;
        min-height: 300px !important;
    }
    
    .ai-header {
        padding: 16px !important;
    }
    
    .qa-input {
        padding: 12px 16px !important;
    }
}

/* Dark Mode AI Enhancements */
@media (prefers-color-scheme: dark) {
    .ai-message {
        background: rgba(30, 41, 59, 0.8) !important;
        border-color: rgba(71, 85, 105, 0.3) !important;
    }
    
    .message-shine {
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent) !important;
    }
    
    .ai-particles .ai-particle {
        opacity: 0.2 !important;
    }
}

/* Performance Optimization for AI */
.magical-chat-container,
.magical-message,
.magical-send-btn {
    will-change: transform;
    backface-visibility: hidden;
}

/* Accessibility for AI Chat */
@media (prefers-reduced-motion: reduce) {
    .ai-particles,
    .typing-dots,
    .avatar-ring,
    .message-shine {
        animation: none !important;
    }
}
/* ===== MAGICAL DASHBOARD CSS ANIMATIONS ===== */

/* Header Magical Animations */
@keyframes patternMegaFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
    25% { transform: translateY(-12px) rotate(1deg) scale(1.01); }
    50% { transform: translateY(-6px) rotate(-0.5deg) scale(0.99); }
    75% { transform: translateY(-18px) rotate(0.8deg) scale(1.02); }
}

@keyframes floatDashboard1 {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); opacity: 0.6; }
    25% { transform: translateY(-20px) scale(1.1) rotate(90deg); opacity: 0.8; }
    50% { transform: translateY(-40px) scale(0.9) rotate(180deg); opacity: 0.4; }
    75% { transform: translateY(-30px) scale(1.2) rotate(270deg); opacity: 0.7; }
}

@keyframes floatDashboard2 {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); opacity: 0.5; }
    33% { transform: translateY(-15px) scale(1.15) rotate(120deg); opacity: 0.8; }
    66% { transform: translateY(-25px) scale(0.85) rotate(240deg); opacity: 0.3; }
}

@keyframes floatDashboard3 {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); opacity: 0.4; }
    20% { transform: translateY(-8px) scale(1.05) rotate(72deg); opacity: 0.6; }
    40% { transform: translateY(-16px) scale(0.95) rotate(144deg); opacity: 0.8; }
    60% { transform: translateY(-24px) scale(1.1) rotate(216deg); opacity: 0.5; }
    80% { transform: translateY(-12px) scale(0.9) rotate(288deg); opacity: 0.7; }
}

@keyframes floatDashboard4 {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); opacity: 0.7; }
    50% { transform: translateY(-35px) scale(1.25) rotate(180deg); opacity: 0.3; }
}

@keyframes floatDashboard5 {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); opacity: 0.3; }
    25% { transform: translateY(-10px) scale(1.08) rotate(90deg); opacity: 0.6; }
    50% { transform: translateY(-20px) scale(0.92) rotate(180deg); opacity: 0.8; }
    75% { transform: translateY(-15px) scale(1.12) rotate(270deg); opacity: 0.4; }
}

@keyframes heroGlowPulse {
    0%, 100% { opacity: 0.6; transform: translate(-50%,-50%) scale(1); }
    25% { opacity: 0.8; transform: translate(-50%,-50%) scale(1.05); }
    50% { opacity: 1; transform: translate(-50%,-50%) scale(0.95); }
    75% { opacity: 0.7; transform: translate(-50%,-50%) scale(1.02); }
}

@keyframes megaTitleGlow {
    0%, 100% { opacity: 0.3; transform: scale(1) rotate(0deg); }
    25% { opacity: 0.6; transform: scale(1.02) rotate(0.5deg); }
    50% { opacity: 0.8; transform: scale(0.98) rotate(-0.3deg); }
    75% { opacity: 0.4; transform: scale(1.01) rotate(0.2deg); }
}

@keyframes decorationSpin {
    0% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(90deg) scale(1.1); }
    50% { transform: rotate(180deg) scale(0.9); }
    75% { transform: rotate(270deg) scale(1.05); }
    100% { transform: rotate(360deg) scale(1); }
}

@keyframes userPulse {
    0%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.1) rotate(5deg); }
    50% { transform: scale(0.95) rotate(-3deg); }
    75% { transform: scale(1.05) rotate(2deg); }
}

@keyframes calendarPulse {
    0%, 100% { transform: scale(1) translateY(0px); }
    50% { transform: scale(1.08) translateY(-2px); }
}

@keyframes clockTick {
    0%, 50%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.05) rotate(6deg); }
    75% { transform: scale(1.05) rotate(-6deg); }
}

@keyframes moonOrbit {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(90deg) scale(1.1); }
    50% { transform: rotate(180deg) scale(0.9); }
    75% { transform: rotate(270deg) scale(1.05); }
}

@keyframes addIconBounce {
    0%, 100% { transform: translateY(0px) scale(1) rotate(0deg); }
    25% { transform: translateY(-3px) scale(1.1) rotate(90deg); }
    50% { transform: translateY(-6px) scale(0.9) rotate(180deg); }
    75% { transform: translateY(-3px) scale(1.05) rotate(270deg); }
}

@keyframes buttonShineMega {
    0% { left: -120%; }
    50% { left: 120%; }
    100% { left: 120%; }
}

/* Tab Magical Animations */
@keyframes tabsSlideUp {
    0% { opacity: 0; transform: translateY(40px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes tabShine {
    0% { left: -120%; }
    50% { left: 120%; }
    100% { left: 120%; }
}

/* Enhanced Button Interactions */
.magical-header-button:hover .button-ripple {
    width: 80px !important;
    height: 80px !important;
}

.magical-tab:hover .tab-ripple {
    width: 100px !important;
    height: 100px !important;
}

.magical-tab.active .tab-shine {
    animation: tabShine 2s ease-in-out infinite;
}

/* Card Magical Interactions */
.magical-card:hover .card-glow {
    animation: cardGlowPulse 2s ease-in-out infinite;
}

@keyframes cardGlowPulse {
    0%, 100% { opacity: 0.6; transform: scale(1) rotate(0deg); }
    50% { opacity: 1; transform: scale(1.01) rotate(0.5deg); }
}

/* Finance Card Enhancements */
.finance-card:hover .card-icon {
    animation: cardIconDance 1.5s ease-in-out;
}

@keyframes cardIconDance {
    0%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.15) rotate(5deg); }
    50% { transform: scale(0.95) rotate(-3deg); }
    75% { transform: scale(1.08) rotate(2deg); }
}

/* Info Item Enhanced Interactions */
.info-item-enhanced:hover {
    animation: itemGlow 0.6s ease-out;
}

@keyframes itemGlow {
    0% { box-shadow: 0 0 0 0 rgba(102,126,234,0.4); }
    50% { box-shadow: 0 0 0 10px rgba(102,126,234,0.1); }
    100% { box-shadow: 0 0 0 0 rgba(102,126,234,0.1); }
}

/* Loading and Transition States */
@keyframes contentFadeIn {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}

.content-loading {
    animation: contentFadeIn 0.8s ease-out;
}

/* Enhanced Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: linear-gradient(180deg, var(--hover-color), rgba(248,250,252,0.7));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, var(--accent-color), var(--primary-color));
    border-radius: 4px;
    transition: all 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, var(--primary-color), var(--positive-color));
    transform: scale(1.1);
}

/* Stats Enhancement */
.stat-card:hover {
    background: linear-gradient(135deg, var(--card-background), rgba(102,126,234,0.02)) !important;
}

.stat-card:hover i {
    animation: statIconPulse 1s ease-in-out;
}

@keyframes statIconPulse {
    0%, 100% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.2) rotate(10deg); }
}

/* Empty State Enhancement */
.empty-state:hover .empty-icon {
    animation: emptyIconFloat 2s ease-in-out;
}

@keyframes emptyIconFloat {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-10px) scale(1.1); }
}

/* Transaction Row Enhancement */
.magical-transaction-row {
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.magical-transaction-row:hover {
    background: linear-gradient(90deg, var(--hover-color), rgba(102,126,234,0.02)) !important;
    transform: translateX(4px) scale(1.002) !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
}

/* Badge Enhancement */
.badge:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Chart Card Enhancement */
.chart-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Chart Canvas Enhancement */
canvas {
    transition: all 0.3s ease;
}

canvas:hover {
    transform: scale(1.02);
    filter: brightness(1.05);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .floating-particles { display: none; }
    .hero-glow { display: none; }
    .header-pattern { opacity: 0.02 !important; }
    
    .magical-dashboard-header { padding: 1.5rem 0 !important; }
    .header-content { flex-direction: column !important; gap: 20px !important; text-align: center !important; }
    .header-left { text-align: center !important; }
    .user-info-enhanced { flex-direction: column !important; gap: 12px !important; }
    .magical-dashboard-title { font-size: 2.2rem !important; }
    .magical-subtitle { font-size: 1rem !important; }
    
    .finance-cards { gap: 16px !important; }
    .finance-card { padding: 20px !important; }
    .card-amount { font-size: 1.8rem !important; }
    
    .content-tabs { margin: 0 -8px !important; border-radius: 12px !important; }
    .tab-nav { padding: 6px !important; gap: 4px !important; }
    .magical-tab { padding: 12px 16px !important; font-size: 0.9rem !important; }
}

@media (max-width: 480px) {
    .magical-dashboard-title { font-size: 2rem !important; }
    .finance-cards { grid-template-columns: 1fr !important; }
    .info-item-enhanced { padding: 6px 12px !important; font-size: 0.85rem !important; }
    .magical-header-button.primary { padding: 12px 16px !important; font-size: 0.9rem !important; }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .hero-glow { background: radial-gradient(ellipse,rgba(102,126,234,0.05) 0%,transparent 70%) !important; }
    .header-pattern { opacity: 0.02 !important; }
    .floating-particles .particle { opacity: 0.4 !important; box-shadow: none !important; }
    .card-glow { background: radial-gradient(circle,rgba(102,126,234,0.08) 0%,transparent 70%) !important; }
    .mega-title-glow { background: linear-gradient(45deg,transparent,rgba(102,126,234,0.08),transparent,rgba(34,197,94,0.05),transparent) !important; }
}

/* Performance Optimizations */
.magical-dashboard-header,
.magical-card,
.magical-header-button,
.magical-tab,
.info-item-enhanced {
    will-change: transform, box-shadow;
    backface-visibility: hidden;
    perspective: 1000px;
}

/* Accessibility Enhancements */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .floating-particles,
    .header-pattern,
    .hero-glow {
        display: none !important;
    }
}

/* Focus States */
.magical-header-button:focus,
.magical-tab:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3) !important;
}

/* Loading States */
@keyframes shimmerLoad {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

.loading-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200px 100%;
    animation: shimmerLoad 1.5s infinite;
}

/* Stagger Animation for Cards */
.finance-card:nth-child(1) { animation-delay: 0s; }
.finance-card:nth-child(2) { animation-delay: 0.1s; }
.finance-card:nth-child(3) { animation-delay: 0.2s; }

/* Enhanced Tab Navigation */
.magical-tab.active {
    background: var(--card-background) !important;
    color: var(--primary-color) !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
}

.magical-tab:not(.active):hover {
    background: rgba(255,255,255,0.7) !important;
    color: var(--primary-color) !important;
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
}

/* Success States */
.success-glow {
    box-shadow: 0 0 20px rgba(34,197,94,0.3) !important;
    border-color: var(--positive-color) !important;
}

.error-glow {
    box-shadow: 0 0 20px rgba(239,68,68,0.3) !important;
    border-color: var(--negative-color) !important;
}
</style>

<!-- Enhanced Magical JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced button click animations
    const magicalButtons = document.querySelectorAll('.magical-header-button');
    magicalButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = this.querySelector('.button-ripple');
            if (ripple) {
                ripple.style.width = '100px';
                ripple.style.height = '100px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 800);
            }
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Enhanced tab interactions
    const magicalTabs = document.querySelectorAll('.magical-tab');
    magicalTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            magicalTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Create ripple effect
            const ripple = this.querySelector('.tab-ripple');
            if (ripple) {
                ripple.style.width = '120px';
                ripple.style.height = '120px';
                setTimeout(() => {
                    ripple.style.width = '0';
                    ripple.style.height = '0';
                }, 800);
            }
        });
    });
    
    // Enhanced card interactions
    const financeCards = document.querySelectorAll('.finance-card');
    financeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // Trigger glow effect
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '1';
                glow.style.animation = 'cardGlowPulse 2s ease-in-out infinite';
            }
            
            // Enhance icon animation
            const icon = this.querySelector('.card-icon');
            if (icon) {
                icon.style.animation = 'cardIconDance 1.5s ease-in-out';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.opacity = '0';
                glow.style.animation = '';
            }
            
            const icon = this.querySelector('.card-icon');
            if (icon) {
                setTimeout(() => {
                    icon.style.animation = 'iconFloat 3s ease-in-out infinite';
                }, 1500);
            }
        });
    });
    
    // Enhanced info item interactions
    const infoItems = document.querySelectorAll('.info-item-enhanced');
    infoItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.animation = 'itemGlow 0.6s ease-out';
        });
        
        item.addEventListener('mouseleave', function() {
            setTimeout(() => {
                this.style.animation = '';
            }, 600);
        });
    });
    
    // Stats card interactions
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.animation = 'statIconPulse 1s ease-in-out';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            if (icon) {
                setTimeout(() => {
                    icon.style.animation = '';
                }, 1000);
            }
        });
    });
    
    // Empty state interactions
    const emptyStates = document.querySelectorAll('.empty-state');
    emptyStates.forEach(state => {
        state.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.empty-icon');
            if (icon) {
                icon.style.animation = 'emptyIconFloat 2s ease-in-out';
            }
        });
    });
    
    // Chart enhancement
    const chartCards = document.querySelectorAll('.chart-card');
    chartCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const canvas = this.querySelector('canvas');
            if (canvas) {
                canvas.style.transform = 'scale(1.02)';
                canvas.style.filter = 'brightness(1.05)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const canvas = this.querySelector('canvas');
            if (canvas) {
                canvas.style.transform = 'scale(1)';
                canvas.style.filter = 'brightness(1)';
            }
        });
    });
    
    // Enhanced page load animation
    window.addEventListener('load', function() {
        document.body.style.opacity = '1';
        
        // Staggered animation for main elements
        const mainElements = document.querySelectorAll('.magical-dashboard-header, .finance-cards, .content-tabs');
        mainElements.forEach((element, index) => {
            element.style.animation = `contentFadeIn 1s ease-out ${index * 0.15}s both`;
        });
        
        // Cards stagger animation
        const cards = document.querySelectorAll('.finance-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${0.5 + index * 0.1}s`;
        });
    });
    
    // Theme toggle is now handled by main.js
    
    // Enhanced add transaction button
    const addTransactionBtn = document.getElementById('addTransactionBtn');
    if (addTransactionBtn) {
        addTransactionBtn.addEventListener('click', function() {
            // Trigger modal or action
            console.log('Add Transaction clicked');
            
            // Enhanced animation
            this.style.transform = 'translateY(-4px) scale(1.1)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    }
    
    // Performance optimization: Pause animations when tab is not visible
    document.addEventListener('visibilitychange', function() {
        const animatedElements = document.querySelectorAll('[style*="animation"]');
        if (document.hidden) {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'paused';
            });
        } else {
            animatedElements.forEach(el => {
                el.style.animationPlayState = 'running';
            });
        }
    });
    
    // Enhanced smooth scroll
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Intersection Observer for animation triggers
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -10% 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'contentFadeIn 0.8s ease-out both';
            }
        });
    }, observerOptions);
    
    // Observe elements that should animate on scroll
    const observeElements = document.querySelectorAll('.stat-card, .chart-card, .transactions-table');
    observeElements.forEach(el => observer.observe(el));
    
    // Enhanced error handling with visual feedback
    function showSuccessGlow(element) {
        element.classList.add('success-glow');
        setTimeout(() => {
            element.classList.remove('success-glow');
        }, 2000);
    }
    
    function showErrorGlow(element) {
        element.classList.add('error-glow');
        setTimeout(() => {
            element.classList.remove('error-glow');
        }, 2000);
    }
    
    // Add to global scope for other scripts
    window.dashboardAnimations = {
        showSuccessGlow,
        showErrorGlow
    };
});

// Additional performance optimizations
if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
        // Non-critical animations
        const decorativeElements = document.querySelectorAll('.title-decoration, .floating-particles .particle');
        decorativeElements.forEach(el => {
            el.style.willChange = 'transform';
        });
    });
}

// Enhanced console welcome message
console.log(`
🎉 Magical Dashboard loaded!
✨ Enhanced with premium animations
🚀 Performance optimized
💫 Dark mode compatible
📱 Fully responsive
`);
</script>

<!-- Load main.js after all DOM elements -->
<script src="js/main.js"></script>

    <!-- Simple test script -->
    <script>
        // Test modal function
        window.testModal = function() {
            const modal = document.getElementById("editTransactionModal")
            modal.style.display = "block"
            document.body.style.overflow = "hidden"
            console.log("Modal opened")
        }
    </script>
</body>
</html>


