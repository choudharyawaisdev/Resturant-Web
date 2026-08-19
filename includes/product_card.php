<?php
// includes/product_card.php
// Expected variable: $prod (array representing product row)

$default_images = [
    1 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80',
    2 => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80',
    3 => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80',
    4 => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=400&q=80',
    5 => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=400&q=80',
    6 => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=400&q=80'
];

$img_url = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80';

if (!empty($prod['image'])) {
    if (strpos($prod['image'], 'http') === 0) {
        $img_url = $prod['image'];
    } elseif (file_exists(dirname(__DIR__) . '/assets/images/uploads/' . $prod['image'])) {
        $img_url = 'assets/images/uploads/' . $prod['image'];
    }
}

$badges = [
    1 => 'Hot Deal',
    2 => 'Pizza',
    3 => 'Burger',
    4 => 'Wings',
    5 => 'Pasta',
    6 => 'Wrap',
    7 => 'Roll',
    8 => 'Fries',
    9 => 'Drink'
];
$badge_text = $prod['category_name'] ?? ($badges[$prod['category_id']] ?? 'Special');

$is_fav = false;
if (is_user_logged_in()) {
    $is_fav = is_in_wishlist($pdo, get_logged_in_user_id(), $prod['id']);
}
?>
<div class="product-card position-relative h-100">

    <!-- Wishlist Heart -->
    <button class="wishlist-heart-btn <?= $is_fav ? 'active' : '' ?>" data-product-id="<?= $prod['id'] ?>" title="Wishlist">
        <i class="bi <?= $is_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
    </button>

    <!-- Product Image -->
    <a href="#" class="product-card-link btn-customize d-block text-decoration-none" data-product-id="<?= $prod['id'] ?>">
        <div class="product-img-wrapper">
            <img
                src="<?= $img_url ?>"
                class="product-img"
                alt="<?= sanitize($prod['name']) ?>"
                loading="lazy"
                onerror="this.src='https://placehold.co/400x260/FFF8F0/FF6B00?text=Food'"
            >
            <!-- Category Badge -->
            <span class="product-badge">
                <?= sanitize($badge_text) ?>
            </span>
        </div>
    </a>

    <!-- Card Body -->
    <div class="product-body">

        <!-- Title -->
        <a href="#" class="product-card-link btn-customize text-decoration-none text-dark" data-product-id="<?= $prod['id'] ?>">
            <h6 class="product-title mb-1"><?= sanitize($prod['name']) ?></h6>
        </a>

        <!-- Description -->
        <p class="product-desc"><?= sanitize($prod['description']) ?></p>

        <!-- Footer: Price + Button -->
        <div class="product-footer">
            <!-- Price -->
            <?php
            $display_price = floatval($prod['base_price'] ?? 0);
            if ($display_price <= 0 && isset($pdo)) {
                $sz_stmt = $pdo->prepare("SELECT MIN(price) FROM product_sizes WHERE product_id = ?");
                $sz_stmt->execute([$prod['id']]);
                $min_sz = $sz_stmt->fetchColumn();
                if ($min_sz && $min_sz > 0) {
                    $display_price = floatval($min_sz);
                }
            }
            ?>
            <div class="product-price-block">
                <span class="price-label">From</span>
                <span class="product-price">Rs. <?= number_format($display_price, 0) ?></span>
            </div>

            <!-- Add / Order Button -->
            <button
                class="btn-order-now btn-customize"
                data-product-id="<?= $prod['id'] ?>"
                title="Order Now"
            >
                <i class="bi bi-plus-lg"></i>
                <span class="btn-order-text">Order</span>
            </button>
        </div>
    </div>
</div>
