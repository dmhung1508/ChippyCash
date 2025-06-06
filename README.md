# ChippyCash - AI-Powered Personal Finance Management System

ChippyCash is an intelligent personal finance management application with integrated AI chatbot, automatic bill analysis, and bank synchronization.

## 🚀 Key Features

- **💬 Financial AI Chatbot** with 3 personalities (Strict Mom, Homie, Smart Assistant)
- **🔊 Natural Text-to-Speech** powered by Gemini AI
- **📷 Bill Analysis** from images using OpenAI Vision
- **🏦 Automatic Bank Integration** with MBBank API
- **📊 Advanced Dashboard** with detailed statistics and reports
- **👨‍💼 Complete Admin Panel** for system management
- **📧 Email Notification System** via Mailgun

## 🏗️ System Architecture

- **Backend**: Python FastAPI + OpenAI + Google Gemini
- **Frontend**: PHP + MySQL + Modern UI
- **Database**: MySQL
- **APIs**: OpenAI GPT-4, Google Gemini TTS, MBBank API

## 📋 System Requirements

### Backend Requirements
- Python 3.8+
- MySQL 8.0+
- OpenAI API Key
- Google Gemini API Key

### Frontend Requirements
- PHP 7.4+
- MySQL 8.0+
- Web server (Apache/Nginx)
- Mailgun API Key (optional)

## 🛠️ Installation Guide

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/ChippyCash.git
cd ChippyCash
```

### 2. Backend Setup (Python)

#### Step 1: Create virtual environment
```bash
cd backend
python -m venv venv

# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate
```

#### Step 2: Install dependencies
```bash
pip install -r requirements.txt
```

#### Step 3: Configure environment variables
Create `.env` file in `backend/` directory:

```env
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here

# Google Gemini Configuration  
GEMINI_API_KEY=your_gemini_api_key_here

# Database Configuration
DB_HOST=localhost
DB_NAME=chippy
DB_USER=root
DB_PASSWORD=your_password
```

#### Step 4: Run backend server
```bash
python main.py
```
Backend will run at: `http://localhost:8506`

### 3. Frontend Setup (PHP)

#### Step 1: Configure database
Edit `frontend/config/database.php`:

```php
<?php
$host = 'localhost';
$dbname = 'chippy';
$username = 'root';
$password = 'your_password';

// Mailgun Configuration (optional)
define('MAILGUN_API_KEY', 'your_mailgun_api_key');
define('MAILGUN_DOMAIN', 'your_domain.com');
define('MAILGUN_FROM_EMAIL', 'noreply@your_domain.com');
define('MAILGUN_FROM_NAME', 'ChippyCash');
?>
```

#### Step 2: Create database
```bash
mysql -u root -p
```

```sql
CREATE DATABASE chippy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chippy;
SOURCE frontend/database.sql;
```

#### Step 3: Setup admin account
Run admin setup script:
```bash
cd frontend
php setup_settings.php
```

#### Step 4: Configure web server

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# PHP Configuration
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
```

**Nginx**
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/ChippyCash/frontend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```


## 🔧 API Keys Configuration

### 1. OpenAI API Key
1. Visit [OpenAI Platform](https://platform.openai.com)
2. Create a new API key
3. Add to `.env` file: `OPENAI_API_KEY=your_key`

### 2. Google Gemini API Key  
1. Visit [Google AI Studio](https://aistudio.google.com)
2. Create a new API key
3. Add to `.env` file: `GEMINI_API_KEY=your_key`

### 3. Mailgun API (optional)
1. Sign up at [Mailgun](https://www.mailgun.com)
2. Get API key and domain
3. Update `frontend/config/database.php`

## 🚀 Running the Application

### Development Mode

**Backend:**
```bash
cd backend
python main.py
```

**Frontend:**
```bash
# Using PHP built-in server
cd frontend
php -S localhost:8000

# Or use XAMPP/WAMP/MAMP
```

### Production Mode

1. **Backend**: Use gunicorn or uvicorn
```bash
pip install gunicorn
gunicorn main:app -w 4 -k uvicorn.workers.UvicornWorker --bind 0.0.0.0:8506
```

2. **Frontend**: Deploy to Apache/Nginx with PHP-FPM

## 📱 Using the Application

### Live Demo Access
- **Demo URL**: [https://ttcs.chippycash.com](https://ttcs.chippycash.com)
- Create a new account or use demo credentials

### Local Development

#### Admin Login
- URL: `http://localhost:8000/admin.php`
- Username: `admin`
- Password: (set during setup)

#### User Registration
- URL: `http://localhost:8000/index.php?register`
- Fill in information and create account

### Main API Endpoints

- `POST /chat` - Chat with AI
- `POST /voice` - Text-to-speech  
- `POST /analyze-bill` - Analyze bill images
- `GET /bank` - Get bank information
- `GET /history` - Chat history

## 🧪 Testing Features

### Test AI Chatbot
```bash
curl -X POST http://localhost:8506/chat \
  -H "Content-Type: application/json" \
  -d '{"id_user": "test", "message": "I spent $10 on breakfast", "role": "Smart Assistant"}'
```

### Test Voice Generation
```bash
curl -X POST http://localhost:8506/voice \
  -H "Content-Type: application/json" \
  -d '{"text": "Hello there", "voice_type": "mama"}'
```

## 🐛 Troubleshooting

### Database Connection Issues
```bash
# Check MySQL service
sudo systemctl status mysql

# Test connection
mysql -u root -p -e "SHOW DATABASES;"
```

### API Keys Issues
```bash
# Check .env file
cat backend/.env

# Test OpenAI connection
python -c "from openai import OpenAI; print('OpenAI OK')"
```

### File Permission Issues
```bash
# Linux/Mac
sudo chown -R www-data:www-data frontend/
chmod -R 755 frontend/

# Windows with XAMPP
# Ensure XAMPP runs with admin privileges
```

### Backend Not Starting
```bash
# Check port 8506
netstat -tlnp | grep 8506

# Check logs
python main.py --log-level debug
```

## 📚 Directory Structure

```
ChippyCash/
├── backend/                 # Python FastAPI Backend
│   ├── main.py             # Main API server
│   ├── voice.py            # Text-to-speech
│   ├── load_chat.py        # Chat management
│   ├── sql.py              # Database functions
│   ├── utils.py            # Utilities
│   ├── cfg.py              # Configuration
│   ├── requirements.txt    # Python dependencies
│   ├── .env                # Environment variables
│   ├── db_store/           # User data storage
│   ├── db_chat/            # Chat history storage
│   └── MBBank/             # Bank integration
├── frontend/               # PHP Frontend
│   ├── index.php           # Main dashboard
│   ├── admin.php           # Admin panel
│   ├── bank.php            # Bank management
│   ├── transactions.php    # Transaction management
│   ├── categories.php      # Category management
│   ├── config/             # Configuration files
│   ├── includes/           # Shared components
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   ├── api/                # API endpoints
│   └── backups/            # Database backups
├── report/                 # Documentation
└── README.md               # This file
```

## 🤝 Contributing

1. Fork the project
2. Create feature branch: `git checkout -b feature/AmazingFeature`
3. Commit changes: `git commit -m 'Add some AmazingFeature'`
4. Push to branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Contact

- **Email**: admin@dinhmanhhung.net
- **Project Link**: [https://github.com/dmhung1508/ChippyCash](https://github.com/dmhung1508/ChippyCash)

## 🌐 Live Demo

🚀 **Try ChippyCash online**: [https://ttcs.chippycash.com](https://ttcs.chippycash.com)

Experience all features including:
- AI Financial Chatbot with multiple personalities
- Voice responses and text-to-speech
- Bill analysis from images
- Bank account integration
- Real-time financial dashboard

---

⭐ If this project is helpful, please give it a star!
