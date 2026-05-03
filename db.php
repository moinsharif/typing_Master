<?php
$host = 'localhost';
$dbname = 'typing_tutor';
$username = 'root';
$password = '';

try {
    // 1. Initial connection to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create Database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname` "); // Just to be sure, though we reconnect next

    // 3. Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        is_guest BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Create Typing Results Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS typing_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        wpm INT,
        accuracy INT,
        difficulty VARCHAR(20),
        key_analysis JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 6. Insert Default Admin if not exists
    $adminUser = 'admin';
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkAdmin->execute([$adminUser]);
    if (!$checkAdmin->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, TRUE)");
        $stmt->execute([$adminUser, $adminPass]);
    }

} catch (PDOException $e) {
    die("Database Initialization Error: " . $e->getMessage());
}
?>
