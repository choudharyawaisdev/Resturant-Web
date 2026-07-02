<?php
// includes/db.php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'restaurant_db';
$charset = 'utf8mb4';

try {
    // 1. Initial connection without database to ensure it exists
    $dsn = "mysql:host=$host;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    
    // 2. Check if tables are already created (e.g. check categories table)
    $tableExists = false;
    try {
        $result = $pdo->query("SELECT 1 FROM `categories` LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    // 3. If tables do not exist, run the database.sql script automatically
    if (!$tableExists) {
        $sqlPath = dirname(__DIR__) . '/database.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Remove comments and execute queries
            $pdo->exec($sql);
        }
    }
    
    // 3b. Dynamically ensure users and wishlists tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(150) NOT NULL,
      `email` VARCHAR(150) NOT NULL UNIQUE,
      `phone` VARCHAR(20) DEFAULT NULL,
      `address` TEXT DEFAULT NULL,
      `area_id` INT DEFAULT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `wishlists` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `product_id` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `user_product` (`user_id`, `product_id`)
    ) ENGINE=InnoDB");

    // Check if user_id column exists in orders, if not add it
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'user_id'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
        $pdo->exec("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL");
    }
    
    // 4. Ensure admin user exists with hashed password
    $adminCheck = $pdo->query("SELECT COUNT(*) FROM `admin_users`")->fetchColumn();
    if ($adminCheck == 0) {
        $adminUsername = 'admin';
        // Password is 'adminpassword'
        $adminHash = password_hash('adminpassword', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `admin_users` (`username`, `password_hash`) VALUES (?, ?)");
        $stmt->execute([$adminUsername, $adminHash]);
    }
    
} catch (\PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>
