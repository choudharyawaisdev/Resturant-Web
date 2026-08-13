-- ============================================================
--  Cafe Chinos -- Complete Menu Seed
--  Database: restaurant_db
-- ============================================================

USE restaurant_db;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE product_drinks;
TRUNCATE TABLE product_addons;
TRUNCATE TABLE product_sizes;
TRUNCATE TABLE products;
TRUNCATE TABLE addons;
TRUNCATE TABLE drinks;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

-- CATEGORIES
INSERT INTO categories (id, name, image, display_order, status) VALUES
(1,  'Burgers',        'burger_cat.jpg',     1,  'active'),
(2,  'Pizza Regular',  'pizza_cat.jpg',      2,  'active'),
(3,  'Chinos Special', 'chinos_special.jpg', 3,  'active'),
(4,  'Stuffed Crust',  'stuffed_crust.jpg',  4,  'active'),
(5,  'Wings',          'wings_cat.jpg',      5,  'active'),
(6,  'Nuggets',        'nuggets_cat.jpg',    6,  'active'),
(7,  'Hot Shots',      'hotshots_cat.jpg',   7,  'active'),
(8,  'Fries',          'fries_cat.jpg',      8,  'active'),
(9,  'Pasta',          'pasta_cat.jpg',      9,  'active'),
(10, 'Sandwich',       'sandwich_cat.jpg',   10, 'active'),
(11, 'Rolls',          'rolls_cat.jpg',      11, 'active'),
(12, 'Wraps',          'wraps_cat.jpg',      12, 'active'),
(13, 'Hot Deals',      'deals_cat.jpg',      13, 'active'),
(14, 'Dips & Drinks',  'drinks_cat.jpg',     14, 'active'),
(15, 'Calzone',        'calzone_cat.jpg',    15, 'active'),
(16, 'Platter',        'platter_cat.jpg',    16, 'active');

-- DRINKS
INSERT INTO drinks (id, name, price, status) VALUES
(1, 'Regular Drink (330ml)',  90.00,  'active'),
(2, '500ml Bottle',          130.00, 'active'),
(3, '1 Ltr Bottle',          190.00, 'active'),
(4, '1.5 Ltr Bottle',        150.00, 'active'),
(5, 'Tin Pack',              140.00, 'active');

-- ADDONS
INSERT INTO addons (id, name, price, status) VALUES
(1,  'Extra Chicken (S)',  100.00, 'active'),
(2,  'Extra Chicken (M)',  150.00, 'active'),
(3,  'Extra Chicken (L)',  200.00, 'active'),
(4,  'Extra Cheese (S)',   100.00, 'active'),
(5,  'Extra Cheese (M)',   150.00, 'active'),
(6,  'Extra Cheese (L)',   200.00, 'active'),
(7,  'Extra Sauce (S)',    100.00, 'active'),
(8,  'Extra Sauce (M)',    150.00, 'active'),
(9,  'Extra Sauce (L)',    200.00, 'active'),
(10, 'Ranch Sauce',         60.00, 'active'),
(11, 'Dip Sauce',           50.00, 'active'),
(12, 'Add Fries',           80.00, 'active'),
(13, 'Jalapenos',           40.00, 'active');

-- BURGERS (cat 1)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(1,  1, 'Tower Burger',         'Signature stacked burger with double patty, cheese, and fresh veggies.',     'tower_burger.jpg',         750.00, 'active'),
(2,  1, 'Zinger Burger',        'Golden crispy chicken fillet with spicy mayo and fresh lettuce.',             'zinger_burger.jpg',        580.00, 'active'),
(3,  1, 'Zinger Cheese Burger', 'Crispy zinger fillet loaded with melted cheddar cheese.',                    'zinger_cheese_burger.jpg', 550.00, 'active'),
(4,  1, 'Hearty Zinger Burger', 'Jumbo crispy zinger with double toppings and rich house sauce.',             'hearty_zinger_burger.jpg', 850.00, 'active'),
(5,  1, 'Zinger with Fries',    'Classic zinger burger served with a side of crispy fries.',                  'zinger_fries.jpg',         600.00, 'active'),
(6,  1, 'Chicken Patty Burger', 'Soft bun with a juicy grilled chicken patty, lettuce, and mayo.',            'chicken_patty_burger.jpg', 400.00, 'active'),
(7,  1, 'Bar-B-Q Zinger Burger','Smoky BBQ sauce-glazed crispy zinger with caramelised onions.',             'bbq_zinger_burger.jpg',    550.00, 'active');

-- PIZZA REGULAR (cat 2)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(8,  2, 'Chicken Tikka Pizza',  'Spicy tikka chunks, onions, green peppers and rich mozzarella.',             'tikka_pizza.jpg',          550.00, 'active'),
(9,  2, 'Chicken Fajita Pizza', 'Fajita chicken, bell peppers, olives and cheese blend.',                     'fajita_pizza.jpg',         550.00, 'active'),
(10, 2, 'Super Supreme Pizza',  'Loaded with chicken, beef, veggies, mushrooms and double cheese.',           'supreme_pizza.jpg',        550.00, 'active'),
(11, 2, 'Tandoori Pizza',       'Tandoori-spiced chicken on a rich tomato base with mozzarella.',             'tandoori_pizza.jpg',       550.00, 'active'),
(12, 2, 'Chicken Lover Pizza',  'Packed with premium chicken toppings for true chicken fans.',                'chicken_lover_pizza.jpg',  550.00, 'active'),
(13, 2, 'Cheese Lover Pizza',   'Triple cheese blend with creamy white sauce and herbs.',                     'cheese_lover_pizza.jpg',   550.00, 'active');

-- Pizza Regular Sizes
INSERT INTO product_sizes (product_id, size_name, price) VALUES
(8,  'Small (S)',    550.00),(8,  'Medium (M)',  1200.00),(8,  'Large (L)',  1600.00),(8,  'XL',  2150.00),
(9,  'Small (S)',    550.00),(9,  'Medium (M)',  1200.00),(9,  'Large (L)',  1600.00),(9,  'XL',  2150.00),
(10, 'Small (S)',    550.00),(10, 'Medium (M)', 1200.00),(10, 'Large (L)', 1600.00),(10, 'XL', 2150.00),
(11, 'Small (S)',    550.00),(11, 'Medium (M)', 1200.00),(11, 'Large (L)', 1600.00),(11, 'XL', 2150.00),
(12, 'Small (S)',    550.00),(12, 'Medium (M)', 1200.00),(12, 'Large (L)', 1600.00),(12, 'XL', 2150.00),
(13, 'Small (S)',    550.00),(13, 'Medium (M)', 1200.00),(13, 'Large (L)', 1600.00),(13, 'XL', 2150.00);

-- CHINOS SPECIAL (cat 3)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(14, 3, 'Behari Kabab Royal Crust', 'Tender behari kabab on signature royal crust with premium cheese.',     'behari_kabab_pizza.jpg',  450.00, 'active'),
(15, 3, 'Malai Boti Pizza',          'Creamy malai boti chicken with special Chinos spice blend.',            'malai_boti_pizza.jpg',    600.00, 'active'),
(16, 3, 'Super Chinos Pizza',        'The ultimate Chinos creation loaded with chef special toppings.',       'super_chinos_pizza.jpg',  600.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(14, 'Mini (MI)',   450.00),(14, 'Large (L)',  2000.00),(14, 'XL',        2490.00),
(15, 'Small (S)',   600.00),(15, 'Medium (M)', 1250.00),(15, 'Large (L)', 1750.00),(15, 'XL', 2300.00),
(16, 'Small (S)',   600.00),(16, 'Medium (M)', 1250.00),(16, 'Large (L)', 1750.00),(16, 'XL', 2300.00);

-- STUFFED CRUST (cat 4)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(17, 4, 'Cheese Stuffed Crust Pizza', 'Gooey cheese-filled crust with favourite pizza toppings.',            'cheese_stuffed_crust.jpg', 1450.00, 'active'),
(18, 4, 'Seekh Kabab Crust Pizza',    'Juicy seekh kabab stuffed into a crispy golden crust.',               'seekh_kabab_crust.jpg',    1450.00, 'active'),
(19, 4, 'Mexican Pizza',              'Double layer filled with spicy chicken, cheese and jalapeno sauce.',  'mexican_pizza.jpg',        1850.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(17, 'Medium (M)', 1450.00),(17, 'Large (L)', 2000.00),(17, 'XL', 2490.00),
(18, 'Medium (M)', 1450.00),(18, 'Large (L)', 2000.00),(18, 'XL', 2490.00),
(19, 'Medium (M)', 1850.00),(19, 'Large (L)', 2350.00),(19, 'XL', 2950.00);

-- WINGS (cat 5)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(20, 5, 'Hot Wings',        'Fiery hot sauce glazed crispy chicken wings.',              'hot_wings.jpg',  400.00, 'active'),
(21, 5, 'Oven Baked Wings', 'Slow-baked juicy wings with herbs and spices.',            'oven_wings.jpg', 430.00, 'active'),
(22, 5, 'Bar-B-Q Wings',    'Smoky BBQ glazed wings with tangy dipping sauce.',         'bbq_wings.jpg',  430.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(20, '5 Pieces',  400.00),(20, '10 Pieces', 790.00),
(21, '6 Pieces',  430.00),(21, '12 Pieces', 830.00),
(22, '6 Pieces',  430.00),(22, '12 Pieces', 830.00);

-- NUGGETS (cat 6)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(23, 6, 'Chicken Nuggets', 'Crispy golden chicken nuggets served with dipping sauce.', 'nuggets.jpg', 300.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(23, '6 Pieces',  300.00),(23, '12 Pieces', 600.00);

-- HOT SHOTS (cat 7)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(24, 7, 'Hot Shots', 'Spicy bite-sized crispy chicken bites with signature hot sauce.', 'hot_shots.jpg', 450.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(24, '10 Pieces', 450.00),(24, '15 Pieces', 650.00);

-- FRIES (cat 8)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(25, 8, 'Plain Fries',  'Classic golden crispy fries lightly salted.',              'plain_fries.jpg',  200.00, 'active'),
(26, 8, 'Mayo Fries',   'Crispy fries drizzled with creamy mayonnaise.',            'mayo_fries.jpg',   350.00, 'active'),
(27, 8, 'Masala Fries', 'Spice-dusted fries with tangy chaat masala twist.',       'masala_fries.jpg', 300.00, 'active'),
(28, 8, 'Loaded Fries', 'Fries topped with cheese sauce, jalapenos and chicken.',  'loaded_fries.jpg', 410.00, 'active'),
(29, 8, 'Pizza Fries',  'Fries smothered with pizza sauce and melted mozzarella.', 'pizza_fries.jpg',  450.00, 'active'),
(30, 8, 'Cheese Fries', 'Fries generously covered with rich cheddar cheese sauce.','cheese_fries.jpg', 490.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(25, 'Regular', 200.00),(25, 'Family', 590.00),
(26, 'Regular', 350.00),(26, 'Family', 690.00),
(27, 'Regular', 300.00),(27, 'Family', 590.00),
(28, 'Regular', 410.00),(28, 'Family', 750.00),
(29, 'Small',   450.00),(29, 'Large',  750.00),
(30, 'Regular', 490.00),(30, 'Family', 750.00);

-- PASTA (cat 9)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(31, 9, 'Chef Special Pasta',   'Chef signature pasta with special Chinos spice blend and toppings.','chef_pasta.jpg',   600.00, 'active'),
(32, 9, 'Creamy Chicken Pasta', 'Tender chicken in a luscious creamy white sauce pasta.',            'creamy_pasta.jpg', 600.00, 'active'),
(33, 9, 'Lasagne Pasta',        'Classic layered lasagne with minced meat and bechamel sauce.',      'lasagne.jpg',      800.00, 'active');

INSERT INTO product_sizes (product_id, size_name, price) VALUES
(31, 'Small (S)', 600.00),(31, 'Large (L)', 800.00),
(32, 'Small (S)', 600.00),(32, 'Large (L)', 800.00);

-- SANDWICH (cat 10)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(34, 10, 'Special Sandwich with Fries',  'Chinos special grilled chicken sandwich served with crispy fries.',  'special_sandwich.jpg',  870.00, 'active'),
(35, 10, 'Mexican Sandwich with Fries',  'Spicy Mexican-style chicken sandwich with jalapenos and fries.',     'mexican_sandwich.jpg',  870.00, 'active'),
(36, 10, 'Jalapeno Sandwich',            'Loaded with crispy chicken and generous jalapeno slices.',           'jalapeno_sandwich.jpg', 870.00, 'active'),
(37, 10, 'Crispy Sandwich',              'Extra crispy chicken fillet sandwich with fresh veggies.',           'crispy_sandwich.jpg',   950.00, 'active');

-- ROLLS (cat 11)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(38, 11, '4 Chicken Spin Rolls', 'Four crispy chicken spin rolls with chutney dip.',             'chicken_spin_rolls.jpg',   690.00, 'active'),
(39, 11, '4 Behari Rolls',       'Four juicy behari kabab rolls wrapped in soft paratha.',       'behari_rolls.jpg',         690.00, 'active'),
(40, 11, 'Tikke Paratha Roll',   'Succulent tikka chicken wrapped in a fresh soft paratha.',     'tikke_paratha_roll.jpg',   370.00, 'active'),
(41, 11, 'Chapli Kabab Paratha', 'Traditional chapli kabab with chutneys in a layered paratha.','chapli_kabab_paratha.jpg', 690.00, 'active'),
(42, 11, 'Crunchy Paratha Roll', 'Crispy crunchy chicken wrapped in a flaky golden paratha.',   'crunchy_paratha_roll.jpg', 370.00, 'active');

-- WRAPS (cat 12)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(43, 12, 'Twister Wrap', 'Grilled chicken strips with fresh veggies in a flour tortilla.',  'twister_wrap.jpg', 750.00, 'active'),
(44, 12, 'Grilled Wrap', 'Juicy grilled chicken with cheese and special sauce in a wrap.',  'grilled_wrap.jpg', 750.00, 'active');

-- HOT DEALS (cat 13)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(45, 13, 'Deal-1', '1 Small Pizza + 1 Regular Drink',               'deal1.jpg',   610.00, 'active'),
(46, 13, 'Deal-2', 'Regular + Medium Pizza + 1 Litre Drink',        'deal2.jpg',  1250.00, 'active'),
(47, 13, 'Deal-3', '1 Large Pizza + 1 Litre Drink',                 'deal3.jpg',  1690.00, 'active'),
(48, 13, 'Deal-4', '1 XL Pizza + 1 Litre Drink',                    'deal4.jpg',  2290.00, 'active'),
(49, 13, 'Deal-5', '1 Zinger Burger + 1 Small Pasta + 1 Reg Drink', 'deal5.jpg',   970.00, 'active'),
(50, 13, 'Deal-6', '1 Large Pasta + 1 Regular Drink',               'deal6.jpg',   750.00, 'active'),
(51, 13, 'Deal-7', '1 Zinger Burger + 1.5 Litre Drink',             'deal7.jpg',  1640.00, 'active');

-- DIPS & DRINKS (cat 14)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(52, 14, 'Ranch Sauce',   'Creamy homemade ranch dipping sauce.',      'ranch_sauce.jpg',   60.00, 'active'),
(53, 14, 'Regular Drink', 'Chilled soft drink 330ml.',                 'regular_drink.jpg', 90.00, 'active'),
(54, 14, '500ml Drink',   'Chilled soft drink 500ml bottle.',          'drink_500.jpg',    130.00, 'active'),
(55, 14, '1 Ltr Drink',   'Chilled soft drink 1 litre bottle.',        'drink_1ltr.jpg',   190.00, 'active'),
(56, 14, '1.5 Ltr Drink', 'Chilled soft drink 1.5 litre bottle.',      'drink_15.jpg',     150.00, 'active'),
(57, 14, 'Tin Pack',      'Premium cold-drink tin pack 330ml.',        'tin_pack.jpg',     140.00, 'active');

-- CALZONE (cat 15)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(58, 15, 'Calzone Chunks', 'Rich Italian-flavour calzone chunks - the ultimate indulgence!', 'calzone.jpg', 1250.00, 'active');

-- PLATTER (cat 16)
INSERT INTO products (id, category_id, name, description, image, base_price, status) VALUES
(59, 16, 'Special Platter', '5 Oven Baked Wings + 4 Pin Spin Rolls + 1 Fries + 1 Dip Sauce', 'special_platter.jpg', 1049.00, 'active');

-- PRODUCT ADDONS (pizza toppings)
INSERT INTO product_addons (product_id, addon_id) VALUES
(8,1),(8,2),(8,3),(8,4),(8,5),(8,6),(8,7),(8,8),(8,9),
(9,1),(9,2),(9,3),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),
(10,1),(10,2),(10,3),(10,4),(10,5),(10,6),(10,7),(10,8),(10,9),
(11,1),(11,2),(11,3),(11,4),(11,5),(11,6),(11,7),(11,8),(11,9),
(12,1),(12,2),(12,3),(12,4),(12,5),(12,6),(12,7),(12,8),(12,9),
(13,1),(13,2),(13,3),(13,4),(13,5),(13,6),(13,7),(13,8),(13,9),
(14,1),(14,2),(14,3),(14,4),(14,5),(14,6),
(15,1),(15,2),(15,3),(15,4),(15,5),(15,6),
(16,1),(16,2),(16,3),(16,4),(16,5),(16,6),
(17,1),(17,2),(17,3),(17,4),(17,5),(17,6),
(18,1),(18,2),(18,3),(18,4),(18,5),(18,6),
(19,1),(19,2),(19,3),(19,4),(19,5),(19,6),
(1,13),(2,13),(3,13),(4,13),(5,13),(6,13),(7,13),
(25,10),(26,10),(27,10),(28,10),(29,10),(30,10),
(20,11),(21,11),(22,11),
(23,10),(23,11),(24,10),(24,11);

-- PRODUCT DRINKS (deals)
INSERT INTO product_drinks (product_id, drink_id) VALUES
(45,1),(46,3),(47,3),(48,3),(49,1),(50,1),(51,4);

-- VERIFY
SELECT 'Categories' AS tbl, COUNT(*) AS total FROM categories
UNION ALL SELECT 'Products',    COUNT(*) FROM products
UNION ALL SELECT 'Sizes',       COUNT(*) FROM product_sizes
UNION ALL SELECT 'Addons',      COUNT(*) FROM addons
UNION ALL SELECT 'Prod Addons', COUNT(*) FROM product_addons
UNION ALL SELECT 'Drinks',      COUNT(*) FROM drinks
UNION ALL SELECT 'Prod Drinks', COUNT(*) FROM product_drinks;

