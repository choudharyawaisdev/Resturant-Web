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
    
    <!-- Working Prominent Close Button X -->
    <button type="button" class="btn-close position-absolute top-0 end-0 m-3 p-2 border rounded-circle bg-white shadow-sm" data-bs-dismiss="modal" aria-label="Close" onclick="closeProductModal()" style="z-index: 1050; opacity: 0.95; cursor: pointer;"></button>

    <div class="modal-body px-4 pt-4 pb-4">
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
                <h3 class="fw-bold mb-1 text-dark pe-4" style="letter-spacing: -0.5px;"><?= sanitize($product['name']) ?></h3>
                <p class="text-muted small mb-4" style="line-height: 1.5;"><?= sanitize($product['description']) ?></p>

                <!-- 1. Portion / Size Selection (Open Collapsible Radio Box) -->
                <?php if (!empty($sizes)): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="fw-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">
                                Select Portion / Size (<?= count($sizes) ?>)
                            </label>
                            <button class="btn btn-sm p-1 rounded-circle bg-light border-0 text-muted shadow-xs d-inline-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#sizeCollapseList" aria-expanded="true" aria-controls="sizeCollapseList" style="width: 28px; height: 28px; cursor: pointer;" title="Toggle Sizes">
                                <i class="bi bi-chevron-down toggle-chevron" id="sizeToggleIcon" style="font-size: 12px; transition: transform 0.2s ease;"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="sizeCollapseList">
                            <div class="size-radio-list rounded-3 border" style="border-color: #E2E8F0 !important; max-height: 200px; overflow-y: auto; scrollbar-width: thin;">
                                <?php foreach ($sizes as $idx => $size): ?>
                                    <div class="custom-row-item d-flex align-items-center justify-content-between px-3.5 py-3 <?= $idx > 0 ? 'border-top' : '' ?>" style="border-color: #F1F5F9 !important; cursor: pointer; padding-left: 16px !important; padding-right: 18px !important;" onclick="selectSizeRow('size_<?= $size['id'] ?>')">
                                        <span class="item-name text-dark" style="font-size: 14px; font-weight: 500;"><?= sanitize($size['size_name']) ?></span>
                                        <div class="d-flex align-items-center gap-3 ms-3">
                                            <span class="item-price text-muted" style="font-size: 13px;">+ Rs. <?= number_format($size['price'], 0) ?></span>
                                            <div class="form-check m-0 p-0">
                                                <input class="form-check-input size-radio m-0" type="radio" name="size_id" id="size_<?= $size['id'] ?>" value="<?= $size['id'] ?>" data-price="<?= $size['price'] ?>" <?= $idx === 0 ? 'checked' : '' ?> onclick="event.stopPropagation()">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. Extra Toppings Selection (Open Collapsible Checkbox Box) -->
                <?php if (!empty($addons)): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="fw-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">
                                Extra Toppings (<?= count($addons) ?>)
                            </label>
                            <button class="btn btn-sm p-1 rounded-circle bg-light border-0 text-muted shadow-xs d-inline-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#addonsCollapseList" aria-expanded="true" aria-controls="addonsCollapseList" style="width: 28px; height: 28px; cursor: pointer;" title="Toggle Toppings">
                                <i class="bi bi-chevron-down toggle-chevron" id="addonToggleIcon" style="font-size: 12px; transition: transform 0.2s ease;"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="addonsCollapseList">
                            <div class="addon-checkbox-list rounded-3 border" style="border-color: #E2E8F0 !important; max-height: 210px; overflow-y: auto; scrollbar-width: thin;">
                                <?php foreach ($addons as $idx => $addon): ?>
                                    <div class="addon-checkbox-row d-flex align-items-center justify-content-between px-3.5 py-3 <?= $idx > 0 ? 'border-top' : '' ?>" style="border-color: #F1F5F9 !important; cursor: pointer; padding-left: 16px !important; padding-right: 18px !important;" onclick="toggleAddonRow(event, 'addon_<?= $addon['id'] ?>')">
                                        <span class="addon-name text-dark" style="font-size: 14px; font-weight: 500;"><?= sanitize($addon['name']) ?></span>
                                        <div class="d-flex align-items-center gap-3 ms-3">
                                            <span class="addon-price text-muted" style="font-size: 13px;">+ Rs. <?= number_format($addon['price'], 2) ?></span>
                                            <label class="custom-addon-checkbox ms-1 me-1 mb-0 d-block" for="addon_<?= $addon['id'] ?>" style="cursor: pointer;">
                                                <input type="checkbox" class="addon-check" name="addons[]" id="addon_<?= $addon['id'] ?>" value="<?= $addon['id'] ?>" data-price="<?= $addon['price'] ?>" data-name="<?= sanitize($addon['name']) ?>" onclick="event.stopPropagation()">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 3. Choice of Drink Selection (Open Collapsible Radio Box) -->
                <?php if (!empty($drinks)): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="fw-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">
                                Choice of Drink (<?= count($drinks) ?>)
                            </label>
                            <button class="btn btn-sm p-1 rounded-circle bg-light border-0 text-muted shadow-xs d-inline-flex align-items-center justify-content-center" type="button" data-bs-toggle="collapse" data-bs-target="#drinksCollapseList" aria-expanded="true" aria-controls="drinksCollapseList" style="width: 28px; height: 28px; cursor: pointer;" title="Toggle Drinks">
                                <i class="bi bi-chevron-down toggle-chevron" id="drinkToggleIcon" style="font-size: 12px; transition: transform 0.2s ease;"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="drinksCollapseList">
                            <div class="drink-radio-list rounded-3 border" style="border-color: #E2E8F0 !important; max-height: 200px; overflow-y: auto; scrollbar-width: thin;">
                                <!-- No Drink Option -->
                                <div class="custom-row-item d-flex align-items-center justify-content-between px-3.5 py-3" style="border-color: #F1F5F9 !important; cursor: pointer; padding-left: 16px !important; padding-right: 18px !important;" onclick="selectDrinkRow('drink_0')">
                                    <span class="item-name text-dark" style="font-size: 14px; font-weight: 500;">-- No Drink --</span>
                                    <div class="d-flex align-items-center gap-3 ms-3">
                                        <span class="item-price text-muted" style="font-size: 13px;">+ Rs. 0.00</span>
                                        <div class="form-check m-0 p-0">
                                            <input class="form-check-input drink-radio m-0" type="radio" name="drink_id" id="drink_0" value="0" data-price="0.00" checked onclick="event.stopPropagation()">
                                        </div>
                                    </div>
                                </div>
                                <?php foreach ($drinks as $idx => $drink): ?>
                                    <div class="custom-row-item d-flex align-items-center justify-content-between px-3.5 py-3 border-top" style="border-color: #F1F5F9 !important; cursor: pointer; padding-left: 16px !important; padding-right: 18px !important;" onclick="selectDrinkRow('drink_<?= $drink['id'] ?>')">
                                        <span class="item-name text-dark" style="font-size: 14px; font-weight: 500;"><?= sanitize($drink['name']) ?></span>
                                        <div class="d-flex align-items-center gap-3 ms-3">
                                            <span class="item-price text-muted" style="font-size: 13px;">+ Rs. <?= number_format($drink['price'], 0) ?></span>
                                            <div class="form-check m-0 p-0">
                                                <input class="form-check-input drink-radio m-0" type="radio" name="drink_id" id="drink_<?= $drink['id'] ?>" value="<?= $drink['id'] ?>" data-price="<?= $drink['price'] ?>" onclick="event.stopPropagation()">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 4. Quantity Stepper (Horizontal Inline Row) -->
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <label class="fw-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.8px; font-size: 11px;">Quantity</label>
                    <div class="quantity-stepper d-inline-flex align-items-center shadow-xs">
                        <button type="button" class="btn-qty-minus">-</button>
                        <input type="text" name="quantity" value="1" readonly>
                        <button type="button" class="btn-qty-plus">+</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Floating Price & Add to Cart Footer (Medium-Small Button) -->
    <div class="modal-price-footer rounded-bottom-4 px-4 py-3 bg-white border-top shadow-lg d-flex align-items-center justify-content-between gap-3">
        <div>
            <p class="modal-price-label mb-0 text-muted text-uppercase fw-bold" style="letter-spacing: 0.8px; font-size: 10px;">Total Price</p>
            <span class="modal-price-val fw-bold fs-4" id="modalTotalPrice" style="color: var(--primary-orange); font-family: 'Poppins', sans-serif;">Rs. 0.00</span>
        </div>
        <button type="submit" class="btn btn-primary-orange px-3.5 py-2.5 fw-semibold rounded-pill text-white shadow-sm d-inline-flex align-items-center gap-2" style="font-size: 13.5px; letter-spacing: 0.1px; white-space: nowrap;">
            <span>Add to Order Basket</span> <i class="bi bi-arrow-right fs-6"></i>
        </button>
    </div>
</form>

<script>
function closeProductModal() {
    const modalEl = document.getElementById('productDetailModal');
    if (modalEl) {
        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        bsModal.hide();
    }
}

function selectSizeRow(radioId) {
    const radio = document.getElementById(radioId);
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function selectDrinkRow(radioId) {
    const radio = document.getElementById(radioId);
    if (radio) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function toggleAddonRow(e, inputId) {
    if (e.target.closest('.custom-addon-checkbox') || e.target.tagName === 'INPUT') {
        return;
    }
    const input = document.getElementById(inputId);
    if (input) {
        input.checked = !input.checked;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

// Chevron rotate animations on collapse
['sizeCollapseList', 'addonsCollapseList', 'drinksCollapseList'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        const icon = el.parentElement.querySelector('.toggle-chevron');
        if (icon) {
            el.addEventListener('hidden.bs.collapse', function () {
                icon.style.transform = 'rotate(180deg)';
            });
            el.addEventListener('shown.bs.collapse', function () {
                icon.style.transform = 'rotate(0deg)';
            });
        }
    }
});
</script>
