<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

require_once '../includes/db.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tạo tên file backup
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$dbname}_{$timestamp}.sql";
    $backupPath = "../backups/";
    
    // Tạo thư mục backup nếu chưa có
    if (!is_dir($backupPath)) {
        mkdir($backupPath, 0755, true);
    }
    
    $fullPath = $backupPath . $filename;
    
    // Method 1: Sử dụng mysqldump command nếu có thể
    $mysqldumpAvailable = false;
    $command = "mysqldump --host={$host} --user={$username} --password={$password} {$dbname} > {$fullPath} 2>&1";
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($fullPath) && filesize($fullPath) > 0) {
        $mysqldumpAvailable = true;
    } else {
        // Method 2: Backup bằng PHP nếu mysqldump không khả dụng
        $backup = generateBackupContent($conn, $dbname);
        file_put_contents($fullPath, $backup);
    }
    
    if (!file_exists($fullPath)) {
        throw new Exception('Failed to create backup file');
    }
    
    $fileSize = filesize($fullPath);
    
    // Log backup activity
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'database_backup',
        "Backup created: {$filename} ({$fileSize} bytes)",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Lưu vào backup history nếu table tồn tại
    try {
        $stmt = $conn->prepare("INSERT INTO backup_history (admin_id, filename, file_size, backup_type, status) VALUES (?, ?, ?, 'manual', 'success')");
        $stmt->execute([$_SESSION['user_id'], $filename, $fileSize]);
    } catch (Exception $e) {
        // Table backup_history chưa tồn tại, bỏ qua
    }
    
    // Force download
    if (file_exists($fullPath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($fullPath);
        
        // Xóa file backup sau khi download để tiết kiệm không gian
        unlink($fullPath);
        exit;
    } else {
        throw new Exception('Backup file not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Backup failed: ' . $e->getMessage()]);
}

function generateBackupContent($conn, $dbname) {
    $backup = "-- Database Backup\n";
    $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Database: {$dbname}\n\n";
    
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $backup .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    $backup .= "SET AUTOCOMMIT=0;\n";
    $backup .= "START TRANSACTION;\n\n";
    
    // Lấy danh sách tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Table structure
        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Table structure for table `{$table}`\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $stmt = $conn->query("SHOW CREATE TABLE `{$table}`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        // Table data
        $backup .= "-- --------------------------------------------------------\n";
        $backup .= "-- Dumping data for table `{$table}`\n";
        $backup .= "-- --------------------------------------------------------\n\n";
        
        $stmt = $conn->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = "`" . implode("`, `", $columns) . "`";
            
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($conn) {
                    return $value === null ? 'NULL' : $conn->quote($value);
                }, array_values($row));
                
                $valueList = implode(", ", $values);
                $backup .= "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n";
            }
            $backup .= "\n";
        }
    }
    
    $backup .= "COMMIT;\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    return $backup;
}
?> 