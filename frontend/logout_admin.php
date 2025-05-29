<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Start new session
session_start();

echo "✅ Session đã được xóa hoàn toàn!<br>";
echo "🔄 Chuyển hướng đến trang đăng nhập...<br>";
echo '<meta http-equiv="refresh" content="2;url=index.php">';
echo '<p style="margin-top:20px;"><a href="index.php" style="color:#4299e1;text-decoration:none;font-weight:600;">👆 Click vào đây nếu không tự động chuyển hướng</a></p>';
?> 