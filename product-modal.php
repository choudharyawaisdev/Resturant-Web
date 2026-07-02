<?php
// product-modal.php
require_once __DIR__ . '/includes/functions.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo '<div class="modal-body"><p class="text-danger">Invalid product selection.</p></div>';
    exit();
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="modal-body"><p class="text-danger">Product not found or currently unavailable.</p></div>';
    exit();
}

// Fetch sizes
$size_stmt = $pdo->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY price ASC");
$size_stmt->execute([$product_id]);
$sizes = $size_stmt->fetchAll();

// Fetch addons (via product_addons map)
$addon_stmt = $pdo->prepare("
    SELECT a.* FROM addons a 
    JOIN product_addons pa ON a.id = pa.addon_id 
    WHERE pa.product_id = ? AND a.status = 'active'
");
$addon_stmt->execute([$product_id]);
$addons = $addon_stmt->fetchAll();

// Fetch drinks (via product_drinks map)
$drink_stmt = $pdo->prepare("
    SELECT d.* FROM drinks d 
    JOIN product_drinks pd ON d.id = pd.drink_id 
    WHERE pd.product_id = ? AND d.status = 'active'
");
$drink_stmt->execute([$product_id]);
$drinks = $drink_stmt->fetchAll();

// Setup Image Fallback
$default_images = [
    1 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80', // Burger
    2 => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80', // Pizza
    3 => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80', // Zinger
    4 => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=400&q=80', // Wings
    5 => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=400&q=80', // Pasta
    6 => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=400&q=80'  // Drinks
];
$img_url = $default_images[$product['category_id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80';
if (!empty($product['image']) && file_exists(__DIR__ . '/assets/images/uploads/' . $product['image'])) {
    $img_url = 'assets/images/uploads/' . $product['image'];
}
?>

<form id="customizationForm" data-base-price="<?= $product['base_price'] ?>">
    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
    
    <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body pt-0">
        <div class="row">
            <!-- Left Side: Image -->
            <div class="col-md-5 mb-3 mb-md-0">
                <div class="rounded-3 overflow-hidden shadow-sm" style="height: 250px; background-color: #f8f9fa;">
                    <img src="<?= $img_url ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= sanitize($product['name']) ?>" onerror="this.src='https://placehold.co/300x300/FFF8F0/FF6B00?text=Food'">
                </div>
            </div>

            <!-- Right Side: Description and Customizations -->
            <div class="col-md-7">
                <h3 class="fw-bold mb-1"><?= sanitize($product['name']) ?></h3>
                <p class="text-muted small mb-4"><?= sanitize($product['description']) ?></p>

                <!-- 1. Size Selection -->
                <?php if (!empty($sizes)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-aspect-ratio text-orange me-1"></i> Choose Size:</h6>
                        <div class="row g-2">
                            <?php foreach ($sizes as $idx => $size): ?>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="size_id" id="size_<?= $size['id'] ?>" value="<?= $size['id'] ?>" data-price="<?= $size['price'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-orange w-100 py-2.5 border-radius-md text-start px-3" for="size_<?= $size['id'] ?>">
                                        <span class="d-block fw-bold small text-dark"><?= sanitize($size['size_name']) ?></span>
                                        <span class="d-block text-muted small mt-1">+Rs. <?= number_format($size['price'], 0) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. Addons Selection -->
                <?php if (!empty($addons)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-plus-circle text-orange me-1"></i> Add Extra Toppings:</h6>
                        <select class="form-select form-select-lg border-radius-md" id="addonDropdown">
                            <option value="0" data-price="0.00" selected>-- Choose Extra Topping --</option>
                            <?php foreach ($addons as $addon): ?>
                                <option value="<?= $addon['id'] ?>" data-price="<?= $addon['price'] ?>" data-name="<?= sanitize($addon['name']) ?>">
                                    <?= sanitize($addon['name']) ?> (+Rs. <?= number_format($addon['price'], 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Selected toppings will be listed here as dynamic tags -->
                        <div id="selectedAddonsContainer" class="d-flex flex-wrap gap-2 mt-3"></div>
                    </div>
                <?php endif; ?>

                <!-- 3. Drinks Selection -->
                <?php if (!empty($drinks)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cup-straw text-orange me-1"></i> Add Chilled Drink:</h6>
                        <select class="form-select form-select-lg border-radius-md" name="drink_id">
                            <option value="0" data-price="0.00" selected>-- No Drink --</option>
                            <?php foreach ($drinks as $drink): ?>
                                <option value="<?= $drink['id'] ?>" data-price="<?= $drink['price'] ?>">
                                    <?= sanitize($drink['name']) ?> (+Rs. <?= number_format($drink['price'], 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- 4. Quantity Stepper -->
                <div class="mb-2 d-flex align-items-center">
                    <h6 class="fw-bold text-dark mb-0 me-3"><i class="bi bi-stack text-orange me-1"></i> Quantity:</h6>
                    <div class="quantity-stepper">
                        <button type="button" class="btn-qty-minus">-</button>
                        <input type="text" name="quantity" value="1" readonly>
                        <button type="button" class="btn-qty-plus">+</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Floating Price & Add to Cart Footer -->
    <div class="modal-price-footer">
        <div>
            <p class="modal-price-label">Total Custom Price</p>
            <span class="modal-price-val" id="modalTotalPrice">Rs. 0.00</span>
        </div>
        <button type="submit" class="btn btn-primary-orange px-5 py-3 fw-bold rounded-3">
            Add to Order Basket <i class="bi bi-bag-plus ms-1"></i>
        </button>
    </div>
</form>
