<?php
/**
 * Settings Management System
 * Xử lý và áp dụng các cài đặt hệ thống
 */

class SystemSettings {
    private $conn;
    private $settings = [];
    private $loaded = false;

    public function __construct($connection) {
        $this->conn = $connection;
        $this->loadSettings();
    }

    /**
     * Load tất cả settings từ database
     */
    private function loadSettings() {
        try {
            $stmt = $this->conn->query("SELECT setting_key, setting_value, setting_type FROM system_settings");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $setting) {
                $this->settings[$setting['setting_key']] = $this->convertValue($setting['setting_value'], $setting['setting_type']);
            }
            
            $this->loaded = true;
        } catch (Exception $e) {
            error_log("Error loading settings: " . $e->getMessage());
        }
    }

    /**
     * Convert setting value theo type
     */
    private function convertValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            case 'json':
                return json_decode($value, true);
            default:
                return (string)$value;
        }
    }

    /**
     * Lấy giá trị setting
     */
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Set giá trị setting
     */
    public function set($key, $value) {
        $this->settings[$key] = $value;
        
        // Cập nhật vào database
        try {
            $stmt = $this->conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([
                is_array($value) ? json_encode($value) : $value,
                $key
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Error setting value: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra maintenance mode
     */
    public function isMaintenanceMode() {
        return $this->get('maintenance_mode', false);
    }

    /**
     * Kiểm tra cho phép đăng ký
     */
    public function allowRegistration() {
        return $this->get('user_registration', true);
    }

    /**
     * Lấy timezone
     */
    public function getTimezone() {
        return $this->get('timezone', 'Asia/Ho_Chi_Minh');
    }

    /**
     * Lấy currency
     */
    public function getCurrency() {
        return $this->get('currency', 'VND');
    }

    /**
     * Lấy site name
     */
    public function getSiteName() {
        return $this->get('site_name', 'Quản lý Thu Chi');
    }

    /**
     * Lấy site description
     */
    public function getSiteDescription() {
        return $this->get('site_description', 'Hệ thống quản lý tài chính cá nhân');
    }

    /**
     * Lấy max file upload size (MB)
     */
    public function getMaxFileSize() {
        return $this->get('max_file_upload_size', 10);
    }

    /**
     * Lấy items per page
     */
    public function getItemsPerPage() {
        return $this->get('items_per_page', 20);
    }

    /**
     * Lấy session timeout (phút)
     */
    public function getSessionTimeout() {
        return $this->get('session_timeout', 1440);
    }

    /**
     * Kiểm tra email notifications enabled
     */
    public function isEmailNotificationsEnabled() {
        return $this->get('email_notifications', true);
    }

    /**
     * Lấy admin email
     */
    public function getAdminEmail() {
        return $this->get('admin_email', 'admin@chippy.local');
    }

    /**
     * Kiểm tra bank sync enabled
     */
    public function isBankSyncEnabled() {
        return $this->get('enable_bank_sync', true);
    }

    /**
     * Lấy date format
     */
    public function getDateFormat() {
        return $this->get('date_format', 'd/m/Y');
    }

    /**
     * Áp dụng settings vào PHP environment
     */
    public function applyToEnvironment() {
        // Set timezone
        date_default_timezone_set($this->getTimezone());
        
        // Set file upload limits
        $maxSize = $this->getMaxFileSize();
        ini_set('upload_max_filesize', $maxSize . 'M');
        ini_set('post_max_size', ($maxSize * 2) . 'M');
        
        // Set session timeout (only if session not started yet)
        if (session_status() === PHP_SESSION_NONE) {
            $timeout = $this->getSessionTimeout() * 60; // Convert to seconds
            ini_set('session.gc_maxlifetime', $timeout);
            session_set_cookie_params($timeout);
        }
    }

    /**
     * Lấy tất cả settings (dùng cho admin panel)
     */
    public function getAllSettings() {
        return $this->settings;
    }

    /**
     * Lấy public settings (dùng cho frontend)
     */
    public function getPublicSettings() {
        try {
            $stmt = $this->conn->query("SELECT setting_key, setting_value, setting_type FROM system_settings WHERE is_public = 1");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $publicSettings = [];
            foreach ($results as $setting) {
                $publicSettings[$setting['setting_key']] = $this->convertValue($setting['setting_value'], $setting['setting_type']);
            }
            
            return $publicSettings;
        } catch (Exception $e) {
            error_log("Error loading public settings: " . $e->getMessage());
            return [];
        }
    }
}

// Global function để dễ sử dụng - renamed để tránh conflict với admin.php
function getSystemSettingsManager($conn = null) {
    global $systemSettings;
    
    if (!isset($systemSettings)) {
        if ($conn === null) {
            global $conn;
        }
        $systemSettings = new SystemSettings($conn);
    }
    
    return $systemSettings;
}

// Middleware để check maintenance mode
function checkMaintenanceMode($conn) {
    $settings = getSystemSettingsManager($conn);
    
    if ($settings->isMaintenanceMode()) {
        // Cho phép bypass nếu có admin_access parameter
        if (isset($_GET['admin_access']) && $_GET['admin_access'] == '1') {
            return; // Skip maintenance check
        }
        
        // Chỉ admin mới được truy cập khi maintenance
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(503);
            include 'includes/maintenance.php';
            exit;
        }
    }
}

// Function để validate registration
function canUserRegister($conn) {
    $settings = getSystemSettingsManager($conn);
    return $settings->allowRegistration();
}
?> 