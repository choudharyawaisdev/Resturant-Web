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
    
    <div class="modal-header border-0 pb-0 pe-4 pt-4">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body px-4 pt-1 pb-4">
        <div class="row g-4">
            <!-- Left Side: Product Image & Badges -->
            <div class="col-md-5">
                <div class="rounded-4 overflow-hidden shadow-sm position-relative bg-light h-100" style="min-height: 260px;">
                    <img src="<?= $img_url ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= sanitize($product['name']) ?>" onerror="this.src='https://placehold.co/400x400/FFF8F0/FF6B00?text=Food'">
                    <span class="position-absolute top-0 start-0 m-3 px-3 py-1 bg-warning text-dark fw-bold rounded-pill shadow-sm" style="font-size: 11px; letter-spacing: 0.5px;">Chef Special</span>
                </div>
            </div>

            <!-- Right Side: Description and Customizations -->
            <div class="col-md-7">
                <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.5px;"><?= sanitize($product['name']) ?></h3>
                <p class="text-muted small mb-4" style="line-height: 1.5;"><?= sanitize($product['description']) ?></p>

                <!-- 1. Size Selection -->
                <?php if (!empty($sizes)): ?>
                    <div class="mb-4">
                        <label class="fw-bold text-muted mb-2.5 d-block small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">Select Portion / Size</label>
                        <div class="row g-2.5">
                            <?php foreach ($sizes as $idx => $size): ?>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="size_id" id="size_<?= $size['id'] ?>" value="<?= $size['id'] ?>" data-price="<?= $size['price'] ?>" <?= $idx === 0 ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-orange w-100 py-3 rounded-3 text-start px-3 d-flex flex-column justify-content-center" for="size_<?= $size['id'] ?>">
                                        <span class="d-block fw-bold text-dark" style="font-size: 13px;"><?= sanitize($size['size_name']) ?></span>
                                        <span class="d-block small mt-0.5" style="color: var(--primary-orange); font-weight: 700;">+Rs. <?= number_format($size['price'], 0) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. Addons Selection -->
                <?php if (!empty($addons)): ?>
                    <div class="mb-4">
                        <label class="fw-bold text-muted mb-2 d-block small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">Extra Toppings</label>
                        <select class="form-select form-select-md rounded-3 border shadow-xs py-2.5 px-3" id="addonDropdown" style="font-size: 14px; border-color: #E2E8F0 !important;">
                            <option value="0" data-price="0.00" selected>-- Select Extra Topping --</option>
                            <?php foreach ($addons as $addon): ?>
                                <option value="<?= $addon['id'] ?>" data-price="<?= $addon['price'] ?>" data-name="<?= sanitize($addon['name']) ?>">
                                    <?= sanitize($addon['name']) ?> (+Rs. <?= number_format($addon['price'], 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Selected toppings container -->
                        <div id="selectedAddonsContainer" class="d-flex flex-wrap gap-2 mt-3"></div>
                    </div>
                <?php endif; ?>

                <!-- 3. Drinks Selection -->
                <?php if (!empty($drinks)): ?>
                    <div class="mb-4">
                        <label class="fw-bold text-muted mb-2 d-block small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">Choice of Drink</label>
                        <select class="form-select form-select-md rounded-3 border shadow-xs py-2.5 px-3" name="drink_id" style="font-size: 14px; border-color: #E2E8F0 !important;">
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
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <label class="fw-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">Quantity</label>
                    <div class="quantity-stepper shadow-xs" style="border: 1px solid #E2E8F0; border-radius: 30px; background-color: #F8FAFC; padding: 4px;">
                        <button type="button" class="btn-qty-minus" style="width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 1px solid #CBD5E1; font-weight: 700; color: #334155; line-height: 1;">-</button>
                        <input type="text" name="quantity" value="1" readonly style="width: 44px; text-align: center; border: none; background: transparent; font-weight: 800; font-size: 15px; color: #0F172A;">
                        <button type="button" class="btn-qty-plus" style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-orange); border: none; font-weight: 700; color: #fff; line-height: 1;">+</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Floating Price & Add to Cart Footer -->
    <div class="modal-price-footer rounded-bottom-4 px-4 py-3 bg-white border-top shadow-lg d-flex align-items-center justify-content-between">
        <div>
            <p class="modal-price-label mb-0 text-muted text-uppercase fw-bold" style="letter-spacing: 0.8px; font-size: 10px;">Total Price</p>
            <span class="modal-price-val fw-extrabold fs-3" id="modalTotalPrice" style="color: var(--primary-orange); font-family: 'Poppins', sans-serif;">Rs. 0.00</span>
        </div>
        <button type="submit" class="btn btn-primary-orange px-4 py-3 fw-bold rounded-3 text-white shadow d-inline-flex align-items-center gap-2" style="font-size: 15px; letter-spacing: -0.2px;">
            <span>Add to Order Basket</span> <i class="bi bi-arrow-right fs-6"></i>
        </button>
    </div>
</form>
