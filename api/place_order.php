<?php
// api/place_order.php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// 1. Check if Cart is Empty
if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty. Please add items to checkout.']);
    exit();
}

// 2. Validate Area
if (!isset($_SESSION['delivery_area_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a delivery location first.']);
    exit();
}
$area_id = intval($_SESSION['delivery_area_id']);

// 3. Retrieve and sanitize input fields
$customer_name = sanitize($_POST['customer_name'] ?? '');
$phone         = sanitize($_POST['phone'] ?? '');
$address       = sanitize($_POST['address'] ?? '');
$order_notes   = sanitize($_POST['order_notes'] ?? '');

// 4. Server-Side Validations
if (empty($customer_name)) {
    echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
    exit();
}
if (empty($phone) || strlen($phone) < 7) {
    echo json_encode(['success' => false, 'message' => 'A valid contact number is required.']);
    exit();
}
if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Delivery address is required.']);
    exit();
}

// 5. Hydrate Cart Details & Calculate Totals
$cart_details = get_cart_details($pdo);
$subtotal = $cart_details['subtotal'];
$delivery_fee = $cart_details['delivery_fee'];
$grand_total = $cart_details['grand_total'];

try {
    // 6. DB Transaction Start
    $pdo->beginTransaction();

    // 7. Insert into Orders Table
    $user_id = is_user_logged_in() ? get_logged_in_user_id() : null;
    $order_stmt = $pdo->prepare("
        INSERT INTO `orders` (
            `user_id`, `customer_name`, `phone`, `address`, `area_id`, 
            `subtotal`, `delivery_charge`, `grand_total`, 
            `status`, `payment_method`, `notes`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Cash on Delivery', ?)
    ");
    $order_stmt->execute([
        $user_id,
        $customer_name,
        $phone,
        $address,
        $area_id,
        $subtotal,
        $delivery_fee,
        $grand_total,
        $order_notes
    ]);
    
    $order_id = $pdo->lastInsertId();

    // 8. Insert Order Items
    $item_stmt = $pdo->prepare("
        INSERT INTO `order_items` (
            `order_id`, `product_id`, `product_name`, `size_name`, 
            `addons`, `drink_name`, `quantity`, 
            `unit_price`, `total_price`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart_details['items'] as $item) {
        // Serialize addons array to JSON for database storage
        $addons_json = !empty($item['addons']) ? json_encode($item['addons'], JSON_UNESCAPED_UNICODE) : null;
        $drink_name = !empty($item['drink_name']) ? $item['drink_name'] : null;

        $item_stmt->execute([
            $order_id,
            $item['product_id'],
            $item['product_name'] ?? 'Product',
            $item['size_name'],
            $addons_json,
            $drink_name,
            $item['quantity'],
            $item['unit_price'],
            $item['line_total']
        ]);
    }

    // 9. Commit transaction
    $pdo->commit();

    // 10. Clear Session Cart
    clear_cart();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order placed successfully!'
    ]);
    exit();

} catch (Exception $e) {
    // Rollback on database failure
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error placing order: ' . $e->getMessage()
    ]);
    exit();
}
?>
