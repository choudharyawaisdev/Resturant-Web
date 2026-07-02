<?php
// api/wishlist.php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your wishlist.']);
    exit();
}

$user_id = get_logged_in_user_id();
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product choice.']);
    exit();
}

try {
    // Check if item exists in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $wishlist_id = $stmt->fetchColumn();

    if ($wishlist_id) {
        // Remove it
        $del = $pdo->prepare("DELETE FROM wishlists WHERE id = ?");
        $del->execute([$wishlist_id]);
        echo json_encode([
            'success' => true,
            'is_added' => false,
            'message' => 'Removed from wishlist!'
        ]);
        exit();
    } else {
        // Add it
        $ins = $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $ins->execute([$user_id, $product_id]);
        echo json_encode([
            'success' => true,
            'is_added' => true,
            'message' => 'Added to wishlist!'
        ]);
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error toggling wishlist item: ' . $e->getMessage()]);
    exit();
}
?>
