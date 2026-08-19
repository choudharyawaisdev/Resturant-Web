<?php
// includes/db.php

// â”€â”€â”€ Environment Detection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Detect whether we are running on localhost (XAMPP/dev) or live server
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'])
    || (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1');

if ($is_local) {
    // â”€â”€ LOCAL / XAMPP Settings â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $dbname = 'restaurant_db';
} else {
    // â”€â”€ LIVE SERVER Settings â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $host = 'localhost';
    $user = 'thedevtasoft_cafe_chinos';   // cPanel DB username
    $pass = 'thedevtasoft_cafe_chinos';              // cPanel DB password  â† update if different
    $dbname = 'thedevtasoft_cafe_chinos';   // cPanel DB name
}

$charset = 'utf8mb4';

try {
    if ($is_local) {
        // LOCAL: create database automatically if missing
        $dsn_no_db = "mysql:host=$host;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn_no_db, $user, $pass, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
    } else {
        // SERVER: database already exists in cPanel; connect directly
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    // â”€â”€â”€ Auto-create core tables if they don't exist â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    // 1. Check if base tables exist (e.g. categories)
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM `categories` LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }

    // 2. If not, run the SQL seed file (local only, on server DB is pre-imported)
    if (!$tableExists && $is_local) {
        $sqlPath = dirname(__DIR__) . '/database_complete.sql';
        if (!file_exists($sqlPath)) {
            $sqlPath = dirname(__DIR__) . '/database.sql';
        }
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    }

    // 3. Ensure users table exists (safe to run on both environments)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id`            INT AUTO_INCREMENT PRIMARY KEY,
      `name`          VARCHAR(150) NOT NULL,
      `email`         VARCHAR(150) NOT NULL UNIQUE,
      `phone`         VARCHAR(20)  DEFAULT NULL,
      `address`       TEXT         DEFAULT NULL,
      `area_id`       INT          DEFAULT NULL,
      `password` VARCHAR(255) NOT NULL,
      `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 4. Ensure wishlists table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `wishlists` (
      `id`         INT AUTO_INCREMENT PRIMARY KEY,
      `user_id`    INT NOT NULL,
      `product_id` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `user_product` (`user_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 5. Ensure orders table has user_id column
    $checkOrders = false;
    try {
        $pdo->query("SELECT 1 FROM `orders` LIMIT 1");
        $checkOrders = true;
    } catch (Exception $e) {
        $checkOrders = false;
    }
    if ($checkOrders) {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'user_id'")->fetch();
        if (!$checkColumn) {
            $pdo->exec("ALTER TABLE `orders` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
        }
    }

    // 6. Ensure admin user exists
    $adminExists = false;
    try {
        $adminCheck = $pdo->query("SELECT COUNT(*) FROM `admin_users`")->fetchColumn();
        $adminExists = ($adminCheck > 0);
    } catch (Exception $e) {
        $adminExists = true; // table might not exist yet; skip
    }
    if (!$adminExists) {
        $adminHash = password_hash('adminpassword', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `admin_users` (`username`, `password`) VALUES (?, ?)");
        $stmt->execute(['admin', $adminHash]);
    }

} catch (\PDOException $e) {
    // Show a clean error rather than a bare 500
    http_response_code(500);
    die("<h2 style='font-family:sans-serif;color:#c00'>Database connection failed.</h2><p style='font-family:monospace'>" . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
