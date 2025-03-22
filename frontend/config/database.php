<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'chippy';
$username = 'root';
$password = 'hung1234';

try {
    // Create PDO connection with UTF-8 support
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configure PDO settings
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Ensure proper UTF-8 encoding
    $conn->exec("SET NAMES utf8mb4");
    $conn->exec("SET CHARACTER SET utf8mb4");
    $conn->exec("SET CHARACTER_SET_CONNECTION=utf8mb4");
} catch(PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}
?>

