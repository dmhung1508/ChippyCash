<footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Quản lý Thu Chi. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
    
    <script src="js/main.js"></script>
    
    <!-- Additional Dark Mode Sync for all pages -->
    <script>
        // Đảm bảo dark mode được áp dụng ngay khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            }
            
            // Lắng nghe thay đổi storage từ các tab khác
            window.addEventListener('storage', function(e) {
                if (e.key === 'darkMode') {
                    const newDarkMode = e.newValue === 'true';
                    if (newDarkMode) {
                        document.body.classList.add('dark-mode');
                    } else {
                        document.body.classList.remove('dark-mode');
                    }
                }
            });
        });
    </script>
</body>
</html>
