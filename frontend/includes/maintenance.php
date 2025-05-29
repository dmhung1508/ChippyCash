<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang bảo trì</title>
    <style>
        :root {
            --primary-color: #1e293b;
            --accent-color: #3b82f6;
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-color: #64748b;
            --card-bg: rgba(255, 255, 255, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .particle:nth-child(1) { left: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 40%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 60%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 80%; animation-delay: 6s; }

        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            50% {
                transform: translateY(50vh) scale(1);
            }
        }

        .maintenance-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 2;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .maintenance-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: var(--gradient-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 20px rgba(59, 130, 246, 0);
            }
        }

        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .maintenance-message {
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .estimated-time {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .estimated-time h4 {
            color: var(--accent-color);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .estimated-time p {
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-bg);
            border-radius: 3px;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes progress {
            0% { width: 20%; }
            50% { width: 80%; }
            100% { width: 20%; }
        }

        .contact-info {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-top: 1.5rem;
        }

        .contact-info a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .admin-login {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(100, 116, 139, 0.2);
        }

        .admin-login a {
            display: inline-block;
            background: var(--gradient-bg);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .admin-login a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        @media (max-width: 768px) {
            .maintenance-container {
                padding: 2rem;
                margin: 1rem;
            }

            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-icon {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle" style="width: 20px; height: 20px;"></div>
        <div class="particle" style="width: 15px; height: 15px;"></div>
        <div class="particle" style="width: 25px; height: 25px;"></div>
        <div class="particle" style="width: 18px; height: 18px;"></div>
    </div>

    <div class="maintenance-container">
        <div class="maintenance-icon">
            🔧
        </div>
        
        <h1 class="maintenance-title">Hệ thống đang bảo trì</h1>
        
        <p class="maintenance-message">
            Chúng tôi đang thực hiện một số cập nhật quan trọng để cải thiện trải nghiệm của bạn. 
            Hệ thống sẽ sớm trở lại hoạt động bình thường.
        </p>

        <div class="estimated-time">
            <h4>⏱️ Thời gian dự kiến</h4>
            <p>Hệ thống sẽ hoạt động trở lại trong vòng 30 phút</p>
        </div>

        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>

        <div class="contact-info">
            <p>Nếu bạn cần hỗ trợ khẩn cấp, vui lòng liên hệ:</p>
            <p>📧 Email: <a href="mailto:admin@chippy.local">admin@chippy.local</a></p>
            <p>📞 Hotline: <a href="tel:+84123456789">+84 123 456 789</a></p>
        </div>

        <div class="admin-login">
            <a href="index.php?admin_access=1">🔐 Đăng nhập Admin</a>
        </div>
    </div>

    <script>
        // Auto refresh page every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 phút

        // Add dynamic particles
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = particle.style.height = Math.random() * 10 + 10 + 'px';
            particle.style.animationDuration = Math.random() * 10 + 10 + 's';
            particle.style.animationDelay = Math.random() * 5 + 's';
            
            document.querySelector('.particles').appendChild(particle);
            
            // Remove particle after animation
            setTimeout(() => {
                particle.remove();
            }, 20000);
        }

        // Create new particle every 3 seconds
        setInterval(createParticle, 3000);
    </script>
</body>
</html> 