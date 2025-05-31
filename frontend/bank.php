<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Quản lý Ngân hàng';

// Xử lý form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_bank_account':
                $bankUsername = $_POST['bank_username'];
                $bankPassword = $_POST['bank_password'];
                $accountName = $_POST['account_name'] ?? '';
                $bankType = $_POST['bank_type'] ?? 'mbbank';
                
                // Mã hóa password (sử dụng base64 encode đơn giản)
                $encodedPassword = base64_encode($bankPassword);
                
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO bank_accounts (user_id, bank_username, bank_password, account_name) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        bank_password = VALUES(bank_password),
                        account_name = VALUES(account_name),
                        updated_at = NOW()
                    ");
                    $stmt->execute([$userId, $bankUsername, $encodedPassword, $accountName]);
                    $success_message = "Lưu tài khoản ngân hàng thành công!";
                } catch (PDOException $e) {
                    $error_message = "Lỗi lưu tài khoản: " . $e->getMessage();
                }
                break;
                
            case 'delete_bank_account':
                $bankId = $_POST['bank_id'];
                try {
                    $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE id = ? AND user_id = ?");
                    $stmt->execute([$bankId, $userId]);
                    $success_message = "Xóa tài khoản ngân hàng thành công!";
                } catch (PDOException $e) {
                    $error_message = "Lỗi xóa tài khoản: " . $e->getMessage();
                }
                break;
        }
    }
}

// Lấy danh sách bank accounts
$bankAccounts = [];
try {
    $stmt = $conn->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $bankAccounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Lỗi tải danh sách tài khoản: " . $e->getMessage();
}

?>

<?php include 'includes/header.php'; ?>

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
        </div>
        
        <!-- Hero Glow Effect -->
        <div class="hero-glow" style="position:absolute;top:50%;left:50%;width:800px;height:400px;background:radial-gradient(ellipse,rgba(102,126,234,0.08) 0%,transparent 70%);transform:translate(-50%,-50%);animation:heroGlowPulse 8s ease-in-out infinite;pointer-events:none;"></div>
        
        <div class="header-content" style="max-width:1400px;margin:0 auto;padding:0 2rem;position:relative;z-index:3;">
            <div class="header-left" style="animation:slideInLeft 1s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <div class="title-container" style="position:relative;margin-bottom:16px;">
                    <h1 class="magical-dashboard-title" style="font-size:2.8rem;font-weight:800;margin:0;color:var(--primary-color);position:relative;display:inline-block;background:linear-gradient(135deg,var(--primary-color),var(--accent-color),var(--positive-color));background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                        🏦 Quản lý Ngân hàng
                        <span class="mega-title-glow" style="position:absolute;top:-10px;left:-10px;right:-10px;bottom:-10px;background:linear-gradient(45deg,transparent,rgba(102,126,234,0.15),transparent,rgba(34,197,94,0.1),transparent);animation:megaTitleGlow 4s ease-in-out infinite;z-index:-1;border-radius:12px;"></span>
                    </h1>
                    <div class="title-decoration" style="position:absolute;top:-5px;right:-20px;width:20px;height:20px;background:linear-gradient(45deg,var(--accent-color),var(--positive-color));border-radius:50%;animation:decorationSpin 6s linear infinite;box-shadow:0 0 15px rgba(102,126,234,0.4);"></div>
                </div>
                <p class="magical-subtitle" style="font-size:1.1rem;color:var(--secondary-color);margin:0 0 16px;animation:fadeInUp 1.2s ease-out 0.3s both;font-weight:500;">Kết nối và theo dõi tài khoản ngân hàng của bạn một cách thông minh</p>
                <div class="user-info-enhanced" style="display:flex;align-items:center;gap:20px;font-size:0.95rem;color:var(--secondary-color);animation:fadeInUp 1.2s ease-out 0.6s both;flex-wrap:wrap;">
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(102,126,234,0.05);border:1px solid rgba(102,126,234,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(102,126,234,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)'" onmouseout="this.style.background='rgba(102,126,234,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                        <i class="fas fa-university" style="color:var(--accent-color);animation:userPulse 3s ease-in-out infinite;"></i> 
                        <span style="font-weight:600;color:var(--primary-color);">MB Bank</span>
                    </div>
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(34,197,94,0.05);border:1px solid rgba(34,197,94,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(34,197,94,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(34,197,94,0.15)'" onmouseout="this.style.background='rgba(34,197,94,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                        <i class="fas fa-shield-alt" style="color:var(--positive-color);animation:calendarPulse 3s ease-in-out infinite 0.5s;"></i> 
                        <span style="font-weight:500;">Bảo mật</span>
                    </div>
                    <div class="info-item-enhanced" style="display:flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.1);border-radius:20px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);" onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.transform='translateY(-3px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(239,68,68,0.15)'" onmouseout="this.style.background='rgba(239,68,68,0.05)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                        <i class="fas fa-sync-alt" style="color:var(--negative-color);animation:clockTick 2s ease-in-out infinite;"></i> 
                        <span style="font-weight:500;">Real-time</span>
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

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;animation:slideInDown 0.4s ease-out;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Bank Account Form Card -->
        <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;margin-bottom:2rem;box-shadow:0 8px 32px rgba(0,0,0,0.12);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.12)';this.querySelector('.card-glow').style.opacity='0'">
            <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(102,126,234,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
            
            <div class="card-header" style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;position:relative;z-index:2;">
                <div class="card-icon" style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                    <i class="fas fa-plus-circle" style="color:var(--accent-color);font-size:1.5rem;"></i>
                </div>
                <div>
                    <h2 style="margin:0;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Thêm tài khoản ngân hàng</h2>
                    <p style="margin:4px 0 0;color:var(--secondary-color);font-size:0.9rem;">Kết nối tài khoản MB Bank của bạn</p>
                </div>
            </div>

            <form method="POST" class="magical-form" style="position:relative;z-index:2;">
                <input type="hidden" name="action" value="save_bank_account">
                <input type="hidden" name="bank_type" value="mbbank">
                
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;margin-bottom:2rem;">
                    <div class="form-group" style="position:relative;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);font-size:0.9rem;">
                            <i class="fas fa-university" style="margin-right:8px;color:var(--accent-color);"></i>
                            Ngân hàng
                        </label>
                        <div class="select-wrapper" style="position:relative;">
                            <select name="bank_type" disabled style="width:100%;padding:0.875rem 1rem;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;transition:all 0.3s ease;background:var(--card-background);color:var(--primary-color);appearance:none;background-repeat:no-repeat;background-position:right 1rem center;background-size:12px;">
                                <option value="mbbank" selected>MB Bank (Military Bank)</option>
                            </select>
                            <div class="select-decoration" style="position:absolute;top:50%;right:1rem;transform:translateY(-50%);pointer-events:none;color:var(--secondary-color);">
                                <i class="fas fa-chevron-down" style="font-size:0.8rem;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="position:relative;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);font-size:0.9rem;">
                            <i class="fas fa-user" style="margin-right:8px;color:var(--accent-color);"></i>
                            Tên đăng nhập
                        </label>
                        <input type="text" name="bank_username" required 
                               placeholder="Nhập username MB Bank"
                               style="width:100%;padding:0.875rem 1rem;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;transition:all 0.3s ease;background:var(--card-background);"
                               onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'" 
                               onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
                    </div>

                    <div class="form-group" style="position:relative;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);font-size:0.9rem;">
                            <i class="fas fa-lock" style="margin-right:8px;color:var(--accent-color);"></i>
                            Mật khẩu
                        </label>
                        <input type="password" name="bank_password" required 
                               placeholder="Nhập mật khẩu MB Bank"
                               style="width:100%;padding:0.875rem 1rem;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;transition:all 0.3s ease;background:var(--card-background);"
                               onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'" 
                               onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
                    </div>

                    <div class="form-group" style="position:relative;">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);font-size:0.9rem;">
                            <i class="fas fa-tag" style="margin-right:8px;color:var(--accent-color);"></i>
                            Tên hiển thị (tùy chọn)
                        </label>
                        <input type="text" name="account_name" 
                               placeholder="VD: Tài khoản chính, Tiết kiệm..."
                               style="width:100%;padding:0.875rem 1rem;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;transition:all 0.3s ease;background:var(--card-background);"
                               onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'" 
                               onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
                    </div>
                </div>
                
                <div class="form-actions" style="display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="submit" class="magical-button primary" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:12px;padding:0.875rem 2rem;font-weight:700;font-size:1rem;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;display:flex;align-items:center;gap:0.5rem;" onmouseover="this.style.background='linear-gradient(135deg,var(--primary-color),var(--positive-color))';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--accent-color),var(--primary-color))';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                        <i class="fas fa-save" style="animation:addIconBounce 3s ease-in-out infinite;"></i>
                        <span>Lưu tài khoản</span>
                        <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Saved Bank Accounts -->
        <div class="magical-card" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;margin-bottom:2rem;box-shadow:0 8px 32px rgba(0,0,0,0.12);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out 0.1s both;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.12)'">
            <div class="card-header" style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;">
                <div class="card-icon" style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(34,197,94,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite 0.5s;">
                    <i class="fas fa-list" style="color:var(--positive-color);font-size:1.5rem;"></i>
                </div>
                <div>
                    <h2 style="margin:0;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Tài khoản đã lưu</h2>
                    <p style="margin:4px 0 0;color:var(--secondary-color);font-size:0.9rem;"><?php echo count($bankAccounts); ?> tài khoản ngân hàng</p>
                </div>
            </div>

            <?php if (empty($bankAccounts)): ?>
                <div class="empty-state" style="text-align:center;padding:3rem;color:var(--secondary-color);animation:fadeInUp 0.6s ease-out;">
                    <div class="empty-icon" style="width:80px;height:80px;margin:0 auto 1rem;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-university" style="font-size:2rem;color:var(--accent-color);"></i>
                    </div>
                    <h3 style="margin:0 0 0.5rem;color:var(--primary-color);">Chưa có tài khoản ngân hàng nào</h3>
                    <p style="margin:0;font-size:0.9rem;">Thêm tài khoản ngân hàng để bắt đầu sử dụng</p>
                </div>
            <?php else: ?>
                <div class="bank-accounts-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.5rem;">
                    <?php foreach ($bankAccounts as $index => $account): ?>
                        <div class="bank-account-card magical-card" style="background:var(--hover-color);border:2px solid var(--border-color);border-radius:12px;padding:1.5rem;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;animation:cardSlideIn 0.6s ease-out <?php echo $index * 0.1; ?>s both;" onmouseover="this.style.transform='translateY(-6px) scale(1.02)';this.style.boxShadow='0 12px 30px rgba(0,0,0,0.15)';this.style.borderColor='var(--accent-color)'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none';this.style.borderColor='var(--border-color)'">
                            <div class="account-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                                <div class="account-info">
                                    <div class="bank-logo" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                                        <div style="width:32px;height:32px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                            <span style="color:white;font-weight:bold;font-size:0.8rem;">MB</span>
                                        </div>
                                        <span style="font-weight:600;color:var(--primary-color);font-size:0.9rem;">MB Bank</span>
                                    </div>
                                    <h3 style="margin:0 0 0.5rem;color:var(--primary-color);font-size:1.1rem;font-weight:700;">
                                        <?php echo htmlspecialchars($account['account_name'] ?: $account['bank_username']); ?>
                                    </h3>
                                    <p style="margin:0 0 0.25rem;color:var(--secondary-color);font-size:0.9rem;font-family:monospace;">
                                        <i class="fas fa-user" style="margin-right:6px;color:var(--accent-color);"></i>
                                        <?php echo htmlspecialchars($account['bank_username']); ?>
                                    </p>
                                    <small style="display:block;color:var(--secondary-color);font-size:0.8rem;opacity:0.8;">
                                        <i class="fas fa-calendar" style="margin-right:4px;"></i>
                                        Tạo: <?php echo date('d/m/Y H:i', strtotime($account['created_at'])); ?>
                                    </small>
                                    <?php if ($account['last_sync']): ?>
                                        <small style="display:block;color:var(--positive-color);font-size:0.8rem;margin-top:2px;">
                                            <i class="fas fa-sync" style="margin-right:4px;"></i>
                                            Sync: <?php echo date('d/m/Y H:i', strtotime($account['last_sync'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="account-actions" style="display:flex;gap:0.75rem;margin-top:1rem;">
                                <button class="magical-button connect" onclick="connectBank(<?php echo $account['id']; ?>)" style="flex:1;background:linear-gradient(135deg,var(--positive-color),#16a34a);color:white;border:none;border-radius:8px;padding:0.75rem 1rem;font-weight:600;font-size:0.9rem;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:0.5rem;" onmouseover="this.style.background='linear-gradient(135deg,#16a34a,var(--positive-color))';this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(34,197,94,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--positive-color),#16a34a)';this.style.transform='translateY(0)';this.style.boxShadow='none'">
                                    <i class="fas fa-plug" style="animation:iconSpin 4s ease-in-out infinite;"></i>
                                    Kết nối
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?')">
                                    <input type="hidden" name="action" value="delete_bank_account">
                                    <input type="hidden" name="bank_id" value="<?php echo $account['id']; ?>">
                                    <button type="submit" class="magical-button danger" style="background:linear-gradient(135deg,var(--negative-color),#dc2626);color:white;border:none;border-radius:8px;padding:0.75rem;font-weight:600;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='linear-gradient(135deg,#dc2626,var(--negative-color))';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 6px 20px rgba(239,68,68,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--negative-color),#dc2626)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                        <i class="fas fa-trash" style="font-size:0.9rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>



        <!-- Bank Data Display -->
        <div id="bankDataContainer" class="magical-card" style="display:none;background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;box-shadow:0 8px 32px rgba(0,0,0,0.12);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out;">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
                <div style="display:flex;align-items:center;gap:1rem;">
                    <div class="card-icon" style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,rgba(102,126,234,0.1),rgba(102,126,234,0.2));display:flex;align-items:center;justify-content:center;animation:iconFloat 3s ease-in-out infinite;">
                        <i class="fas fa-chart-line" style="color:var(--accent-color);font-size:1.5rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin:0;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Thông tin ngân hàng</h2>
                        <p style="margin:4px 0 0;color:var(--secondary-color);font-size:0.9rem;">Dữ liệu real-time từ MB Bank</p>
                    </div>
                </div>
                <button id="importTransactionsBtn" class="magical-button success" style="background:linear-gradient(135deg,var(--positive-color),#16a34a);color:white;border:none;border-radius:12px;padding:0.875rem 1.5rem;font-weight:700;font-size:0.9rem;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:0.5rem;" onmouseover="this.style.background='linear-gradient(135deg,#16a34a,var(--positive-color))';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(34,197,94,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--positive-color),#16a34a)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-download" style="animation:addIconBounce 3s ease-in-out infinite;"></i>
                    Import giao dịch
                </button>
            </div>

            <!-- Balance Summary -->
            <div class="balance-summary" style="background:linear-gradient(135deg,var(--primary-color),var(--accent-color));color:white;padding:2rem;border-radius:12px;text-align:center;margin-bottom:2rem;position:relative;overflow:hidden;">
                <div class="balance-pattern" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.1;background:radial-gradient(circle at 20% 80%, white 2px, transparent 2px), radial-gradient(circle at 80% 20%, white 1.5px, transparent 1.5px);background-size:40px 40px, 60px 60px;animation:patternFloat 20s ease-in-out infinite;"></div>
                <h3 style="margin:0 0 1rem;opacity:0.9;font-weight:600;position:relative;z-index:2;">Tổng số dư</h3>
                <div class="amount" id="totalBalance" style="font-size:2.5rem;font-weight:800;margin-bottom:0.5rem;position:relative;z-index:2;animation:numberPulse 2s ease-in-out infinite;">0 ₫</div>
                <small id="accountCount" style="opacity:0.8;position:relative;z-index:2;">0 tài khoản</small>
            </div>

            <!-- Accounts List -->
            <div id="accountsList" class="accounts-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-bottom:2rem;">
                <!-- Accounts will be loaded here -->
            </div>

            <!-- Transactions -->
            <div class="transactions-section">
                <div class="section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                    <div style="display:flex;align-items:center;gap:2rem;">
                        <h3 style="margin:0;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-exchange-alt" style="color:var(--accent-color);"></i>
                            Giao dịch gần đây
                        </h3>
                        <div class="transaction-legend" style="display:flex;gap:1rem;font-size:0.85rem;">
                            <span class="legend-item" style="display:flex;align-items:center;gap:0.5rem;">
                                <span style="width:12px;height:12px;background:var(--positive-color);border-radius:50%;"></span>
                                Đã import
                            </span>
                            <span class="legend-item" style="display:flex;align-items:center;gap:0.5rem;">
                                <span style="width:12px;height:12px;background:var(--negative-color);border-radius:50%;"></span>
                                Chưa import
                            </span>
                        </div>
                    </div>
                    <div class="batch-import-actions" style="display:flex;gap:12px;align-items:center;">
                        <div class="select-all-container" style="display:none;align-items:center;gap:8px;">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:0.85rem;color:var(--secondary-color);">
                                <input type="checkbox" id="selectAllTransactions" style="transform:scale(1.1);">
                                <span>Chọn tất cả</span>
                            </label>
                            <span id="selectedCount" style="font-size:0.8rem;color:var(--accent-color);font-weight:600;">0 đã chọn</span>
                        </div>
                        <button id="batchImportBtn" class="magical-button success" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:8px;padding:0.75rem 1.25rem;font-weight:600;font-size:0.85rem;transition:all 0.3s ease;display:none;align-items:center;gap:8px;" onmouseover="this.style.background='linear-gradient(135deg,var(--primary-color),var(--positive-color))';this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(102,126,234,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--accent-color),var(--primary-color))';this.style.transform='translateY(0)';this.style.boxShadow='none'">
                            <i class="fas fa-download"></i>
                            <span id="batchImportText">Import hàng loạt</span>
                        </button>
                    </div>
                </div>
                
                <div class="transactions-table" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;">
                    <table style="width:100%;border-collapse:collapse;" id="transactionsTable">
                        <thead style="background:var(--hover-color);">
                            <tr>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">
                                    <input type="checkbox" id="selectAllVisible" style="transform:scale(1.1);" title="Chọn/bỏ chọn tất cả giao dịch chưa import">
                                </th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Trạng thái</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Ngày</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Mô tả</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Số tiền</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Loại</th>
                                <th style="padding:1rem;text-align:left;font-weight:600;color:var(--primary-color);border:none;font-size:0.9rem;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsBody">
                            <!-- Transactions will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading-state magical-card" style="display:none;text-align:center;padding:4rem;background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.12);position:relative;overflow:hidden;">
            <div class="loading-spinner" style="width:50px;height:50px;border:4px solid var(--border-color);border-top:4px solid var(--accent-color);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 1.5rem auto;"></div>
            <h3 style="margin:0 0 0.5rem;color:var(--primary-color);">Đang kết nối ngân hàng...</h3>
            <p style="margin:0;color:var(--secondary-color);font-size:0.9rem;">Vui lòng chờ trong giây lát</p>
        </div>

        <!-- Category Selection Modal -->
        <div id="categoryModal" class="modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:1000;backdrop-filter:blur(8px);animation:fadeIn 0.3s ease-out;">
            <div class="modal-content magical-card" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:2rem;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:modalSlideIn 0.4s ease-out;">
                <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border-color);">
                    <h3 style="margin:0;color:var(--primary-color);font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fas fa-tags" style="color:var(--accent-color);"></i>
                        Chọn thể loại
                    </h3>
                    <button id="closeCategoryModal" style="background:none;border:none;color:var(--secondary-color);font-size:1.5rem;cursor:pointer;padding:0.5rem;border-radius:50%;transition:all 0.3s ease;" onmouseover="this.style.background='var(--hover-color)';this.style.color='var(--primary-color)'" onmouseout="this.style.background='none';this.style.color='var(--secondary-color)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="transaction-info" style="background:var(--hover-color);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                            <span style="font-weight:600;color:var(--primary-color);" id="modalTransactionAmount">0 ₫</span>
                            <span style="padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:500;" id="modalTransactionType"></span>
                        </div>
                        <p style="margin:0;color:var(--secondary-color);font-size:0.9rem;" id="modalTransactionDescription"></p>
                        <small style="color:var(--secondary-color);opacity:0.8;" id="modalTransactionDate"></small>
                    </div>
                    
                    <div class="category-selection">
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:var(--primary-color);">
                            <i class="fas fa-list" style="margin-right:8px;color:var(--accent-color);"></i>
                            Chọn thể loại:
                        </label>
                        <select id="categorySelect" style="width:100%;padding:0.875rem 1rem;border:2px solid var(--border-color);border-radius:12px;font-size:1rem;transition:all 0.3s ease;background:var(--card-background);color:var(--primary-color);" onfocus="this.style.borderColor='var(--accent-color)';this.style.boxShadow='0 0 0 3px rgba(102,126,234,0.1)'" onblur="this.style.borderColor='var(--border-color)';this.style.boxShadow='none'">
                            <option value="">-- Chọn thể loại --</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer" style="display:flex;gap:1rem;justify-content:flex-end;margin-top:2rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                    <button id="cancelImport" class="magical-button secondary" style="background:var(--hover-color);color:var(--secondary-color);border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;" onmouseover="this.style.background='var(--border-color)';this.style.color='var(--primary-color)'" onmouseout="this.style.background='var(--hover-color)';this.style.color='var(--secondary-color)'">
                        Hủy
                    </button>
                    <button id="confirmImport" class="magical-button primary" style="background:linear-gradient(135deg,var(--positive-color),#16a34a);color:white;border:none;border-radius:8px;padding:0.75rem 1.5rem;font-weight:600;transition:all 0.3s ease;display:flex;align-items:center;gap:0.5rem;" onmouseover="this.style.background='linear-gradient(135deg,#16a34a,var(--positive-color))';this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(34,197,94,0.4)'" onmouseout="this.style.background='linear-gradient(135deg,var(--positive-color),#16a34a)';this.style.transform='translateY(0)';this.style.boxShadow='none'">
                        <i class="fas fa-download"></i>
                        Import
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification Container -->
        <div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:12px;max-width:400px;pointer-events:none;">
            <!-- Toast notifications will be dynamically added here -->
        </div>
    </main>
</div>

<script>
// Global variables
let currentBankData = null;
let importedTransactions = new Set(); // Track imported transactions
let categories = { income: [], expense: [] };
let currentTransactionForImport = null;

// Toast notification system
function showToast(message, type = 'info', options = {}) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Define styles based on type
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
            <div style="
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                background: rgba(255,255,255,0.2); 
                display: flex; 
                align-items: center; 
                justify-content: center;
                flex-shrink: 0;
                backdrop-filter: blur(8px);
                animation: toastIconPulse 2s ease-in-out infinite;
            ">
                <i class="${icon}" style="font-size: 1.1rem; color: ${style.iconColor};"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 4px; line-height: 1.3;">
                    ${message}
                </div>
                ${options.category ? `
                    <div style="font-size: 0.8rem; opacity: 0.9; margin-bottom: 2px;">
                        <i class="fas fa-tag" style="margin-right: 6px;"></i>
                        Thể loại: <strong>${options.category}</strong>
                    </div>
                ` : ''}
                ${options.amount ? `
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 600;">
                        <i class="fas fa-money-bill-wave" style="margin-right: 6px;"></i>
                        ${options.amount}
                    </div>
                ` : ''}
                ${options.details ? `
                    <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 4px;">
                        ${options.details}
                    </div>
                ` : ''}
            </div>
            <button onclick="dismissToast(this.parentElement.parentElement)" style="
                background: none;
                border: none;
                color: rgba(255,255,255,0.8);
                font-size: 1rem;
                cursor: pointer;
                padding: 4px;
                border-radius: 50%;
                transition: all 0.3s ease;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            " onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.color='white'" onmouseout="this.style.background='none'; this.style.color='rgba(255,255,255,0.8)'">
                <i class="fas fa-times" style="font-size: 0.7rem;"></i>
            </button>
        </div>
        
        <!-- Progress bar -->
        <div style="
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: ${style.progressColor};
            border-radius: 0 0 16px 16px;
            animation: toastProgress 5s linear forwards;
            transform-origin: left;
        "></div>
        
        <!-- Shine effect -->
        <div style="
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: toastShine 3s ease-in-out infinite;
            pointer-events: none;
        "></div>
        
        <!-- Floating particles -->
        <div style="position: absolute; top: 15%; left: 20%; width: 4px; height: 4px; background: rgba(255,255,255,0.6); border-radius: 50%; animation: toastParticle1 4s ease-in-out infinite;"></div>
        <div style="position: absolute; top: 60%; right: 25%; width: 3px; height: 3px; background: rgba(255,255,255,0.4); border-radius: 50%; animation: toastParticle2 5s ease-in-out infinite 1s;"></div>
        <div style="position: absolute; top: 30%; right: 15%; width: 2px; height: 2px; background: rgba(255,255,255,0.7); border-radius: 50%; animation: toastParticle3 3s ease-in-out infinite 0.5s;"></div>
    `;

    toast.innerHTML = content;
    container.appendChild(toast);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        dismissToast(toast);
    }, 5000);

    // Manual dismiss on click
    toast.addEventListener('click', (e) => {
        if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'I') {
            dismissToast(toast);
        }
    });

    return toast;
}

function dismissToast(toast) {
    if (!toast || !toast.parentElement) return;
    
    toast.style.animation = 'toastSlideOut 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 400);
}

// Connect to bank
async function connectBank(bankId) {
    const loadingState = document.getElementById('loadingState');
    const bankDataContainer = document.getElementById('bankDataContainer');
    
    loadingState.style.display = 'block';
    bankDataContainer.style.display = 'none';
    
    // Smooth scroll to loading section
    loadingState.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    try {
        const response = await fetch('api/connect-bank.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ bank_id: bankId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentBankData = result.data;
            await loadImportedTransactions();
            await loadCategories();
            displayBankData(result.data);
            bankDataContainer.style.display = 'block';
            bankDataContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            throw new Error(result.message);
        }
            } catch (error) {
            console.error('Error connecting to bank:', error);
            showToast('Lỗi kết nối ngân hàng: ' + error.message, 'error');
        } finally {
            loadingState.style.display = 'none';
        }
}

// Load imported transactions
async function loadImportedTransactions() {
    try {
        const response = await fetch('api/get-imported-transactions.php');
        const result = await response.json();
        
        if (result.success) {
            importedTransactions = new Set(result.data.map(t => t.bank_transaction_id).filter(id => id));
        }
    } catch (error) {
        console.error('Error loading imported transactions:', error);
    }
}

// Load categories
async function loadCategories() {
    try {
        const response = await fetch('api/get-categories.php');
        const result = await response.json();
        
        if (result.success) {
            categories = result.data;
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Display bank data
function displayBankData(data) {
    if (!data || !data.data) return;
    
    const bankData = data.data;
    
    // Update balance summary
    document.getElementById('totalBalance').textContent = 
        formatCurrency(bankData.balance_info.totalBalanceRaw || 0);
    document.getElementById('accountCount').textContent = 
        `${bankData.balance_info.accountCount || 0} tài khoản`;
    
    // Display accounts
    displayAccounts(bankData.balance_info.accounts || []);
    
    // Display transactions
    displayTransactions(bankData.transaction_data?.transactions || []);
}

// Display accounts
function displayAccounts(accounts) {
    const accountsList = document.getElementById('accountsList');
    
    if (!accounts || accounts.length === 0) {
        accountsList.innerHTML = '<p style="color:var(--secondary-color);text-align:center;grid-column:1/-1;">Không có tài khoản nào</p>';
        return;
    }
    
    accountsList.innerHTML = accounts.map((account, index) => `
        <div class="account-card magical-card" style="background:var(--hover-color);border:1px solid var(--border-color);padding:1.5rem;border-radius:12px;transition:all 0.3s ease;animation:cardSlideIn 0.5s ease-out ${index * 0.1}s both;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">
            <h4 style="margin:0 0 0.5rem;color:var(--primary-color);font-size:1rem;font-weight:700;">${account.acctNo}</h4>
            <p style="margin:0 0 1rem;color:var(--secondary-color);font-size:0.9rem;">${account.acctName}</p>
            <div style="font-size:1.4rem;font-weight:800;color:var(--positive-color);">${formatCurrency(parseInt(account.currentBalance) || 0)}</div>
        </div>
    `).join('');
}

// Display transactions
function displayTransactions(transactions) {
    const transactionsBody = document.getElementById('transactionsBody');
    const batchImportBtn = document.getElementById('batchImportBtn');
    
    if (!transactions || transactions.length === 0) {
        transactionsBody.innerHTML = '<tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--secondary-color);">Không có giao dịch nào</td></tr>';
        batchImportBtn.style.display = 'none';
        return;
    }
    
    let pendingTransactions = 0;
    
    transactionsBody.innerHTML = transactions.map((transaction, index) => {
        const creditAmount = parseInt(transaction.creditAmount) || 0;
        const debitAmount = parseInt(transaction.debitAmount) || 0;
        const amount = creditAmount > 0 ? creditAmount : -debitAmount;
        const type = creditAmount > 0 ? 'Thu' : 'Chi';
        
        // Create unique transaction ID
        const transactionId = `${transaction.transactionDate}_${Math.abs(amount)}_${transaction.description.substring(0, 20)}`;
        const isImported = importedTransactions.has(transactionId);
        
        if (!isImported) pendingTransactions++;
        
        return `
            <tr data-transaction-id="${transactionId}" data-transaction='${JSON.stringify(transaction).replace(/'/g, "&apos;")}' style="border-bottom:1px solid var(--border-color);transition:all 0.2s ease;animation:tableRowSlide 0.4s ease-out ${index * 0.05}s both;" onmouseover="this.style.background='var(--hover-color)'" onmouseout="this.style.background='transparent'">
                <td style="padding:1rem;border:none;text-align:center;">
                    ${!isImported ? 
                        `<input type="checkbox" class="transaction-checkbox" value="${transactionId}" style="transform:scale(1.1);" onchange="updateBatchImportButton()">` :
                        '<span style="color:var(--secondary-color);font-size:0.8rem;">-</span>'
                    }
                </td>
                <td style="padding:1rem;border:none;">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="width:8px;height:8px;border-radius:50%;background:${isImported ? 'var(--positive-color)' : 'var(--negative-color)'};"></span>
                        <span style="font-size:0.85rem;color:var(--secondary-color);">${isImported ? 'Đã import' : 'Chưa import'}</span>
                    </div>
                </td>
                <td style="padding:1rem;border:none;color:var(--secondary-color);font-size:0.9rem;">${formatDate(transaction.transactionDate)}</td>
                <td style="padding:1rem;border:none;color:var(--primary-color);font-weight:500;font-size:0.9rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${transaction.description}">${transaction.description}</td>
                <td style="padding:1rem;border:none;font-weight:700;font-size:0.95rem;color:${amount > 0 ? 'var(--positive-color)' : 'var(--negative-color)'};">
                    ${formatCurrency(Math.abs(amount))}
                </td>
                <td style="padding:1rem;border:none;">
                    <span style="padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:600;background:${amount > 0 ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)'};color:${amount > 0 ? 'var(--positive-color)' : 'var(--negative-color)'};">
                        ${type}
                    </span>
                </td>
                <td style="padding:1rem;border:none;">
                    ${isImported ? 
                        '<span style="color:var(--positive-color);font-size:0.85rem;"><i class="fas fa-check-circle"></i> Đã import</span>' :
                        `<button class="import-single-btn magical-button mini" data-transaction='${JSON.stringify(transaction).replace(/'/g, "&apos;")}' data-transaction-id="${transactionId}" style="background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:white;border:none;border-radius:6px;padding:0.5rem 0.75rem;font-weight:600;font-size:0.8rem;transition:all 0.3s ease;display:flex;align-items:center;gap:0.25rem;" onmouseover="this.style.background='linear-gradient(135deg,var(--primary-color),var(--positive-color))';this.style.transform='translateY(-2px) scale(1.05)'" onmouseout="this.style.background='linear-gradient(135deg,var(--accent-color),var(--primary-color))';this.style.transform='translateY(0) scale(1)'">
                            <i class="fas fa-download" style="font-size:0.7rem;"></i>
                            Import
                        </button>`
                    }
                </td>
            </tr>
        `;
    }).join('');
    
    // Show/hide batch import button based on pending transactions
    if (pendingTransactions > 0) {
        batchImportBtn.style.display = 'flex';
        document.getElementById('batchImportText').textContent = `Import hàng loạt (${pendingTransactions})`;
    } else {
        batchImportBtn.style.display = 'none';
    }
    
    // Add event listeners to import buttons
    document.querySelectorAll('.import-single-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionData = JSON.parse(this.getAttribute('data-transaction').replace(/&apos;/g, "'"));
            const transactionId = this.getAttribute('data-transaction-id');
            showCategoryModal(transactionData, transactionId);
        });
    });
    
    // Add event listener for select all visible
    document.getElementById('selectAllVisible').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.transaction-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBatchImportButton();
    });
}

// Show category selection modal
function showCategoryModal(transactionData, transactionId) {
    currentTransactionForImport = { data: transactionData, id: transactionId };
    
    const creditAmount = parseInt(transactionData.creditAmount) || 0;
    const debitAmount = parseInt(transactionData.debitAmount) || 0;
    const amount = creditAmount > 0 ? creditAmount : -debitAmount;
    const type = creditAmount > 0 ? 'income' : 'expense';
    const typeText = creditAmount > 0 ? 'Thu' : 'Chi';
    
    // Update modal content
    document.getElementById('modalTransactionAmount').textContent = formatCurrency(Math.abs(amount));
    document.getElementById('modalTransactionDescription').textContent = transactionData.description;
    document.getElementById('modalTransactionDate').textContent = formatDate(transactionData.transactionDate);
    
    const typeElement = document.getElementById('modalTransactionType');
    typeElement.textContent = typeText;
    typeElement.style.background = type === 'income' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)';
    typeElement.style.color = type === 'income' ? 'var(--positive-color)' : 'var(--negative-color)';
    
    // Populate category select
    const categorySelect = document.getElementById('categorySelect');
    categorySelect.innerHTML = '<option value="">-- Chọn thể loại --</option>';
    
    if (categories[type] && categories[type].length > 0) {
        categories[type].forEach(category => {
            const option = document.createElement('option');
            option.value = category.name;
            option.textContent = category.name;
            if (category.description) {
                option.title = category.description;
            }
            categorySelect.appendChild(option);
        });
    }
    
    // Show modal
    document.getElementById('categoryModal').style.display = 'block';
}

// Hide category modal
function hideCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
    currentTransactionForImport = null;
    document.getElementById('categorySelect').value = '';
}

// Import single transaction
async function importSingleTransaction() {
    if (!currentTransactionForImport) return;
    
    const selectedCategory = document.getElementById('categorySelect').value;
    if (!selectedCategory) {
        showToast('Vui lòng chọn thể loại để import giao dịch', 'warning', {
            icon: 'fas fa-exclamation-triangle'
        });
        return;
    }
    
    const confirmBtn = document.getElementById('confirmImport');
    const originalText = confirmBtn.innerHTML;
    
    // Show loading state
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang import...';
    confirmBtn.disabled = true;
    
    try {
        const response = await fetch('api/import-single-transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                transaction_id: currentTransactionForImport.id,
                category: selectedCategory
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success', {
                icon: 'fas fa-check-circle',
                category: selectedCategory,
                amount: formatCurrency(Math.abs(parseInt(currentTransactionForImport.data.creditAmount) || parseInt(currentTransactionForImport.data.debitAmount) || 0))
            });
            
            // Add to imported transactions set
            importedTransactions.add(currentTransactionForImport.id);
            
            // Refresh transaction display
            displayTransactions(currentBankData?.data?.transaction_data?.transactions || []);
            
            // Hide modal
            hideCategoryModal();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error importing single transaction:', error);
        showToast('Lỗi import giao dịch: ' + error.message, 'error');
    } finally {
        // Restore button
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    }
}

// Modal event listeners
document.getElementById('closeCategoryModal').addEventListener('click', hideCategoryModal);
document.getElementById('cancelImport').addEventListener('click', hideCategoryModal);
document.getElementById('confirmImport').addEventListener('click', importSingleTransaction);

// Close modal when clicking outside
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCategoryModal();
    }
});

// Import transactions
document.getElementById('importTransactionsBtn')?.addEventListener('click', async () => {
    if (!currentBankData) {
        showToast('Vui lòng kết nối ngân hàng trước khi import', 'warning', {
            icon: 'fas fa-plug'
        });
        return;
    }
    
    if (confirm('Bạn có muốn import các giao dịch chưa import vào hệ thống không?')) {
        const button = document.getElementById('importTransactionsBtn');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang import...';
        button.disabled = true;
        
        try {
            const response = await fetch('api/import-bank-transactions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success', {
                    icon: 'fas fa-download',
                    details: 'Import hàng loạt hoàn tất'
                });
                // Reload imported transactions and refresh display
                await loadImportedTransactions();
                displayBankData(currentBankData);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error importing transactions:', error);
            showToast('Lỗi import giao dịch hàng loạt: ' + error.message, 'error');
        } finally {
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
});

// Update batch import button state
function updateBatchImportButton() {
    const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
    const batchImportBtn = document.getElementById('batchImportBtn');
    const selectedCount = document.getElementById('selectedCount');
    const selectAllContainer = document.querySelector('.select-all-container');
    
    if (selectedCheckboxes.length > 0) {
        batchImportBtn.style.display = 'flex';
        selectAllContainer.style.display = 'flex';
        selectedCount.textContent = `${selectedCheckboxes.length} đã chọn`;
        document.getElementById('batchImportText').textContent = `Import ${selectedCheckboxes.length} giao dịch`;
        
        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.transaction-checkbox');
        const selectAllVisible = document.getElementById('selectAllVisible');
        selectAllVisible.indeterminate = selectedCheckboxes.length > 0 && selectedCheckboxes.length < allCheckboxes.length;
        selectAllVisible.checked = selectedCheckboxes.length === allCheckboxes.length;
    } else {
        selectAllContainer.style.display = 'none';
        
        // Show all pending transactions count instead
        const allPendingCount = document.querySelectorAll('.transaction-checkbox').length;
        if (allPendingCount > 0) {
            batchImportBtn.style.display = 'flex';
            document.getElementById('batchImportText').textContent = `Import hàng loạt (${allPendingCount})`;
        } else {
            batchImportBtn.style.display = 'none';
        }
        
        document.getElementById('selectAllVisible').checked = false;
        document.getElementById('selectAllVisible').indeterminate = false;
    }
}

// Batch import transactions
async function batchImportTransactions() {
    const selectedCheckboxes = document.querySelectorAll('.transaction-checkbox:checked');
    const allCheckboxes = document.querySelectorAll('.transaction-checkbox');
    
    // If no specific selection, import all pending
    const transactionsToImport = selectedCheckboxes.length > 0 ? 
        Array.from(selectedCheckboxes) : 
        Array.from(allCheckboxes);
    
    if (transactionsToImport.length === 0) {
        showToast('Không có giao dịch nào để import', 'warning');
        return;
    }
    
    // Show confirmation
    const confirmMessage = `Bạn có muốn import ${transactionsToImport.length} giao dịch được chọn không?\n\nCác giao dịch sẽ được tự động phân loại theo AI.`;
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const batchImportBtn = document.getElementById('batchImportBtn');
    const originalText = batchImportBtn.innerHTML;
    
    // Show loading state
    batchImportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang import...';
    batchImportBtn.disabled = true;
    
    let successCount = 0;
    let errorCount = 0;
    const errors = [];
    
    try {
        // Process transactions in batches of 5
        const batchSize = 5;
        for (let i = 0; i < transactionsToImport.length; i += batchSize) {
            const batch = transactionsToImport.slice(i, i + batchSize);
            
            // Update progress
            const progress = Math.round((i / transactionsToImport.length) * 100);
            batchImportBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Import... ${progress}%`;
            
            // Process batch
            const batchPromises = batch.map(async (checkbox) => {
                try {
                    const transactionId = checkbox.value;
                    const row = document.querySelector(`tr[data-transaction-id="${transactionId}"]`);
                    const transactionData = JSON.parse(row.getAttribute('data-transaction').replace(/&apos;/g, "'"));
                    
                    const response = await fetch('api/import-single-transaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            transaction_id: transactionId,
                            category: 'auto' // Let AI decide category
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        successCount++;
                        importedTransactions.add(transactionId);
                        return { success: true, id: transactionId };
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    errorCount++;
                    errors.push({
                        id: checkbox.value,
                        error: error.message
                    });
                    return { success: false, error: error.message };
                }
            });
            
            await Promise.all(batchPromises);
            
            // Small delay between batches to prevent overwhelming the server
            if (i + batchSize < transactionsToImport.length) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }
        
        // Show results
        if (successCount > 0) {
            showToast(`Đã import thành công ${successCount} giao dịch`, 'success', {
                icon: 'fas fa-check-circle',
                details: errorCount > 0 ? `${errorCount} giao dịch thất bại` : 'Tất cả giao dịch đã được import'
            });
        }
        
        if (errorCount > 0) {
            showToast(`${errorCount} giao dịch không thể import`, 'error', {
                icon: 'fas fa-exclamation-triangle',
                details: 'Vui lòng thử lại hoặc import từng giao dịch'
            });
            console.error('Batch import errors:', errors);
        }
        
        // Refresh transaction display
        displayTransactions(currentBankData?.data?.transaction_data?.transactions || []);
        
    } catch (error) {
        console.error('Batch import error:', error);
        showToast('Lỗi import hàng loạt: ' + error.message, 'error');
    } finally {
        // Restore button
        batchImportBtn.innerHTML = originalText;
        batchImportBtn.disabled = false;
    }
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatDate(dateString) {
    try {
        const [datePart] = dateString.split(' ');
        const [day, month, year] = datePart.split('/');
        const date = new Date(year, month - 1, day);
        
        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    } catch (error) {
        return dateString;
    }
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes cardSlideIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes tableRowSlide {
        from { opacity: 0; transform: translateX(-20px); }
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
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes numberPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes patternFloat {
        0%, 100% { transform: translateX(0px) translateY(0px); }
        50% { transform: translateX(20px) translateY(-20px); }
    }
    
    @keyframes buttonShine {
        0% { left: -100%; }
        50%, 100% { left: 100%; }
    }
    
    @keyframes addIconBounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    @keyframes iconSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
    
    @keyframes floatDashboard4 {
        0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
        25% { transform: translateY(-15px) translateX(15px) rotate(90deg); }
        50% { transform: translateY(-30px) translateX(0px) rotate(180deg); }
        75% { transform: translateY(-15px) translateX(-15px) rotate(270deg); }
    }
    
    @keyframes floatDashboard5 {
        0%, 100% { transform: translateY(0px) translateX(0px); }
        50% { transform: translateY(-20px) translateX(-30px); }
    }
    
    @keyframes heroGlowPulse {
        0%, 100% { opacity: 0.08; transform: translate(-50%, -50%) scale(1); }
        50% { opacity: 0.12; transform: translate(-50%, -50%) scale(1.1); }
    }
    
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-50px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(50px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes megaTitleGlow {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.7; }
    }
    
    @keyframes decorationSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes userPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    @keyframes calendarPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
         @keyframes clockTick {
         0%, 100% { transform: rotate(0deg); }
         50% { transform: rotate(180deg); }
     }
     
     @keyframes fadeIn {
         from { opacity: 0; }
         to { opacity: 1; }
     }
     
     @keyframes modalSlideIn {
         from { opacity: 0; transform: translate(-50%, -60%) scale(0.9); }
         to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
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
     
     @keyframes toastSlideOut {
         0% { 
             transform: translateX(0) scale(1); 
             opacity: 1; 
         }
         100% { 
             transform: translateX(400px) scale(0.8); 
             opacity: 0; 
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
             width: 100%; 
             opacity: 1; 
         }
         90% { 
             width: 5%; 
             opacity: 1; 
         }
         100% { 
             width: 0%; 
             opacity: 0; 
         }
     }
     
     @keyframes toastShine {
         0% { 
             left: -100%; 
             opacity: 0; 
         }
         50% { 
             left: 0%; 
             opacity: 1; 
         }
         100% { 
             left: 100%; 
             opacity: 0; 
         }
     }
     
     @keyframes toastParticle1 {
         0%, 100% { 
             transform: translateY(0px) translateX(0px) scale(1); 
             opacity: 0.6; 
         }
         25% { 
             transform: translateY(-8px) translateX(4px) scale(1.2); 
             opacity: 1; 
         }
         50% { 
             transform: translateY(-12px) translateX(-2px) scale(0.8); 
             opacity: 0.4; 
         }
         75% { 
             transform: translateY(-6px) translateX(6px) scale(1.1); 
             opacity: 0.8; 
         }
     }
     
     @keyframes toastParticle2 {
         0%, 100% { 
             transform: translateY(0px) translateX(0px) rotate(0deg); 
             opacity: 0.4; 
         }
         33% { 
             transform: translateY(-10px) translateX(-5px) rotate(120deg); 
             opacity: 0.8; 
         }
         66% { 
             transform: translateY(-15px) translateX(3px) rotate(240deg); 
             opacity: 0.6; 
         }
     }
     
     @keyframes toastParticle3 {
         0%, 100% { 
             transform: translateY(0px) scale(1); 
             opacity: 0.7; 
         }
         50% { 
             transform: translateY(-20px) scale(1.5); 
             opacity: 0.3; 
         }
     }
     
     /* Mobile responsive adjustments for toasts */
     @media (max-width: 768px) {
         #toastContainer {
             top: 10px !important;
             right: 10px !important;
             left: 10px !important;
             max-width: none !important;
         }
         
         .toast {
             min-width: auto !important;
             max-width: none !important;
         }
     }
 
     

 `;
 document.head.appendChild(style);

 
     
// Auto Import JavaScript Functionality  
document.addEventListener('DOMContentLoaded', function() {
    // Setup dropdown menu for user logout
    const setupDropdown = () => {
        const dropdownToggle = document.querySelector('.dropdown-toggle')
        const dropdownMenu = document.querySelector('.dropdown-menu')
        
        if (dropdownToggle && dropdownMenu) {
            // Toggle dropdown on click
            dropdownToggle.addEventListener('click', (e) => {
                e.preventDefault()
                e.stopPropagation()
                
                const isVisible = dropdownMenu.style.display === 'block'
                dropdownMenu.style.display = isVisible ? 'none' : 'block'
                
                console.log('Dropdown toggled:', !isVisible)
            })
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.style.display = 'none'
                }
            })
            
            // Close dropdown on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    dropdownMenu.style.display = 'none'
                }
            })
            
            console.log("✅ Dropdown menu ready!")
        } else {
            console.log("❌ Dropdown elements not found")
        }
    }
    
    // Initialize dropdown
    setupDropdown()
     
    // Batch import button event listener
    const batchImportBtn = document.getElementById('batchImportBtn')
    if (batchImportBtn) {
        batchImportBtn.addEventListener('click', batchImportTransactions)
    }
});
</script>

<?php include 'includes/footer.php'; ?> 