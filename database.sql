-- Create Database
CREATE DATABASE IF NOT EXISTS `restaurant_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `restaurant_db`;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Product Sizes Table
CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `size_name` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Addons Table
CREATE TABLE IF NOT EXISTS `addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- 5. Product Addons Mapping Table
CREATE TABLE IF NOT EXISTS `product_addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `addon_id` INT NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`addon_id`) REFERENCES `addons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Drinks Table
CREATE TABLE IF NOT EXISTS `drinks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- 7. Product Drinks Mapping Table
CREATE TABLE IF NOT EXISTS `product_drinks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `drink_id` INT NOT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`drink_id`) REFERENCES `drinks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Areas Table
CREATE TABLE IF NOT EXISTS `areas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Chiniot',
  `area_name` VARCHAR(150) NOT NULL,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- 8b. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `area_id` INT DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `area_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `delivery_fee` DECIMAL(10,2) NOT NULL,
  `grand_total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
  `payment_method` VARCHAR(50) DEFAULT 'COD',
  `order_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `size_name` VARCHAR(50) DEFAULT NULL,
  `addons` TEXT DEFAULT NULL, -- JSON
  `drink_name` VARCHAR(100) DEFAULT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `item_price` DECIMAL(10,2) NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. Admin Users Table
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- =========================================================================
-- SEED DATA
-- =========================================================================

-- Seed Chiniot Areas (8 Areas with Delivery Fee)
INSERT INTO `areas` (`city`, `area_name`, `delivery_fee`, `status`) VALUES
('Chiniot', 'Nao Gazah Road', 40.00, 'active'),
('Chiniot', 'Kutchery Road & Civil Lines', 50.00, 'active'),
('Chiniot', 'Rail Bazar & Circular Road', 60.00, 'active'),
('Chiniot', 'Jhang Road Area', 70.00, 'active'),
('Chiniot', 'Faisalabad Road Sector', 80.00, 'active'),
('Chiniot', 'Sargodha Road Colony', 90.00, 'active'),
('Chiniot', 'Bhowana Road Sector', 100.00, 'active'),
('Chiniot', 'Chenab Bridge Area', 120.00, 'active');

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `image`, `display_order`, `status`) VALUES
(1, 'Burgers', 'burger_cat.jpg', 1, 'active'),
(2, 'Pizza', 'pizza_cat.jpg', 2, 'active'),
(3, 'Zinger', 'zinger_cat.jpg', 3, 'active'),
(4, 'Wings', 'wings_cat.jpg', 4, 'active'),
(5, 'Pasta', 'pasta_cat.jpg', 5, 'active'),
(6, 'Drinks', 'drinks_cat.jpg', 6, 'active');

-- Seed Products
-- Burgers (Category 1)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(1, 1, 'Classic Beef Burger', 'Juicy grilled beef patty with lettuce, tomatoes, onions, and house sauce.', 'beef_burger.jpg', 450.00, 'active'),
(2, 1, 'Cheese Loaded Burger', 'Mouthwatering beef patty topped with double melted cheddar cheese and pickles.', 'cheese_burger.jpg', 520.00, 'active');

-- Pizza (Category 2)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(3, 2, 'Chicken Tikka Pizza', 'Spicy chicken tikka chunks, onions, green peppers, and rich mozzarella cheese.', 'tikka_pizza.jpg', 800.00, 'active'),
(4, 2, 'Fajita Sensation Pizza', 'Fajita-flavored chicken, bell peppers, onions, black olives, and cheese blend.', 'fajita_pizza.jpg', 850.00, 'active');

-- Zinger (Category 3)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(5, 3, 'Crispy Zinger Burger', 'Golden crispy chicken breast fillet with fresh lettuce and spicy mayonnaise.', 'zinger_burger.jpg', 480.00, 'active'),
(6, 3, 'Double Decker Zinger', 'Double crispy chicken patties with cheese layers, jalapeños, and special sauce.', 'double_zinger.jpg', 680.00, 'active');

-- Wings (Category 4)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(7, 4, 'Hot & Spicy Wings', 'Crunchy battered chicken wings tossed in our signature hot sauce.', 'hot_wings.jpg', 350.00, 'active'),
(8, 4, 'BBQ Glazed Wings', 'Smokey BBQ sauce basted wings sprinkled with sesame seeds.', 'bbq_wings.jpg', 380.00, 'active');

-- Pasta (Category 5)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(9, 5, 'Alfredo Fettuccine Pasta', 'Creamy parmesan sauce with grilled chicken breast chunks and mushrooms.', 'alfredo_pasta.jpg', 690.00, 'active'),
(10, 5, 'Spicy Mac & Cheese', 'Rich cheddar cheese macaroni topped with spicy breadcrumbs and chicken bites.', 'mac_cheese.jpg', 590.00, 'active');

-- Drinks (Category 6)
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `base_price`, `status`) VALUES
(11, 6, 'Soft Drink Can', 'Chilled beverage can of your choice (Coke, Sprite, Fanta).', 'soda_can.jpg', 120.00, 'active'),
(12, 6, 'Mineral Water', 'Clean and refreshing bottled mineral water.', 'mineral_water.jpg', 80.00, 'active');

-- Seed Product Sizes
-- Beef Burger Sizes
INSERT INTO `product_sizes` (`product_id`, `size_name`, `price`) VALUES
(1, 'Regular', 450.00),
(1, 'Double Patty', 600.00),
-- Cheese Burger Sizes
(2, 'Regular', 520.00),
(2, 'Double Cheese', 650.00),
-- Tikka Pizza Sizes
(3, 'Small 7\"', 500.00),
(3, 'Medium 10\"', 900.00),
(3, 'Large 13\"', 1400.00),
-- Fajita Pizza Sizes
(4, 'Small 7\"', 550.00),
(4, 'Medium 10\"', 950.00),
(4, 'Large 13\"', 1500.00),
-- Zinger Sizes
(5, 'Regular', 480.00),
(5, 'Zinger with Cheese', 540.00),
-- Double Decker Sizes
(6, 'Regular', 680.00),
-- Hot Wings Sizes
(7, '6 Pieces', 350.00),
(7, '10 Pieces', 550.00),
-- BBQ Wings Sizes
(8, '6 Pieces', 380.00),
(8, '10 Pieces', 580.00),
-- Alfredo Pasta Sizes
(9, 'Single Serving', 690.00),
-- Mac & Cheese Sizes
(10, 'Single Serving', 590.00),
-- Drinks
(11, '250ml Can', 120.00),
(12, '500ml Bottle', 80.00);

-- Seed Addons
INSERT INTO `addons` (`id`, `name`, `price`, `status`) VALUES
(1, 'Extra Cheese Slice', 70.00, 'active'),
(2, 'Dip Sauce', 50.00, 'active'),
(3, 'Jalapenos', 40.00, 'active'),
(4, 'Extra Chicken Patty', 150.00, 'active'),
(5, 'Turkey Bacon', 100.00, 'active'),
(6, 'Extra Olives & Mushrooms', 90.00, 'active');

-- Map Addons to Products
-- Map to Beef Burger
INSERT INTO `product_addons` (`product_id`, `addon_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
-- Map to Cheese Burger
(2, 1), (2, 2), (2, 3), (2, 4),
-- Map to Tikka Pizza
(3, 1), (3, 2), (3, 3), (3, 6),
-- Map to Fajita Pizza
(4, 1), (4, 2), (4, 3), (4, 6),
-- Map to Crispy Zinger
(5, 1), (5, 2), (5, 3), (5, 4),
-- Map to Double Decker Zinger
(6, 1), (6, 2), (6, 3),
-- Map to Wings
(7, 2), (8, 2),
-- Map to Pasta
(9, 1), (9, 2), (10, 1), (10, 2);

-- Seed Drinks
INSERT INTO `drinks` (`id`, `name`, `price`, `status`) VALUES
(1, 'Coca-Cola', 0.00, 'active'),
(2, 'Sprite', 0.00, 'active'),
(3, 'Fanta', 0.00, 'active'),
(4, 'Diet Coke', 10.00, 'active'),
(5, 'Mineral Water', 0.00, 'active');

-- Map Drinks to Products (For main items, drinks option is available)
INSERT INTO `product_drinks` (`product_id`, `drink_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 1), (2, 2), (2, 3), (2, 4),
(3, 1), (3, 2), (3, 3), (3, 4),
(4, 1), (4, 2), (4, 3), (4, 4),
(5, 1), (5, 2), (5, 3), (5, 4),
(6, 1), (6, 2), (6, 3), (6, 4),
(7, 1), (7, 2), (7, 3),
(8, 1), (8, 2), (8, 3),
(9, 1), (9, 2), (9, 3), (9, 4),
(10, 1), (10, 2), (10, 3), (10, 4);

-- 12. Wishlists Table
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB;
