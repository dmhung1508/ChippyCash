<?php
// Enable error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/settings.php';

// Initialize system settings
$systemSettings = getSystemSettingsManager($conn);
$systemSettings->applyToEnvironment();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id'])) {
    // Preserve admin_access parameter when redirecting
    $redirect_url = 'index.php';
    if (isset($_GET['admin_access']) && $_GET['admin_access'] == '1') {
        $redirect_url .= '?admin_access=1';
    }
    header('Location: ' . $redirect_url);
    exit;
}

// Kiểm tra role admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Admin Panel - Quản lý Hệ thống';

// Xử lý các actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_user_role':
            $userId = $_POST['user_id'];
            $newRole = $_POST['role'];
            
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            
            // Log admin action
            logAdminAction($_SESSION['user_id'], 'update_user_role', 'user', $userId, "Changed role to: $newRole");
            $success_message = "Cập nhật quyền người dùng thành công!";
            break;
            
        case 'delete_user':
            $userId = $_POST['user_id'];
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$userId]);
            
            logAdminAction($_SESSION['user_id'], 'delete_user', 'user', $userId, "Deleted user");
            $success_message = "Xóa người dùng thành công!";
            break;
            
        case 'update_setting':
            $settingKey = $_POST['setting_key'];
            $settingValue = $_POST['setting_value'];
            
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$settingValue, $settingKey]);
            
            logAdminAction($_SESSION['user_id'], 'update_setting', 'setting', null, "Updated $settingKey to: $settingValue");
            $success_message = "Cập nhật cài đặt thành công!";
            break;
            
        case 'send_notification':
            $title = $_POST['title'];
            $message = $_POST['message'];
            $type = $_POST['type'];
            $isGlobal = isset($_POST['is_global']);
            $userId = $isGlobal ? null : $_POST['user_id'];

            if ($isGlobal) {
                // Send to all users
                $stmt = $conn->prepare("SELECT * FROM users");
                $stmt->execute();
                $users = $stmt->fetchAll();
                
                foreach ($users as $user) {
                    // Insert notification to database
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user['id'], $title, $message, $type]);
                    
                    // Send email notification via Mailgun
                    sendEmailNotification($user['email'], $user['name'], $title, $message, $type);
                }
                
                logAdminAction($_SESSION['user_id'], 'send_global_notification', 'notification', null, "Global notification: $title");
            } else {
                // Send to specific user
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $title, $message, $type]);
                
                // Get user info for email
                $stmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Send email notification via Mailgun
                    sendEmailNotification($user['email'], $user['name'], $title, $message, $type);
                }
                
                logAdminAction($_SESSION['user_id'], 'send_notification', 'notification', $userId, "Notification to user $userId: $title");
            }

            $success_message = "Gửi thông báo thành công!";
            break;
    }
}

// Lấy thống kê tổng quan
$stats = getSystemStats($conn);

// Lấy danh sách users
$users = getAllUsers($conn);

// Lấy cài đặt hệ thống
$settings = getSystemSettings($conn);

// Lấy logs gần đây
$recentLogs = getRecentAdminLogs($conn);

// Helper functions
function logAdminAction($adminId, $action, $targetType = null, $targetId = null, $details = null) {
    global $conn;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details, $ipAddress, $userAgent]);
}

function sendEmailNotification($to_email, $to_name, $subject, $message, $type = 'info') {
    // Load Mailgun configuration from database.php
    global $mailgun_config;
    $mailgun_domain = $mailgun_config['domain'];
    $mailgun_api_key = $mailgun_config['api_key'];
    $from_email = $mailgun_config['from_email'];
    $from_name = $mailgun_config['from_name'];
    
    // Email template with styling
    $email_template = getEmailTemplate($to_name, $subject, $message, $type);
    
    // Mailgun API endpoint
    $url = "https://api.mailgun.net/v3/{$mailgun_domain}/messages";
    
    // Prepare data for Mailgun
    $postData = [
        'from' => "{$from_name} <{$from_email}>",
        'to' => "{$to_name} <{$to_email}>",
        'subject' => $subject,
        'html' => $email_template,
        'text' => strip_tags($message) // Fallback plain text
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "api:{$mailgun_api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log the result
    if ($http_code == 200) {
        error_log("Email sent successfully to {$to_email}: {$subject}");
        return true;
    } else {
        error_log("Failed to send email to {$to_email}. HTTP Code: {$http_code}, Error: {$error}, Response: {$response}");
        return false;
    }
}

function getEmailTemplate($user_name, $subject, $message, $type) {
    // Define colors based on notification type
    $colors = [
        'info' => ['bg' => '#3b82f6', 'accent' => '#1e40af'],
        'success' => ['bg' => '#10b981', 'accent' => '#047857'],
        'warning' => ['bg' => '#f59e0b', 'accent' => '#d97706'],
        'error' => ['bg' => '#ef4444', 'accent' => '#dc2626']
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$subject}</title>
    </head>
    <body style='margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f5f5f5;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f5f5f5;padding:20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);overflow:hidden;'>
                        <!-- Header -->
                        <tr>
                            <td style='background:linear-gradient(135deg,{$color['bg']},{$color['accent']});padding:30px;text-align:center;'>
                                <h1 style='color:white;margin:0;font-size:24px;font-weight:bold;'>💼 Finance Management</h1>
                                <p style='color:rgba(255,255,255,0.9);margin:5px 0 0;font-size:14px;'>Thông báo từ hệ thống</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding:30px;'>
                                <h2 style='color:#333;margin:0 0 10px;font-size:20px;'>Chào {$user_name}!</h2>
                                <h3 style='color:{$color['bg']};margin:0 0 20px;font-size:18px;'>{$subject}</h3>
                                
                                <div style='background-color:#f8f9fa;border-left:4px solid {$color['bg']};padding:20px;margin:20px 0;border-radius:0 8px 8px 0;'>
                                    " . nl2br(htmlspecialchars($message)) . "
                                </div>
                                
                                <p style='color:#666;font-size:14px;line-height:1.6;margin:20px 0;'>
                                    Đây là email thông báo tự động từ hệ thống Finance Management. 
                                    Vui lòng đăng nhập vào hệ thống để xem chi tiết.
                                </p>
                                
                                <div style='text-align:center;margin:30px 0;'>
                                    <a href='#' style='background:linear-gradient(135deg,{$color['bg']},{$color['accent']});color:white;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:bold;'>
                                        Truy cập hệ thống
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #e9ecef;'>
                                <p style='color:#6c757d;font-size:12px;margin:0;'>
                                    © 2024 Finance Management System. Được gửi bởi Mailgun.
                                </p>
                                <p style='color:#6c757d;font-size:12px;margin:5px 0 0;'>
                                    🌐 mail.dinhmanhhung.net | 📧 Không trả lời email này
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

function getSystemStats($conn) {
    $stats = [];
    
    // Tổng số users
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    // Tổng số giao dịch
    $stmt = $conn->query("SELECT COUNT(*) as total_transactions FROM transactions");
    $stats['total_transactions'] = $stmt->fetch()['total_transactions'];
    
    // Tổng số tiền
    $stmt = $conn->query("SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as total_balance FROM transactions");
    $stats['total_balance'] = $stmt->fetch()['total_balance'] ?? 0;
    
    // Users mới trong 30 ngày
    $stmt = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_users'] = $stmt->fetch()['new_users'];
    
    // Giao dịch trong 30 ngày
    $stmt = $conn->query("SELECT COUNT(*) as recent_transactions FROM transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['recent_transactions'] = $stmt->fetch()['recent_transactions'];
    
    return $stats;
}

function getAllUsers($conn) {
    $stmt = $conn->query("
        SELECT u.*, 
               COUNT(t.id) as transaction_count,
               COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END), 0) as balance
        FROM users u 
        LEFT JOIN transactions t ON u.id = t.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ");
    return $stmt->fetchAll();
}

function getSystemSettings($conn) {
    $stmt = $conn->query("SELECT * FROM system_settings ORDER BY setting_key");
    return $stmt->fetchAll();
}

function getRecentAdminLogs($conn) {
    $stmt = $conn->query("
        SELECT al.*, u.name as admin_name 
        FROM admin_logs al 
        JOIN users u ON al.admin_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 20
    ");
    return $stmt->fetchAll();
}

?>

<?php include 'includes/header.php'; ?>

<div class="app-container">
    <!-- Enhanced Admin Header -->
    <header class="magical-dashboard-header" style="background:linear-gradient(135deg,#1e1b4b,#312e81,#1e40af);border-bottom:1px solid rgba(255,255,255,0.1);padding:2.5rem 0;position:relative;overflow:hidden;">
        <!-- Animated Background Pattern -->
        <div class="header-pattern" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.1;background:radial-gradient(circle at 15% 35%, #ffffff 2px, transparent 2px), radial-gradient(circle at 85% 65%, #fbbf24 1.5px, transparent 1.5px), radial-gradient(circle at 50% 15%, #ef4444 1px, transparent 1px);background-size:50px 50px, 80px 80px, 60px 60px;animation:patternMegaFloat 25s ease-in-out infinite;"></div>
        
        <!-- Floating Admin Particles -->
        <div class="floating-particles" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;">
            <div class="particle" style="position:absolute;width:8px;height:8px;background:linear-gradient(45deg,#fbbf24,#f59e0b);border-radius:50%;opacity:0.6;top:20%;left:15%;animation:floatDashboard1 12s ease-in-out infinite;box-shadow:0 0 10px rgba(251,191,36,0.3);"></div>
            <div class="particle" style="position:absolute;width:6px;height:6px;background:linear-gradient(45deg,#ef4444,#dc2626);border-radius:50%;opacity:0.5;top:70%;left:85%;animation:floatDashboard2 16s ease-in-out infinite 2s;box-shadow:0 0 8px rgba(239,68,68,0.3);"></div>
            <div class="particle" style="position:absolute;width:5px;height:5px;background:linear-gradient(45deg,#8b5cf6,#7c3aed);border-radius:50%;opacity:0.4;top:15%;left:75%;animation:floatDashboard3 14s ease-in-out infinite 1s;box-shadow:0 0 6px rgba(139,92,246,0.2);"></div>
        </div>
        
        <div class="header-content" style="max-width:1400px;margin:0 auto;padding:0 2rem;position:relative;z-index:3;">
            <div class="header-left" style="animation:slideInLeft 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <div class="title-container" style="position:relative;margin-bottom:16px;">
                    <h1 class="magical-dashboard-title" style="font-size:2.8rem;font-weight:800;margin:0;color:#ffffff;position:relative;display:inline-block;">
                        🛡️ Admin Panel
                        <span class="mega-title-glow" style="position:absolute;top:-10px;left:-10px;right:-10px;bottom:-10px;background:linear-gradient(45deg,transparent,rgba(251,191,36,0.15),transparent,rgba(139,92,246,0.1),transparent);animation:megaTitleGlow 4s ease-in-out infinite;z-index:-1;border-radius:12px;"></span>
                    </h1>
                </div>
                <p class="magical-subtitle" style="font-size:1.1rem;color:rgba(255,255,255,0.8);margin:0 0 16px;animation:fadeInUp 1.2s ease-out 0.3s both;font-weight:500;">Quản lý và giám sát hệ thống tài chính</p>
                <div class="admin-info" style="display:flex;align-items:center;gap:20px;font-size:0.95rem;color:rgba(255,255,255,0.7);animation:fadeInUp 1.2s ease-out 0.6s both;flex-wrap:wrap;">
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:20px;backdrop-filter:blur(10px);">
                        <i class="fas fa-crown" style="color:#fbbf24;"></i> 
                        <span style="font-weight:600;color:#ffffff;">Administrator</span>
                    </div>
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:20px;backdrop-filter:blur(10px);">
                        <i class="fas fa-users" style="color:#8b5cf6;"></i> 
                        <span style="font-weight:500;"><?php echo $stats['total_users']; ?> Users</span>
                    </div>
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:20px;backdrop-filter:blur(10px);">
                        <i class="fas fa-chart-line" style="color:#10b981;"></i> 
                        <span style="font-weight:500;"><?php echo number_format($stats['total_transactions']); ?> Giao dịch</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding:2rem;max-width:1400px;margin:0 auto;">
        <!-- Flash Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background:rgba(34,197,94,0.1);color:#15803d;border:1px solid rgba(34,197,94,0.2);border-radius:12px;padding:1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;animation:slideInDown 0.4s ease-out;">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Admin Navigation Tabs -->
        <div class="admin-tabs" style="display:flex;gap:1rem;margin-bottom:2rem;border-bottom:2px solid var(--border-color);padding-bottom:1rem;">
            <button class="tab-button active" data-tab="dashboard" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;cursor:pointer;">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </button>
            <button class="tab-button" data-tab="users" style="background:var(--hover-color);color:var(--primary-color);border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;cursor:pointer;">
                <i class="fas fa-users"></i> Quản lý Users
            </button>
            <button class="tab-button" data-tab="settings" style="background:var(--hover-color);color:var(--primary-color);border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;cursor:pointer;">
                <i class="fas fa-cogs"></i> Cài đặt
            </button>
            <button class="tab-button" data-tab="notifications" style="background:var(--hover-color);color:var(--primary-color);border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;cursor:pointer;">
                <i class="fas fa-bell"></i> Thông báo
            </button>
            <button class="tab-button" data-tab="logs" style="background:var(--hover-color);color:var(--primary-color);border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;cursor:pointer;">
                <i class="fas fa-history"></i> Logs
            </button>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <!-- Stats Cards -->
            <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem;">
                <div class="stat-card magical-card" style="background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(16,185,129,0.1));border:1px solid rgba(34,197,94,0.2);border-radius:16px;padding:2rem;position:relative;overflow:hidden;animation:cardSlideIn 0.6s ease-out;">
                    <div class="stat-icon" style="width:60px;height:60px;background:linear-gradient(135deg,var(--positive-color),#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;animation:iconFloat 3s ease-in-out infinite;">
                        <i class="fas fa-users" style="color:white;font-size:1.5rem;"></i>
                    </div>
                    <h3 style="margin:0 0 0.5rem;color:var(--positive-color);font-size:2rem;font-weight:800;"><?php echo number_format($stats['total_users']); ?></h3>
                    <p style="margin:0;color:var(--secondary-color);font-weight:600;">Tổng số người dùng</p>
                    <small style="color:var(--positive-color);font-size:0.85rem;margin-top:0.5rem;display:block;">+<?php echo $stats['new_users']; ?> trong 30 ngày</small>
                </div>

                <div class="stat-card magical-card" style="background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(59,130,246,0.1));border:1px solid rgba(102,126,234,0.2);border-radius:16px;padding:2rem;position:relative;overflow:hidden;animation:cardSlideIn 0.6s ease-out 0.1s both;">
                    <div class="stat-icon" style="width:60px;height:60px;background:linear-gradient(135deg,var(--accent-color),var(--primary-color));border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;animation:iconFloat 3s ease-in-out infinite 0.5s;">
                        <i class="fas fa-exchange-alt" style="color:white;font-size:1.5rem;"></i>
                    </div>
                    <h3 style="margin:0 0 0.5rem;color:var(--accent-color);font-size:2rem;font-weight:800;"><?php echo number_format($stats['total_transactions']); ?></h3>
                    <p style="margin:0;color:var(--secondary-color);font-weight:600;">Tổng giao dịch</p>
                    <small style="color:var(--accent-color);font-size:0.85rem;margin-top:0.5rem;display:block;">+<?php echo $stats['recent_transactions']; ?> trong 30 ngày</small>
                </div>

                <div class="stat-card magical-card" style="background:linear-gradient(135deg,rgba(251,191,36,0.1),rgba(245,158,11,0.1));border:1px solid rgba(251,191,36,0.2);border-radius:16px;padding:2rem;position:relative;overflow:hidden;animation:cardSlideIn 0.6s ease-out 0.2s both;">
                    <div class="stat-icon" style="width:60px;height:60px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;animation:iconFloat 3s ease-in-out infinite 1s;">
                        <i class="fas fa-wallet" style="color:white;font-size:1.5rem;"></i>
                    </div>
                    <h3 style="margin:0 0 0.5rem;color:#f59e0b;font-size:2rem;font-weight:800;"><?php echo number_format($stats['total_balance']); ?>₫</h3>
                    <p style="margin:0;color:var(--secondary-color);font-weight:600;">Tổng số dư hệ thống</p>
                    <small style="color:#f59e0b;font-size:0.85rem;margin-top:0.5rem;display:block;">Tất cả người dùng</small>
                </div>

                <div class="stat-card magical-card" style="background:linear-gradient(135deg,rgba(139,92,246,0.1),rgba(124,58,237,0.1));border:1px solid rgba(139,92,246,0.2);border-radius:16px;padding:2rem;position:relative;overflow:hidden;animation:cardSlideIn 0.6s ease-out 0.3s both;">
                    <div class="stat-icon" style="width:60px;height:60px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;animation:iconFloat 3s ease-in-out infinite 1.5s;">
                        <i class="fas fa-server" style="color:white;font-size:1.5rem;"></i>
                    </div>
                    <h3 style="margin:0 0 0.5rem;color:#8b5cf6;font-size:2rem;font-weight:800;">Online</h3>
                    <p style="margin:0;color:var(--secondary-color);font-weight:600;">Trạng thái hệ thống</p>
                    <small style="color:#8b5cf6;font-size:0.85rem;margin-top:0.5rem;display:block;">Hoạt động bình thường</small>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;margin-bottom:2rem;animation:cardSlideIn 0.8s ease-out 0.4s both;">
                <h3 style="margin:0 0 1.5rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-clock" style="color:var(--accent-color);"></i>
                    Hoạt động gần đây
                </h3>
                <div class="activity-list" style="max-height:400px;overflow-y:auto;">
                    <?php foreach (array_slice($recentLogs, 0, 10) as $log): ?>
                        <div class="activity-item" style="display:flex;align-items:center;gap:1rem;padding:1rem;border-bottom:1px solid var(--border-color);transition:all 0.3s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                            <div class="activity-icon" style="width:40px;height:40px;background:rgba(102,126,234,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user-shield" style="color:var(--accent-color);font-size:0.9rem;"></i>
                            </div>
                            <div class="activity-content" style="flex:1;">
                                <div style="font-weight:600;color:var(--primary-color);margin-bottom:2px;"><?php echo htmlspecialchars($log['admin_name']); ?></div>
                                <div style="font-size:0.9rem;color:var(--secondary-color);margin-bottom:2px;"><?php echo htmlspecialchars($log['action']); ?></div>
                                <?php if ($log['details']): ?>
                                    <div style="font-size:0.8rem;color:var(--secondary-color);opacity:0.8;"><?php echo htmlspecialchars($log['details']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time" style="font-size:0.8rem;color:var(--secondary-color);">
                                <?php echo date('H:i d/m', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Users Management Tab -->
        <div id="users-tab" class="tab-content" style="display:none;">
            <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;">
                <h3 style="margin:0 0 1.5rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-users" style="color:var(--accent-color);"></i>
                    Quản lý người dùng
                </h3>
                
                <div class="users-table" style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead style="background:var(--hover-color);">
                            <tr>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">ID</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Tên</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Username</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Email</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Role</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Giao dịch</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Số dư</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr style="border-bottom:1px solid var(--border-color);transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:1rem;border:none;"><?php echo $user['id']; ?></td>
                                    <td style="padding:1rem;border:none;font-weight:600;color:var(--primary-color);"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td style="padding:1rem;border:none;">
                                        <span style="padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:600;background:<?php echo $user['role'] === 'admin' ? 'rgba(251,191,36,0.1)' : 'rgba(102,126,234,0.1)'; ?>;color:<?php echo $user['role'] === 'admin' ? '#f59e0b' : 'var(--accent-color)'; ?>;">
                                            <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);"><?php echo number_format($user['transaction_count']); ?></td>
                                    <td style="padding:1rem;border:none;font-weight:600;color:<?php echo $user['balance'] >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'; ?>;">
                                        <?php echo number_format($user['balance']); ?>₫
                                    </td>
                                    <td style="padding:1rem;border:none;">
                                        <div style="display:flex;gap:0.5rem;">
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_user_role">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="role" value="admin">
                                                    <button type="submit" style="background:linear-gradient(135deg,#fbbf24,#f59e0b);color:white;border:none;border-radius:6px;padding:0.5rem 0.75rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                        <i class="fas fa-crown"></i> Admin
                                                    </button>
                                                </form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa user này?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" style="background:linear-gradient(135deg,var(--negative-color),#dc2626);color:white;border:none;border-radius:6px;padding:0.5rem 0.75rem;font-size:0.8rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

                 <!-- Settings Tab -->
         <div id="settings-tab" class="tab-content" style="display:none;">
             <!-- Backup Section -->
             <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;margin-bottom:2rem;">
                 <h3 style="margin:0 0 1.5rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                     <i class="fas fa-database" style="color:var(--accent-color);"></i>
                     Backup & Bảo trì
                 </h3>
                 
                 <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">
                     <div class="backup-card" style="background:var(--hover-color);padding:1.5rem;border-radius:12px;border:1px solid var(--border-color);">
                         <h4 style="margin:0 0 1rem;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                             <i class="fas fa-download" style="color:var(--positive-color);"></i>
                             Backup Database
                         </h4>
                         <p style="margin:0 0 1rem;color:var(--secondary-color);font-size:0.9rem;">Tạo bản sao lưu toàn bộ cơ sở dữ liệu</p>
                         <button onclick="backupDatabase()" style="background:linear-gradient(135deg,var(--positive-color),#16a34a);color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;width:100%;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                             <i class="fas fa-download"></i> Tạo Backup
                         </button>
                     </div>
                     
                     <div class="maintenance-card" style="background:var(--hover-color);padding:1.5rem;border-radius:12px;border:1px solid var(--border-color);">
                         <h4 style="margin:0 0 1rem;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                             <i class="fas fa-tools" style="color:#f59e0b;"></i>
                             Chế độ bảo trì
                         </h4>
                         <p style="margin:0 0 1rem;color:var(--secondary-color);font-size:0.9rem;">Tạm dừng hệ thống để bảo trì</p>
                         <button onclick="toggleMaintenance()" id="maintenanceBtn" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;width:100%;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                             <i class="fas fa-tools"></i> Bật bảo trì
                         </button>
                     </div>
                     
                     <div class="stats-card" style="background:var(--hover-color);padding:1.5rem;border-radius:12px;border:1px solid var(--border-color);">
                         <h4 style="margin:0 0 1rem;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                             <i class="fas fa-chart-bar" style="color:var(--accent-color);"></i>
                             Thống kê nâng cao
                         </h4>
                         <p style="margin:0 0 1rem;color:var(--secondary-color);font-size:0.9rem;">Xem báo cáo chi tiết hệ thống</p>
                         <button onclick="loadAdvancedStats()" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;width:100%;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                             <i class="fas fa-chart-line"></i> Xem thống kê
                         </button>
                     </div>
                 </div>
             </div>
             
             <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;">
                 <h3 style="margin:0 0 1.5rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                     <i class="fas fa-cogs" style="color:var(--accent-color);"></i>
                     Cài đặt hệ thống
                 </h3>
                
                <div class="settings-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:1.5rem;">
                    <?php foreach ($settings as $setting): ?>
                        <div class="setting-item" style="background:var(--hover-color);padding:1.5rem;border-radius:12px;border:1px solid var(--border-color);">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_setting">
                                <input type="hidden" name="setting_key" value="<?php echo $setting['setting_key']; ?>">
                                
                                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);">
                                    <?php echo ucfirst(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                
                                <?php if ($setting['description']): ?>
                                    <p style="margin:0 0 1rem;font-size:0.9rem;color:var(--secondary-color);"><?php echo htmlspecialchars($setting['description']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                    <select name="setting_value" style="width:100%;padding:0.75rem;border:2px solid var(--border-color);border-radius:8px;background:var(--card-background);color:var(--primary-color);">
                                        <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Tắt</option>
                                        <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Bật</option>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo $setting['setting_type'] === 'number' ? 'number' : 'text'; ?>" 
                                           name="setting_value" 
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           style="width:100%;padding:0.75rem;border:2px solid var(--border-color);border-radius:8px;background:var(--card-background);color:var(--primary-color);margin-bottom:1rem;">
                                <?php endif; ?>
                                
                                <button type="submit" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;width:100%;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <i class="fas fa-save"></i> Cập nhật
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notifications-tab" class="tab-content" style="display:none;">
            <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;">
                <h3 style="margin:0 0 1rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-envelope" style="color:var(--accent-color);"></i>
                    Gửi Email Thông Báo
                </h3>
                
                <!-- <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);border-radius:12px;padding:1rem;margin-bottom:1.5rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;color:#1e40af;font-weight:600;margin-bottom:0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        <span>📧 Gửi qua Mailgun API</span>
                    </div>
                    <p style="margin:0;color:#374151;font-size:0.9rem;">
                        Thông báo sẽ được gửi qua email đến người dùng sử dụng Mailgun service 
                        (<strong>mail.dinhmanhhung.net</strong>). Đồng thời cũng lưu vào database để hiển thị trong hệ thống.
                    </p>
                </div> -->
                
                <form method="POST" style="max-width:600px;">
                    <input type="hidden" name="action" value="send_notification">
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-heading" style="color:var(--accent-color);"></i>
                            Tiêu đề email
                        </label>
                        <input type="text" name="title" required placeholder="Nhập tiêu đề cho email thông báo..." style="width:100%;padding:1rem;border:2px solid var(--border-color);border-radius:12px;background:var(--card-background);color:var(--primary-color);transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
                    </div>
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-align-left" style="color:var(--accent-color);"></i>
                            Nội dung email
                        </label>
                        <textarea name="message" required rows="5" placeholder="Nhập nội dung chi tiết sẽ gửi qua email..." style="width:100%;padding:1rem;border:2px solid var(--border-color);border-radius:12px;background:var(--card-background);color:var(--primary-color);resize:vertical;transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'"></textarea>
                    </div>
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-palette" style="color:var(--accent-color);"></i>
                            Template màu email
                        </label>
                        <select name="type" style="width:100%;padding:1rem;border:2px solid var(--border-color);border-radius:12px;background:var(--card-background);color:var(--primary-color);transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
                            <option value="info">🔵 Thông tin (Template xanh dương)</option>
                            <option value="success">🟢 Thành công (Template xanh lá)</option>
                            <option value="warning">🟡 Cảnh báo (Template vàng cam)</option>
                            <option value="error">🔴 Lỗi (Template đỏ)</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom:1.5rem;">
                        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);border-radius:12px;padding:1rem;">
                            <label style="display:flex;align-items:center;gap:0.5rem;font-weight:600;color:#047857;cursor:pointer;">
                                <input type="checkbox" name="is_global" value="1" style="margin:0;transform:scale(1.2);">
                                <i class="fas fa-globe"></i>
                                Gửi email cho tất cả người dùng
                            </label>
                            <p style="margin:0.5rem 0 0;color:#374151;font-size:0.85rem;">
                                Nếu chọn, email sẽ được gửi đến tất cả người dùng trong hệ thống
                            </p>
                        </div>
                    </div>
                    
                    <div id="user-select" style="margin-bottom:1.5rem;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-user-circle" style="color:var(--accent-color);"></i>
                            Chọn người dùng cụ thể
                        </label>
                        <select name="user_id" style="width:100%;padding:1rem;border:2px solid var(--border-color);border-radius:12px;background:var(--card-background);color:var(--primary-color);transition:border-color 0.3s;" onfocus="this.style.borderColor='var(--accent-color)'" onblur="this.style.borderColor='var(--border-color)'">
                            <option value="">-- Chọn người dùng để gửi email --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">📧 <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:12px;padding:1.2rem 2rem;font-weight:700;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:0.5rem;font-size:1rem;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.3)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">
                        <i class="fas fa-envelope"></i> Gửi Email Thông Báo
                    </button>
                </form>
            </div>
        </div>

        <!-- Logs Tab -->
        <div id="logs-tab" class="tab-content" style="display:none;">
            <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;">
                <h3 style="margin:0 0 1.5rem;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-history" style="color:var(--accent-color);"></i>
                    Nhật ký hoạt động
                </h3>
                
                <div class="logs-table" style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead style="background:var(--hover-color);">
                            <tr>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Thời gian</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Admin</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Hành động</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">Chi tiết</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr style="border-bottom:1px solid var(--border-color);transition:all 0.2s ease;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td style="padding:1rem;border:none;font-weight:600;color:var(--primary-color);">
                                        <?php echo htmlspecialchars($log['admin_name']); ?>
                                    </td>
                                    <td style="padding:1rem;border:none;">
                                        <span style="padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:500;background:rgba(102,126,234,0.1);color:var(--accent-color);">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($log['details']); ?>">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </td>
                                    <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.8rem;font-family:monospace;">
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'var(--hover-color)';
                btn.style.color = 'var(--primary-color)';
            });
            
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            this.style.background = 'linear-gradient(135deg,var(--accent-color),var(--primary-color))';
            this.style.color = 'white';
            
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });
    
    // Global notification checkbox handler
    const globalCheckbox = document.querySelector('input[name="is_global"]');
    const userSelect = document.getElementById('user-select');
    
    if (globalCheckbox && userSelect) {
        globalCheckbox.addEventListener('change', function() {
            userSelect.style.display = this.checked ? 'none' : 'block';
        });
    }
    
    // Load initial data
    loadDashboardStats();
    loadUsers();
    loadLogs();
    loadNotifications();
    loadSettings();
    
    // Set up auto-refresh for dashboard (every 30 seconds)
    setInterval(loadDashboardStats, 30000);
});

// Admin functions
async function backupDatabase() {
    if (!confirm('Bạn có muốn tạo backup database không?')) return;
    
    try {
        const response = await fetch('api/backup-database.php');
        
        if (response.ok) {
            // Tạo link download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'database_backup_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.sql';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showToast('Backup database thành công!', 'success', {
                icon: 'fas fa-download',
                details: 'File đã được tải xuống'
            });
        } else {
            throw new Error('Lỗi tạo backup');
        }
    } catch (error) {
        console.error('Error backing up database:', error);
        showToast('Lỗi tạo backup database: ' + error.message, 'error');
    }
}

async function toggleMaintenance() {
    const btn = document.getElementById('maintenanceBtn');
    const isMaintenanceMode = btn.textContent.includes('Tắt');
    
    if (!confirm(isMaintenanceMode ? 'Tắt chế độ bảo trì?' : 'Bật chế độ bảo trì?')) return;
    
    try {
        const response = await fetch('api/settings.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                settings: {
                    maintenance_mode: isMaintenanceMode ? '0' : '1'
                }
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            btn.innerHTML = isMaintenanceMode ? 
                '<i class="fas fa-tools"></i> Bật bảo trì' : 
                '<i class="fas fa-times"></i> Tắt bảo trì';
            btn.style.background = isMaintenanceMode ? 
                'linear-gradient(135deg,#f59e0b,#d97706)' : 
                'linear-gradient(135deg,var(--negative-color),#dc2626)';
                
            showToast(
                isMaintenanceMode ? 'Đã tắt chế độ bảo trì' : 'Đã bật chế độ bảo trì', 
                'success',
                { icon: 'fas fa-tools' }
            );
        } else {
            throw new Error(result.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error toggling maintenance:', error);
        showToast('Lỗi thay đổi chế độ bảo trì: ' + error.message, 'error');
    }
}

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch('api/admin-stats.php');
        const stats = await response.json();
        
        if (!response.ok) {
            throw new Error(stats.error || 'Unknown error');
        }
        
        // Update stat cards
        updateStatCard('users-stat', stats.total_users || 0, 'fas fa-users');
        updateStatCard('transactions-stat', stats.total_transactions || 0, 'fas fa-exchange-alt');
        updateStatCard('balance-stat', formatCurrency(stats.net_balance || 0), 'fas fa-wallet');
        updateStatCard('admin-stat', stats.total_admins || 0, 'fas fa-crown');
        
        // Update system status
        const systemStatus = stats.system_status || {};
        const statusElement = document.getElementById('system-status');
        if (statusElement) {
            statusElement.innerHTML = `
                <i class="fas fa-server" style="color: ${systemStatus.database_connected ? 'var(--positive-color)' : 'var(--negative-color)'}"></i>
                Database: ${systemStatus.database_connected ? 'Online' : 'Offline'}
            `;
        }
        
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showToast('Lỗi tải thống kê dashboard: ' + error.message, 'error');
    }
}

// Load users with admin controls
async function loadUsers() {
    try {
        const response = await fetch('api/users.php');
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Unknown error');
        }
        
        const tbody = document.querySelector('#users-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        result.users.forEach(user => {
            const row = document.createElement('tr');
            row.style.cssText = 'border-bottom:1px solid var(--border-color);transition:all 0.2s ease;';
            row.onmouseover = () => row.style.background = 'var(--hover-color)';
            row.onmouseout = () => row.style.background = 'transparent';
            
            const roleColor = user.role === 'admin' ? 'var(--accent-color)' : 'var(--secondary-color)';
            const balanceColor = user.balance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';
            
            row.innerHTML = `
                <td style="padding:1rem;border:none;">
                    <div style="font-weight:600;color:var(--primary-color);">${user.name}</div>
                    <div style="font-size:0.9rem;color:var(--secondary-color);">@${user.username}</div>
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);">${user.email}</td>
                <td style="padding:1rem;border:none;">
                    <span style="padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:500;background:rgba(102,126,234,0.1);color:${roleColor};">
                        ${user.role}
                    </span>
                </td>
                <td style="padding:1rem;border:none;text-align:center;color:var(--primary-color);font-weight:600;">${user.transaction_count}</td>
                <td style="padding:1rem;border:none;text-align:right;color:${balanceColor};font-weight:600;">${formatCurrency(user.balance)}</td>
                <td style="padding:1rem;border:none;">
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                                                 ${user.role === 'user' ? 
                             '<button onclick="promoteUser(' + user.id + ', \'' + user.username + '\')" style="padding:6px 12px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;font-size:0.8rem;cursor:pointer;transition:transform 0.2s ease;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'"><i class="fas fa-crown"></i> Promote</button>' : 
                             (user.id != '<?php echo $_SESSION['user_id']; ?>' ? 
                                 '<button onclick="demoteUser(' + user.id + ', \'' + user.username + '\')" style="padding:6px 12px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--secondary-color),#6b7280);color:white;font-size:0.8rem;cursor:pointer;transition:transform 0.2s ease;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'"><i class="fas fa-user"></i> Demote</button>' : ''
                             )
                         }
                         ${user.id != '<?php echo $_SESSION['user_id']; ?>' ? 
                             '<button onclick="deleteUser(' + user.id + ', \'' + user.username + '\', \'' + user.role + '\')" style="padding:6px 12px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--negative-color),#dc2626);color:white;font-size:0.8rem;cursor:pointer;transition:transform 0.2s ease;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'"><i class="fas fa-trash"></i> Delete</button>' : ''
                         }
                    </div>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
    } catch (error) {
        console.error('Error loading users:', error);
        showToast('Lỗi tải danh sách users: ' + error.message, 'error');
    }
}

// Load logs
async function loadLogs() {
    try {
        const response = await fetch('api/admin-stats.php');
        const stats = await response.json();
        
        if (!response.ok) {
            throw new Error(stats.error || 'Unknown error');
        }
        
        const tbody = document.querySelector('#logs-table tbody');
        if (!tbody || !stats.recent_admin_activities) return;
        
        tbody.innerHTML = '';
        
        stats.recent_admin_activities.forEach(log => {
            const row = document.createElement('tr');
            row.style.cssText = 'border-bottom:1px solid var(--border-color);transition:all 0.2s ease;';
            row.onmouseover = () => row.style.background = 'var(--hover-color)';
            row.onmouseout = () => row.style.background = 'transparent';
            
            row.innerHTML = `
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;">
                    ${new Date(log.created_at).toLocaleString('vi-VN')}
                </td>
                <td style="padding:1rem;border:none;font-weight:600;color:var(--primary-color);">
                    ${log.admin_name || 'Unknown'}
                </td>
                <td style="padding:1rem;border:none;">
                    <span style="padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:500;background:rgba(102,126,234,0.1);color:var(--accent-color);">
                        ${log.action}
                    </span>
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${log.details || ''}">
                    ${log.details || ''}
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.8rem;font-family:monospace;">
                    ${log.ip_address || 'unknown'}
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
    } catch (error) {
        console.error('Error loading logs:', error);
        showToast('Lỗi tải logs: ' + error.message, 'error');
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('api/notifications.php');
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Unknown error');
        }
        
        const tbody = document.querySelector('#notifications-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        result.notifications.forEach(notification => {
            const row = document.createElement('tr');
            row.style.cssText = 'border-bottom:1px solid var(--border-color);transition:all 0.2s ease;';
            row.onmouseover = () => row.style.background = 'var(--hover-color)';
            row.onmouseout = () => row.style.background = 'transparent';
            
            const typeColors = {
                info: 'var(--accent-color)',
                success: 'var(--positive-color)',
                warning: '#f59e0b',
                error: 'var(--negative-color)'
            };
            
            row.innerHTML = `
                <td style="padding:1rem;border:none;">
                    <div style="font-weight:600;color:var(--primary-color);">${notification.title}</div>
                    <div style="font-size:0.9rem;color:var(--secondary-color);margin-top:4px;">${notification.message}</div>
                </td>
                <td style="padding:1rem;border:none;">
                    <span style="padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:500;background:rgba(102,126,234,0.1);color:${typeColors[notification.type] || 'var(--accent-color)'};">
                        ${notification.type}
                    </span>
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;">
                    ${notification.is_global ? 'Global' : (notification.user_name || 'Unknown User')}
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;">
                    ${new Date(notification.created_at).toLocaleString('vi-VN')}
                </td>
                <td style="padding:1rem;border:none;">
                    <button onclick="deleteNotification(${notification.id})" 
                            style="padding:6px 12px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--negative-color),#dc2626);color:white;font-size:0.8rem;cursor:pointer;transition:transform 0.2s ease;" 
                            onmouseover="this.style.transform='scale(1.05)'" 
                            onmouseout="this.style.transform='scale(1)'">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
    } catch (error) {
        console.error('Error loading notifications:', error);
        showToast('Lỗi tải notifications: ' + error.message, 'error');
    }
}

// Load settings
async function loadSettings() {
    try {
        const response = await fetch('api/settings.php');
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Unknown error');
        }
        
        const container = document.getElementById('settings-grid');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(result.settings).forEach(([key, setting]) => {
            const card = document.createElement('div');
            card.style.cssText = 'background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;padding:1.5rem;transition:all 0.2s ease;';
            card.onmouseover = () => card.style.transform = 'translateY(-2px)';
            card.onmouseout = () => card.style.transform = 'translateY(0)';
            
            let inputElement = '';
            
            switch (setting.type) {
                case 'boolean':
                    inputElement = `
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" ${setting.value === '1' ? 'checked' : ''} 
                                   onchange="updateSetting('${key}', this.checked)"
                                   style="width:18px;height:18px;accent-color:var(--accent-color);">
                            <span style="color:var(--secondary-color);font-size:0.9rem;">Enable</span>
                        </label>
                    `;
                    break;
                case 'number':
                    inputElement = `
                        <input type="number" value="${setting.value}" 
                               onchange="updateSetting('${key}', this.value)"
                               style="width:100%;padding:8px 12px;border:1px solid var(--border-color);border-radius:8px;background:var(--input-background);color:var(--primary-color);font-size:0.9rem;">
                    `;
                    break;
                default:
                    inputElement = `
                        <input type="text" value="${setting.value}" 
                               onchange="updateSetting('${key}', this.value)"
                               style="width:100%;padding:8px 12px;border:1px solid var(--border-color);border-radius:8px;background:var(--input-background);color:var(--primary-color);font-size:0.9rem;">
                    `;
            }
            
            card.innerHTML = `
                <h4 style="margin:0 0 8px;color:var(--primary-color);font-size:1rem;font-weight:600;">${key.replace(/_/g, ' ').toUpperCase()}</h4>
                <p style="margin:0 0 12px;color:var(--secondary-color);font-size:0.85rem;">${setting.description}</p>
                ${inputElement}
            `;
            
            container.appendChild(card);
        });
        
    } catch (error) {
        console.error('Error loading settings:', error);
        showToast('Lỗi tải settings: ' + error.message, 'error');
    }
}

async function loadAdvancedStats() {
    try {
        const response = await fetch('api/admin-stats.php');
        const stats = await response.json();
        
        if (!response.ok) {
            throw new Error(stats.error || 'Unknown error');
        }
        
        showAdvancedStatsModal(stats);
    } catch (error) {
        console.error('Error loading advanced stats:', error);
        showToast('Lỗi tải thống kê: ' + error.message, 'error');
    }
}

// Helper functions
function updateStatCard(cardId, value, icon) {
    const card = document.getElementById(cardId);
    if (card) {
        card.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(59,130,246,0.1));display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);animation:iconFloat 6s ease-in-out infinite;">
                    <i class="${icon}" style="font-size:1.5rem;color:var(--accent-color);"></i>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:2.2rem;font-weight:800;color:var(--primary-color);line-height:1;margin-bottom:4px;">${value}</div>
                </div>
            </div>
        `;
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// User management functions
async function promoteUser(userId, username) {
    if (!confirm(`Promote user "${username}" to admin?`)) return;
    
    try {
        const response = await fetch('api/users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                action: 'promote_to_admin'
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(result.message, 'success');
            loadUsers(); // Reload users table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error promoting user: ' + error.message, 'error');
    }
}

async function demoteUser(userId, username) {
    if (!confirm(`Demote admin "${username}" to regular user?`)) return;
    
    try {
        const response = await fetch('api/users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                action: 'demote_to_user'
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(result.message, 'success');
            loadUsers(); // Reload users table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error demoting user: ' + error.message, 'error');
    }
}

async function deleteUser(userId, username, role) {
    const isAdmin = role === 'admin';
    const confirmMessage = isAdmin ? 
        `Delete admin "${username}"? This action cannot be undone and requires confirmation.` :
        `Delete user "${username}"? This will also delete all their transactions and data.`;
    
    if (!confirm(confirmMessage)) return;
    
    try {
        const requestBody = {
            user_id: userId
        };
        
        if (isAdmin) {
            requestBody.confirm_admin_delete = true;
        }
        
        const response = await fetch('api/users.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(result.message, 'success');
            loadUsers(); // Reload users table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error deleting user: ' + error.message, 'error');
    }
}

// Notification functions
async function sendNotification() {
    const form = document.getElementById('notification-form');
    const formData = new FormData(form);
    
    const title = formData.get('title');
    const message = formData.get('message');
    const type = formData.get('type');
    const isGlobal = formData.has('is_global');
    const userId = isGlobal ? null : formData.get('user_id');
    
    if (!title || !message) {
        showToast('Please fill in title and message', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title,
                message: message,
                type: type,
                user_id: userId,
                is_global: isGlobal
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(result.message, 'success');
            form.reset();
            loadNotifications(); // Reload notifications table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error sending notification: ' + error.message, 'error');
    }
}

async function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) return;
    
    try {
        const response = await fetch('api/notifications.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                notification_id: notificationId
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(result.message, 'success');
            loadNotifications(); // Reload notifications table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error deleting notification: ' + error.message, 'error');
    }
}

// Settings functions
async function updateSetting(key, value) {
    try {
        const response = await fetch('api/settings.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                settings: {
                    [key]: value
                }
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast(`Updated ${key} setting`, 'success', { details: `New value: ${value}` });
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showToast('Error updating setting: ' + error.message, 'error');
    }
}

function showAdvancedStatsModal(stats) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 2000;
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease-out;
    `;
    
    modal.innerHTML = `
        <div style="
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.4s ease-out;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: var(--primary-color); font-size: 1.3rem; font-weight: 700;">
                    <i class="fas fa-chart-line" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                    Thống kê nâng cao
                </h3>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: var(--secondary-color);
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0.5rem;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: var(--hover-color); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--positive-color);">${stats.new_users_today}</div>
                    <div style="font-size: 0.9rem; color: var(--secondary-color);">Users hôm nay</div>
                </div>
                <div style="background: var(--hover-color); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent-color);">${stats.transactions_today}</div>
                    <div style="font-size: 0.9rem; color: var(--secondary-color);">Giao dịch hôm nay</div>
                </div>
                <div style="background: var(--hover-color); padding: 1rem; border-radius: 8px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b;">${stats.total_bank_accounts}</div>
                    <div style="font-size: 0.9rem; color: var(--secondary-color);">Tài khoản ngân hàng</div>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 1rem; color: var(--primary-color);">Top Users</h4>
                <div style="max-height: 200px; overflow-y: auto;">
                    ${stats.top_users.map(user => `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-weight: 600; color: var(--primary-color);">${user.name}</div>
                                <div style="font-size: 0.8rem; color: var(--secondary-color);">@${user.username}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 600; color: var(--accent-color);">${user.transaction_count} giao dịch</div>
                                <div style="font-size: 0.8rem; color: ${user.balance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)'};">
                                    ${new Intl.NumberFormat('vi-VN').format(user.balance)}₫
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="background: var(--hover-color); padding: 1rem; border-radius: 8px;">
                    <h5 style="margin: 0 0 0.5rem; color: var(--primary-color);">Thu nhập</h5>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--positive-color);">
                        ${new Intl.NumberFormat('vi-VN').format(stats.transaction_stats.total_income)}₫
                    </div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color);">
                        ${stats.transaction_stats.income_count} giao dịch
                    </div>
                </div>
                <div style="background: var(--hover-color); padding: 1rem; border-radius: 8px;">
                    <h5 style="margin: 0 0 0.5rem; color: var(--primary-color);">Chi tiêu</h5>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--negative-color);">
                        ${new Intl.NumberFormat('vi-VN').format(stats.transaction_stats.total_expense)}₫
                    </div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color);">
                        ${stats.transaction_stats.expense_count} giao dịch
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Toast notification system
function showToast(message, type = 'info', options = {}) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:12px;max-width:400px;pointer-events:none;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const typeStyles = {
        success: {
            background: 'linear-gradient(135deg, rgba(34,197,94,0.95), rgba(16,185,129,0.95))',
            borderColor: 'var(--positive-color)',
            iconColor: '#ffffff',
            progressColor: 'rgba(255,255,255,0.3)'
        },
        error: {
            background: 'linear-gradient(135deg, rgba(239,68,68,0.95), rgba(220,38,38,0.95))',
            borderColor: 'var(--negative-color)',
            iconColor: '#ffffff',
            progressColor: 'rgba(255,255,255,0.3)'
        },
        warning: {
            background: 'linear-gradient(135deg, rgba(245,158,11,0.95), rgba(217,119,6,0.95))',
            borderColor: '#f59e0b',
            iconColor: '#ffffff',
            progressColor: 'rgba(255,255,255,0.3)'
        },
        info: {
            background: 'linear-gradient(135deg, rgba(102,126,234,0.95), rgba(59,130,246,0.95))',
            borderColor: 'var(--accent-color)',
            iconColor: '#ffffff',
            progressColor: 'rgba(255,255,255,0.3)'
        }
    };

    const style = typeStyles[type] || typeStyles.info;
    const icon = options.icon || (type === 'success' ? 'fas fa-check-circle' : 
                                  type === 'error' ? 'fas fa-times-circle' : 
                                  type === 'warning' ? 'fas fa-exclamation-triangle' : 
                                  'fas fa-info-circle');

    toast.style.cssText = `
        background: ${style.background};
        color: white;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        border: 1px solid ${style.borderColor};
        box-shadow: 0 12px 40px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1) inset;
        backdrop-filter: blur(12px);
        transform: translateX(400px);
        transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        pointer-events: auto;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        max-width: 380px;
        min-width: 300px;
        margin-bottom: 12px;
        animation: toastSlideIn 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    `;

    const content = `
        <div style="display: flex; align-items: flex-start; gap: 12px; position: relative; z-index: 2;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; backdrop-filter: blur(8px); animation: toastIconPulse 2s ease-in-out infinite;">
                <i class="${icon}" style="font-size: 1.1rem; color: ${style.iconColor};"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 4px; line-height: 1.3;">${message}</div>
                ${options.details ? `<div style="font-size: 0.8rem; opacity: 0.8; margin-top: 4px;">${options.details}</div>` : ''}
            </div>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: rgba(255,255,255,0.8); font-size: 1rem; cursor: pointer; padding: 4px; border-radius: 50%; transition: all 0.3s ease; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-times" style="font-size: 0.7rem;"></i>
            </button>
        </div>
        <div style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${style.progressColor}; border-radius: 0 0 16px 16px; animation: toastProgress 5s linear forwards; transform-origin: left;"></div>
    `;

    toast.innerHTML = content;
    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);

    return toast;
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes cardSlideIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-50px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes patternMegaFloat {
        0%, 100% { transform: translateX(0px) translateY(0px); }
        33% { transform: translateX(30px) translateY(-30px); }
        66% { transform: translateX(-20px) translateY(20px); }
    }
    
    @keyframes floatDashboard1 {
        0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
        33% { transform: translateY(-20px) translateX(10px) rotate(120deg); }
        66% { transform: translateY(10px) translateX(-15px) rotate(240deg); }
    }
    
    @keyframes floatDashboard2 {
        0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
        50% { transform: translateY(-25px) translateX(20px) rotate(180deg); }
    }
    
    @keyframes floatDashboard3 {
        0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
        33% { transform: translateY(15px) translateX(-10px) scale(1.1); }
        66% { transform: translateY(-10px) translateX(25px) scale(0.9); }
    }
    
    @keyframes megaTitleGlow {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.7; }
    }
    
    .tab-button:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
    }
    
    .stat-card:hover {
        transform: translateY(-6px) scale(1.02) !important;
        box-shadow: 0 12px 40px rgba(0,0,0,0.15) !important;
    }
    
    @keyframes toastSlideIn {
        0% { 
            transform: translateX(400px) scale(0.8); 
            opacity: 0; 
        }
        60% { 
            transform: translateX(-20px) scale(1.05); 
            opacity: 1; 
        }
        80% { 
            transform: translateX(10px) scale(0.98); 
        }
        100% { 
            transform: translateX(0) scale(1); 
            opacity: 1; 
        }
    }
    
    @keyframes toastIconPulse {
        0%, 100% { 
            transform: scale(1); 
            opacity: 1; 
        }
        50% { 
            transform: scale(1.1); 
            opacity: 0.8; 
        }
    }
    
    @keyframes toastProgress {
        0% { 
            transform: scaleX(1); 
        }
        100% { 
            transform: scaleX(0); 
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?> 