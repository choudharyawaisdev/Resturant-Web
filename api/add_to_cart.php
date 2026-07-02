<?php
// api/add_to_cart.php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Set Location Action
    if ($action === 'set_location') {
        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        if ($area_id > 0) {
            $_SESSION['delivery_area_id'] = $area_id;
            echo json_encode(['success' => true]);
            exit();
        }
        echo json_encode(['success' => false, 'message' => 'Invalid area ID']);
        exit();
    }

    // Add Item to Cart Action
    if ($action === 'add') {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $size_id = isset($_POST['size_id']) ? intval($_POST['size_id']) : 0;
        $addon_ids = isset($_POST['addons']) ? $_POST['addons'] : [];
        $drink_id = isset($_POST['drink_id']) ? intval($_POST['drink_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit();
        }

        $res = add_to_cart($pdo, $product_id, $size_id, $addon_ids, $drink_id, $quantity);
        if ($res) {
            echo json_encode([
                'success' => true,
                'cart_count' => get_cart_count()
            ]);
            exit();
        }
        echo json_encode(['success' => false, 'message' => 'Failed to add item to cart.']);
        exit();
    }

    // Update Cart Item Quantity Action
    if ($action === 'update') {
        $cart_key = isset($_POST['cart_key']) ? sanitize($_POST['cart_key']) : '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

        if (empty($cart_key)) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart key']);
            exit();
        }

        $res = update_cart_qty($cart_key, $quantity);
        echo json_encode([
            'success' => $res,
            'cart_count' => get_cart_count()
        ]);
        exit();
    }

    // Remove Item Action
    if ($action === 'remove') {
        $cart_key = isset($_POST['cart_key']) ? sanitize($_POST['cart_key']) : '';

        if (empty($cart_key)) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart key']);
            exit();
        }

        $res = remove_from_cart($cart_key);
        echo json_encode([
            'success' => $res,
            'cart_count' => get_cart_count()
        ]);
        exit();
    }
}

// GET requests: e.g. retrieve hydrated cart details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_cart') {
        $cart_details = get_cart_details($pdo);
        $cart_details['cart_count'] = get_cart_count();
        echo json_encode($cart_details);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action or request method.']);
exit();
?>
