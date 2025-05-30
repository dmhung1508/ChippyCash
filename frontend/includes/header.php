<?php
header('Content-Type: text/html; charset=utf-8');
if (isset($_SESSION['user_id']) && !isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Quản lý Thu Chi'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Dark Mode Sync Script -->
    <script>
        // Áp dụng dark mode ngay lập tức từ localStorage (trước khi trang load)
        (function() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.documentElement.classList.add('dark-mode');
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <?php if (isset($_SESSION['user_id']) && !isset($hideNavbar)): ?>
    <nav class="main-navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <a href="index.php">ChippyCash</a>
            </div>
            <div class="navbar-menu">
                <a href="index.php" class="navbar-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="transactions.php" class="navbar-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> Giao dịch
                </a>
                <a href="categories.php" class="navbar-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Thể loại
                </a>
                <a href="bank.php" class="navbar-item <?php echo basename($_SERVER['PHP_SELF']) == 'bank.php' ? 'active' : ''; ?>">
                    <i class="fas fa-university"></i> Ngân hàng
                </a>
                <?php 
                // Kiểm tra quyền admin
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $currentUser = $stmt->fetch();
                if ($currentUser && $currentUser['role'] === 'admin'): 
                ?>
                <a href="admin.php" class="navbar-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>" style="color: #fbbf24;">
                    <i class="fas fa-crown"></i> Admin
                </a>
                <?php endif; ?>
                <div class="navbar-dropdown">
                    <button class="dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user-cog"></i> Hồ sơ</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
