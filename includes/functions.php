<?php
// includes/functions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// Sanitize inputs
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format Price
function format_price($price) {
    return 'Rs. ' . number_format($price, 2);
}

// Redirect helpers
function redirect($url) {
    header("Location: $url");
    exit();
}

// Add to Cart
function add_to_cart($pdo, $product_id, $size_id, $addon_ids, $drink_id, $quantity) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $product_id = intval($product_id);
    $size_id = intval($size_id);
    $drink_id = intval($drink_id);
    $quantity = intval($quantity);
    
    // Normalize and sort addons for key generation
    $addon_ids = is_array($addon_ids) ? array_map('intval', $addon_ids) : [];
    sort($addon_ids);

    // Create a unique key for this customized item
    $cart_key = md5($product_id . '-' . $size_id . '-' . implode(',', $addon_ids) . '-' . $drink_id);

    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'size_id'    => $size_id,
            'addon_ids'  => $addon_ids,
            'drink_id'   => $drink_id,
            'quantity'   => $quantity
        ];
    }
    return true;
}

// Update Cart Quantity
function update_cart_qty($cart_key, $qty) {
    $qty = intval($qty);
    if (isset($_SESSION['cart'][$cart_key])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$cart_key]);
        } else {
            $_SESSION['cart'][$cart_key]['quantity'] = $qty;
        }
        return true;
    }
    return false;
}

// Remove from Cart
function remove_from_cart($cart_key) {
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        return true;
    }
    return false;
}

// Clear Cart
function clear_cart() {
    $_SESSION['cart'] = [];
}

// Get Cart Details (Hydrates raw session IDs into full product data)
function get_cart_details($pdo) {
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $hydrated_items = [];
    $subtotal = 0.00;

    foreach ($cart as $key => $item) {
        // Fetch Product
        $stmt = $pdo->prepare("SELECT name, image, base_price, category_id FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        
        if (!$product) {
            continue;
        }

        // Fetch Size Price
        $size_name = 'Regular';
        $size_price = $product['base_price'];
        if ($item['size_id'] > 0) {
            $stmt = $pdo->prepare("SELECT size_name, price FROM product_sizes WHERE id = ? AND product_id = ?");
            $stmt->execute([$item['size_id'], $item['product_id']]);
            $size_info = $stmt->fetch();
            if ($size_info) {
                $size_name = $size_info['size_name'];
                $size_price = $size_info['price'];
            }
        }

        // Fetch Addons
        $addons_list = [];
        $addons_price = 0.00;
        if (!empty($item['addon_ids'])) {
            $placeholders = implode(',', array_fill(0, count($item['addon_ids']), '?'));
            $stmt = $pdo->prepare("SELECT id, name, price FROM addons WHERE id IN ($placeholders)");
            $stmt->execute($item['addon_ids']);
            $addons_db = $stmt->fetchAll();
            foreach ($addons_db as $adb) {
                $addons_list[] = [
                    'id' => $adb['id'],
                    'name' => $adb['name'],
                    'price' => $adb['price']
                ];
                $addons_price += $adb['price'];
            }
        }

        // Fetch Drink
        $drink_name = '';
        $drink_price = 0.00;
        if ($item['drink_id'] > 0) {
            $stmt = $pdo->prepare("SELECT name, price FROM drinks WHERE id = ?");
            $stmt->execute([$item['drink_id']]);
            $drink_info = $stmt->fetch();
            if ($drink_info) {
                $drink_name = $drink_info['name'];
                $drink_price = $drink_info['price'];
            }
        }

        $unit_price = $size_price + $addons_price + $drink_price;
        $line_total = $unit_price * $item['quantity'];
        $subtotal += $line_total;

        // Resolve Image URL
        $default_images = [
            1 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80', // Burger
            2 => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80', // Pizza
            3 => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80', // Zinger
            4 => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=400&q=80', // Wings
            5 => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=400&q=80', // Pasta
            6 => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=400&q=80'  // Drinks
        ];
        $img_url = $default_images[$product['category_id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80';
        if (!empty($product['image']) && file_exists(dirname(__DIR__) . '/assets/images/uploads/' . $product['image'])) {
            $img_url = 'assets/images/uploads/' . $product['image'];
        }

        $hydrated_items[$key] = [
            'product_id' => $item['product_id'],
            'product_name' => $product['name'],
            'image' => $product['image'],
            'image_url' => $img_url,
            'size_name' => $size_name,
            'addons' => $addons_list,
            'drink_name' => $drink_name,
            'quantity' => $item['quantity'],
            'unit_price' => $unit_price,
            'line_total' => $line_total
        ];
    }

    // Delivery Fee calculation
    $delivery_fee = 0.00;
    if (isset($_SESSION['delivery_area_id'])) {
        $stmt = $pdo->prepare("SELECT delivery_charge AS delivery_fee FROM areas WHERE id = ?");
        $stmt->execute([$_SESSION['delivery_area_id']]);
        $delivery_fee = floatval($stmt->fetchColumn());
    }

    $grand_total = $subtotal + $delivery_fee;

    return [
        'items' => $hydrated_items,
        'subtotal' => $subtotal,
        'delivery_fee' => $delivery_fee,
        'grand_total' => $grand_total
    ];
}

// Get Cart Item Count
function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

// Get Setting Helper
function get_setting($key, $default = '') {
    $path = __DIR__ . '/settings.json';
    if (file_exists($path)) {
        $settings = json_decode(file_get_contents($path), true);
        return $settings[$key] ?? $default;
    }
    return $default;
}

// Customer account check helpers
function is_user_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function get_logged_in_user_id() {
    return is_user_logged_in() ? intval($_SESSION['user_id']) : null;
}

// Wishlist helpers
function is_in_wishlist($pdo, $user_id, $product_id) {
    if (!$user_id) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetchColumn() > 0;
}

// Customer stats retriever
function get_user_stats($pdo, $user_id) {
    if (!$user_id) {
        return ['total_orders' => 0, 'total_spent' => 0.00, 'total_wishlist' => 0];
    }
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetchColumn();
    $total_spent = $pdo->query("SELECT SUM(grand_total) FROM orders WHERE user_id = $user_id AND status != 'Cancelled'")->fetchColumn();
    $total_spent = $total_spent ? floatval($total_spent) : 0.00;
    
    $total_wishlist = $pdo->query("SELECT COUNT(*) FROM wishlists WHERE user_id = $user_id")->fetchColumn();
    
    return [
        'total_orders' => $total_orders,
        'total_spent' => $total_spent,
        'total_wishlist' => $total_wishlist
    ];
}
?>
