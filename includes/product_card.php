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
<div class="card product-card position-relative">
    <button class="wishlist-heart-btn <?= $is_fav ? 'active' : '' ?>" data-product-id="<?= $prod['id'] ?>">
        <i class="bi <?= $is_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
    </button>
    <a href="#" class="product-card-link btn-customize" data-product-id="<?= $prod['id'] ?>">
        <div class="product-img-wrapper">
            <img src="<?= $img_url ?>" class="card-img-top product-img" alt="<?= sanitize($prod['name']) ?>">
            <span class="product-badge">Premium</span>
        </div>
    </a>
    <div class="product-body">
        <h5 class="product-title"><?= sanitize($prod['name']) ?></h5>
        <p class="product-desc"><?= sanitize($prod['description']) ?></p>
        <div class="product-footer">
            <span class="product-price">Rs. <?= number_format($prod['base_price'], 0) ?></span>
            <button class="btn btn-primary-orange btn-customize" data-product-id="<?= $prod['id'] ?>">
                Customize <i class="bi bi-sliders ms-1"></i>
            </button>
        </div>
    </div>
</div>
