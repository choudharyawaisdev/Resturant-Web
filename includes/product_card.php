<?php
// includes/product_card.php
// Expected variable: $prod (array representing product row)

// Map category ids to default Unsplash images for high aesthetic value
$default_images = [
    1 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80', // Burger
    2 => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80', // Pizza
    3 => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80', // Zinger (fried chicken)
    4 => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=400&q=80', // Wings
    5 => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=400&q=80', // Pasta
    6 => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=400&q=80'  // Drinks
];

$img_url = $default_images[$prod['category_id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80';

// If product has a custom upload, check and use it
if (!empty($prod['image']) && file_exists(dirname(__DIR__) . '/assets/images/uploads/' . $prod['image'])) {
    $img_url = 'assets/images/uploads/' . $prod['image'];
}
?>
<?php
$is_fav = false;
if (is_user_logged_in()) {
    $is_fav = is_in_wishlist($pdo, get_logged_in_user_id(), $prod['id']);
}
?>
<div class="card product-card position-relative border-0 shadow-sm rounded-4 h-100 overflow-hidden">
    <button class="wishlist-heart-btn <?= $is_fav ? 'active' : '' ?>" data-product-id="<?= $prod['id'] ?>" title="Add to Wishlist">
        <i class="bi <?= $is_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
    </button>
    <a href="#" class="product-card-link btn-customize text-decoration-none" data-product-id="<?= $prod['id'] ?>">
        <div class="product-img-wrapper position-relative overflow-hidden bg-light" style="height: 200px;">
            <img src="<?= $img_url ?>" class="card-img-top product-img w-100 h-100" style="object-fit: cover; transition: transform 0.4s ease;" alt="<?= sanitize($prod['name']) ?>">
            <span class="product-badge position-absolute top-0 start-0 m-3 px-3 py-1 bg-warning text-dark fw-bold rounded-pill" style="font-size: 11px; letter-spacing: 0.5px;">Gourmet</span>
        </div>
    </a>
    <div class="product-body p-3.5 d-flex flex-column flex-grow-1">
        <a href="#" class="product-card-link btn-customize text-decoration-none text-dark" data-product-id="<?= $prod['id'] ?>">
            <h5 class="product-title fw-bold mb-1 text-truncate"><?= sanitize($prod['name']) ?></h5>
        </a>
        <p class="product-desc text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px;">
            <?= sanitize($prod['description']) ?>
        </p>
        <div class="product-footer d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
            <div class="d-flex flex-column">
                <span class="text-muted small" style="font-size: 11px;">Starting at</span>
                <span class="product-price fw-bold fs-5" style="color: var(--primary-orange);">Rs. <?= number_format($prod['base_price'], 0) ?></span>
            </div>
            <button class="btn btn-primary-orange btn-customize px-3 py-2 text-white fw-bold d-inline-flex align-items-center gap-1" data-product-id="<?= $prod['id'] ?>" style="font-size: 12px; border-radius: 20px;">
                <span>Customize</span> <i class="bi bi-sliders fs-6"></i>
            </button>
        </div>
    </div>
</div>
