<?php
// seed_runner.php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'restaurant_db';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Ensure database exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname` ");

    echo "Database connected successfully.\n";

    // Ensure required columns exist
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(100) DEFAULT NULL AFTER name;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE addons ADD COLUMN is_free TINYINT(1) DEFAULT 0 AFTER price;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE addons ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER is_free;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE drinks ADD COLUMN volume VARCHAR(50) DEFAULT NULL AFTER name;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE drinks ADD COLUMN can_be_free TINYINT(1) DEFAULT 0 AFTER price;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE product_sizes ADD COLUMN size_label VARCHAR(50) DEFAULT NULL AFTER size_name;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE product_sizes ADD COLUMN sort_order INT DEFAULT 0 AFTER price;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN has_sizes TINYINT(1) DEFAULT 0 AFTER base_price;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER has_sizes;");
    } catch (Exception $ex) {}
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN display_order INT DEFAULT 0 AFTER is_featured;");
    } catch (Exception $ex) {}

    // 1. CATEGORIES (13 Fast Food Categories)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE categories;");
    $pdo->exec("TRUNCATE TABLE addons;");
    $pdo->exec("TRUNCATE TABLE drinks;");
    $pdo->exec("TRUNCATE TABLE products;");
    $pdo->exec("TRUNCATE TABLE product_sizes;");
    $pdo->exec("TRUNCATE TABLE product_addons;");
    $pdo->exec("TRUNCATE TABLE product_drinks;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $categories = [
        ['Hot Deals',          'hot-deals',          '🔥', 1],
        ['Pizzas',             'pizzas',             '🍕', 2],
        ['Burgers',            'burgers',            '🍔', 3],
        ['Shawarma & Wraps',   'shawarma-wraps',     '🌯', 4],
        ['Wings',              'wings',              '🍗', 5],
        ['Fries & Sides',      'fries-sides',        '🍟', 6],
        ['Pasta',              'pasta',              '🍝', 7],
        ['Sandwiches',         'sandwiches',         '🥪', 8],
        ['Rolls & Paratha',    'rolls-paratha',      '🌯', 9],
        ['Nuggets & Hot Shots','nuggets-hotshots',   '🍗', 10],
        ['Dips & Sauces',      'dips-sauces',        '🥣', 11],
        ['Drinks & Beverages', 'drinks-beverages',   '🥤', 12],
        ['Platters & Combos',  'platters-combos',    '🍽️', 13]
    ];

    $cat_stmt = $pdo->prepare("INSERT INTO categories (name, slug, image, display_order, status) VALUES (?, ?, NULL, ?, 'active')");
    $cat_ids = [];
    foreach ($categories as $c) {
        $cat_stmt->execute([$c[0], $c[1], $c[3]]);
        $cat_ids[$c[1]] = $pdo->lastInsertId();
    }
    echo "Added 13 Categories.\n";

    // 2. ADDONS (20 Add-ons: Paid & 0 FREE Price)
    $addons = [
        // Paid Add-ons
        ['Extra Cheese Slice',         60.00,  0, 'Single melted cheddar cheese slice'],
        ['Extra Melted Mozzarella',   100.00,  0, 'Gooey melted mozzarella cheese topping'],
        ['Pickled Jalapenos',          50.00,  0, 'Spicy tangy jalapeno slices'],
        ['Extra Chicken Patty',       150.00,  0, 'Crispy fried chicken breast patty'],
        ['Extra Beef Patty',          180.00,  0, 'Grilled juicy beef smash patty'],
        ['Smoky BBQ Sauce Dip',        40.00,  0, 'Smoky sweet bar-b-q dipping sauce'],
        ['Creamy Ranch Dip',           60.00,  0, 'Rich garlic herb ranch sauce'],
        ['Sriracha Garlic Dip',        50.00,  0, 'Spicy sriracha mayo dipping sauce'],
        ['Grilled Mushrooms',          70.00,  0, 'Sauteed butter garlic mushrooms'],
        ['Black Olives Portion',       50.00,  0, 'Sliced Spanish black olives'],
        ['Pepperoni Slices (4pc)',    120.00,  0, 'Beef pepperoni slices'],
        ['Crispy Bacon Strips (2pc)', 150.00,  0, 'Crispy chicken bacon strips'],
        ['Sweet Corn Topping',         50.00,  0, 'Golden sweet corn kernels'],
        ['Garlic Mayo Drizzle',        40.00,  0, 'Creamy garlic mayonnaise'],

        // 0 Price (FREE) Add-ons
        ['Free Tomato Ketchup Sachet',   0.00, 1, 'Complimentary tomato ketchup packet'],
        ['Free Chili Garlic Sachet',     0.00, 1, 'Complimentary chili garlic packet'],
        ['Free Complimentary Ranch Dip', 0.00, 1, 'Complimentary dip sauce with order'],
        ['Free Extra Tissues & Straws',  0.00, 1, 'Cutlery, straws and tissue paper set'],
        ['Free Platter Special Dip',     0.00, 1, 'Special chef dip included free'],
        ['Free Fresh Pickle Portion',    0.00, 1, 'Complimentary cucumber pickles']
    ];

    $add_stmt = $pdo->prepare("INSERT INTO addons (name, price, is_free, description, status) VALUES (?, ?, ?, ?, 'active')");
    $addon_ids = [];
    foreach ($addons as $add) {
        $add_stmt->execute([$add[0], $add[1], $add[2], $add[3]]);
        $addon_ids[] = $pdo->lastInsertId();
    }
    echo "Added 20 Add-ons (Paid & Free).\n";

    // 3. DRINKS (Paid & 0 FREE Price)
    $drinks = [
        ['Regular Cold Drink',     '250ml', 90.00,  1],
        ['Tin Can Drink',          '330ml', 140.00, 1],
        ['1 Liter Family Bottle',  '1L',    220.00, 1],
        ['1.5 Liter Mega Bottle',  '1.5L',  190.00, 1],
        ['Mineral Water',          '500ml', 60.00,  0],
        ['Fresh Mint Lemonade',    '350ml', 150.00, 0],
        ['Iced Cold Coffee',       '350ml', 250.00, 0],
        ['Free 250ml Drink (Combo)', '250ml', 0.00, 1], // FREE
        ['Free Water Bottle',       '500ml', 0.00, 1]  // FREE
    ];

    $drk_stmt = $pdo->prepare("INSERT INTO drinks (name, volume, price, can_be_free, status) VALUES (?, ?, ?, ?, 'active')");
    $drink_ids = [];
    foreach ($drinks as $drk) {
        $drk_stmt->execute([$drk[0], $drk[1], $drk[2], $drk[3]]);
        $drink_ids[] = $pdo->lastInsertId();
    }
    echo "Added 9 Drinks (Paid & Free).\n";

    // 4. PRODUCTS & SIZES
    $prod_stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, image, base_price, has_sizes, is_featured, display_order, status) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'active')");
    $size_stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_name, size_label, price, sort_order) VALUES (?, ?, ?, ?, ?)");
    $map_add_stmt = $pdo->prepare("INSERT INTO product_addons (product_id, addon_id) VALUES (?, ?)");
    $map_drk_stmt = $pdo->prepare("INSERT INTO product_drinks (product_id, drink_id) VALUES (?, ?)");

    // --- PIZZAS ---
    $pizzas = [
        ['Chicken Tikka Pizza',  'Spicy chicken tikka chunks, capsicum & onions'],
        ['Chicken Fajita Pizza', 'Grilled fajita chicken with bell peppers & cheese'],
        ['Super Supreme Pizza',  'Loaded chicken, pepperoni, mushrooms, olives & cheese'],
        ['Behari Kabab Pizza',   'Tender behari kabab slices with jalapenos'],
        ['Cheese Lover Pizza',   'Triple layer melted mozzarella & cheddar cheese'],
        ['Malai Boti Pizza',     'Creamy white sauce malai boti chicken pizza']
    ];

    foreach ($pizzas as $p) {
        $prod_stmt->execute([$cat_ids['pizzas'], $p[0], $p[1], 0.00, 1, 1, 1]);
        $pid = $pdo->lastInsertId();
        // Sizes: S / M / L / XL
        $size_stmt->execute([$pid, 'Small (8")',   'S',  550.00, 1]);
        $size_stmt->execute([$pid, 'Medium (10")', 'M', 1200.00, 2]);
        $size_stmt->execute([$pid, 'Large (12")',  'L', 1600.00, 3]);
        $size_stmt->execute([$pid, 'Extra Large (14")', 'XL', 2150.00, 4]);

        // Map first 5 addons & drinks
        for ($i=0; $i<5; $i++) {
            $map_add_stmt->execute([$pid, $addon_ids[$i]]);
        }
        $map_drk_stmt->execute([$pid, $drink_ids[0]]);
        $map_drk_stmt->execute([$pid, $drink_ids[2]]);
    }
    echo "Added Pizzas with S/M/L/XL sizes.\n";

    // --- BURGERS ---
    $burgers = [
        ['Zinger Burger',        'Crispy fried zinger chicken fillet with lettuce & mayo', 500.00],
        ['Tower Burger',         'Double stacked zinger patty with cheese slice', 750.00],
        ['Zinger Cheese Burger', 'Zinger burger loaded with extra cheese slice', 550.00],
        ['BBQ Zinger Burger',    'Zinger burger glazed in smoky barbecue sauce', 550.00],
        ['Smash Beef Burger',    'Double juicy beef patty smashed with caramelized onions', 680.00],
        ['Honey Zinger Burger',  'Sweet honey glaze on crispy chicken breast', 620.00]
    ];

    foreach ($burgers as $b) {
        $prod_stmt->execute([$cat_ids['burgers'], $b[0], $b[1], $b[2], 0, 1, 2]);
        $pid = $pdo->lastInsertId();
        for ($i=0; $i<6; $i++) {
            $map_add_stmt->execute([$pid, $addon_ids[$i]]);
        }
        $map_drk_stmt->execute([$pid, $drink_ids[0]]);
    }
    echo "Added Burgers.\n";

    // --- WINGS ---
    $wings = [
        ['Hot Wings',        'Crispy fried chicken wings tossed in hot spicy sauce'],
        ['Oven Baked Wings', 'Juicy oven baked wings with smoky seasoning'],
        ['Bar-B-Q Wings',    'Classic BBQ glazed wings, smoky & sweet']
    ];

    foreach ($wings as $w) {
        $prod_stmt->execute([$cat_ids['wings'], $w[0], $w[1], 0.00, 1, 1, 3]);
        $pid = $pdo->lastInsertId();
        $size_stmt->execute([$pid, '6 Pieces',  '6pc',  430.00, 1]);
        $size_stmt->execute([$pid, '12 Pieces', '12pc', 830.00, 2]);
    }
    echo "Added Wings with 6pc / 12pc sizes.\n";

    // --- SHAWARMA & WRAPS ---
    $wraps = [
        ['Chicken Shawarma',   'Spiced pita shawarma with garlic mayo & pickles', 250.00],
        ['Zinger Wrap',        'Crispy zinger strips wrapped in soft tortilla', 450.00],
        ['Cheese Shawarma',    'Chicken shawarma loaded with melted cheese', 320.00],
        ['Twister Wrap',       'Crispy fried chicken twister wrap with veggies', 550.00]
    ];

    foreach ($wraps as $wr) {
        $prod_stmt->execute([$cat_ids['shawarma-wraps'], $wr[0], $wr[1], $wr[2], 0, 1, 4]);
    }
    echo "Added Shawarma & Wraps.\n";

    // --- FRIES & SIDES ---
    $fries = [
        ['Plain Salted Fries', 'Classic crispy golden salted fries', 200.00],
        ['Masala Fries',       'Crispy fries tossed in special masala spices', 250.00],
        ['Mayo Garlic Fries',  'Fries topped with creamy garlic mayo', 320.00],
        ['Loaded Pizza Fries', 'Fries topped with pizza sauce, chicken & cheese', 490.00]
    ];

    foreach ($fries as $fr) {
        $prod_stmt->execute([$cat_ids['fries-sides'], $fr[0], $fr[1], $fr[2], 0, 0, 5]);
    }
    echo "Added Fries & Sides.\n";

    echo "ALL DATA SUCCESSFULLY SEEDED INTO RESTAURANT DATABASE!\n";

} catch (Exception $e) {
    echo "SEED ERROR: " . $e->getMessage() . "\n";
}
