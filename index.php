<?php
// index.php
require_once __DIR__ . '/includes/functions.php';

// Fetch all active categories
$cat_stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY display_order ASC");
$categories = $cat_stmt->fetchAll();

// Fetch all areas for location selection modal
$area_stmt = $pdo->query("SELECT * FROM areas WHERE status = 'active' ORDER BY area_name ASC");
$areas = $area_stmt->fetchAll();

// Check if location is set in session
$has_location = isset($_SESSION['delivery_area_id']) ? 'true' : 'false';
$selected_area_name = '';
if (isset($_SESSION['delivery_area_id'])) {
    foreach ($areas as $area) {
        if ($area['id'] == $_SESSION['delivery_area_id']) {
            $selected_area_name = $area['area_name'];
            break;
        }
    }
}

// Search filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cravers - Faisalabad's Premium Food Delivery</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .product-card-link {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <!-- Premium SVG Logo -->
                <svg width="40" height="40" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                    <path d="M47 10C47 10 50 15 50 17C50 19 47 24 47 24" stroke="#FFC107" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span class="fs-4 fw-bold" style="color: var(--text-dark); letter-spacing: -0.5px;"><?= sanitize(get_setting('restaurant_name', 'Cravers')) ?><span style="color: var(--primary-orange);">.</span></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarText">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <?php if (is_user_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-orange fw-bold" href="profile.php" style="color: var(--primary-orange) !important;"><i class="bi bi-person-badge-fill me-1"></i> Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="bi bi-person-circle me-1"></i> Login/Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Delivery Location Display -->
                <div class="d-flex align-items-center me-3">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                    <span class="text-muted small me-1">Deliver to:</span>
                    <strong class="text-dark small me-2">
                        <?= !empty($selected_area_name) ? sanitize($selected_area_name) . ', FSD' : 'Select Location' ?>
                    </strong>
                    <a href="#" id="btnChangeLocation" class="link-orange small text-decoration-none" style="color: var(--primary-orange); font-weight:600;">Change</a>
                </div>

                <!-- Search Bar -->
                <form class="d-flex me-3" action="index.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search delicious food..." value="<?= sanitize($search_query) ?>" aria-label="Search" style="border-radius: 20px 0 0 20px; border-color: #eee;">
                        <button class="btn btn-outline-secondary btn-sm" type="submit" style="border-radius: 0 20px 20px 0; border-color: #eee; background-color: var(--primary-orange); color: white; border: none;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Cart Button with Badge -->
                <button class="cart-icon-btn d-flex align-items-center ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-controls="cartOffcanvas">
                    <i class="bi bi-bag-heart-fill"></i>
                    <span class="cart-badge" style="display: <?= get_cart_count() > 0 ? 'flex' : 'none' ?>;"><?= get_cart_count() ?></span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container my-3">
        <!-- CATEGORY NAVIGATION CONTAINER (PILLS) -->
        <?php if (empty($search_query)): ?>
            <div class="category-pill-container my-3">
                <a href="#" class="category-pill active" data-target="all">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="#category-<?= $cat['id'] ?>" class="category-pill">
                        <span><?= sanitize($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- HERO BANNER -->
        <div class="hero-banner">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <span class="badge bg-white text-dark mb-3 fw-bold px-3 py-2" style="color: var(--primary-orange) !important;">50% Off First Order</span>
                    <h1 class="display-5 fw-extrabold mb-3"><?= sanitize(get_setting('promo_text', 'Craving Something Delicious?')) ?></h1>
                    <p class="lead mb-4"><?= sanitize(get_setting('promo_subtitle', 'Get the best burgers, piping hot pizzas, and spicy pastas in Faisalabad delivered fresh to your doorstep within 30 minutes.')) ?></p>
                    <a href="#category-1" class="btn btn-light btn-lg px-4 py-2 fw-bold" style="color: var(--primary-orange); border-radius: 30px;">Order Now</a>
                </div>
                <div class="col-md-5 d-none d-md-block text-end">
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=500&q=80" alt="Delicious Food" class="img-fluid rounded-4 shadow-lg" style="max-height: 250px; object-fit: cover;">
                </div>
            </div>
        </div>

        <!-- PRODUCTS SECTIONS -->
        <?php
        if (!empty($search_query)) {
            // Display search results
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' AND (p.name LIKE ? OR p.description LIKE ?) ORDER BY p.id DESC");
            $stmt->execute(["%$search_query%", "%$search_query%"]);
            $products = $stmt->fetchAll();
            ?>
            <h2 class="mb-4">Search Results for "<?= sanitize($search_query) ?>"</h2>
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">No items matched your search criteria. Try looking for burgers or pizza!</p>
                    <a href="index.php" class="btn btn-primary-orange">View Menu</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $prod): ?>
                        <div class="col-md-3 col-sm-6">
                            <?php include __DIR__ . '/includes/product_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php } else {
            // Loop through categories and display products
            foreach ($categories as $cat) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'active'");
                $stmt->execute([$cat['id']]);
                $products = $stmt->fetchAll();

                if (empty($products)) {
                    continue; // Skip empty categories
                }
                ?>
                <div class="category-section mt-5 pt-3" id="category-<?= $cat['id'] ?>">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h2 class="category-title mb-0 position-relative">
                            <?= sanitize($cat['name']) ?>
                            <span style="position: absolute; bottom: -8px; left: 0; width: 40px; height: 4px; background-color: var(--primary-orange); border-radius: 2px;"></span>
                        </h2>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($products as $prod): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <?php include __DIR__ . '/includes/product_card.php'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <!-- PRODUCT DETAIL MODAL -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <!-- Populated dynamically via AJAX -->
            </div>
        </div>
    </div>

    <!-- OFFCANVAS CART SIDEBAR -->
    <div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="cartOffcanvasLabel"><i class="bi bi-cart3 text-orange me-2"></i>Your Order Basket</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column justify-content-between">
            
            <!-- Cart Items Container -->
            <div id="cartItemsContainer" style="overflow-y: auto; flex-grow: 1; max-height: 70vh;">
                <!-- Dynamically loaded via AJAX main.js -->
            </div>

            <!-- Cart Footer Calc and Checkout -->
            <div class="cart-sidebar-summary pt-3 mt-3 border-top bg-white">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <strong class="text-dark" id="cartSubtotal">Rs. 0.00</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Delivery Charges (<?= !empty($selected_area_name) ? sanitize($selected_area_name) : 'No Area' ?>)</span>
                    <strong class="text-dark" id="cartDeliveryFee">Rs. 0.00</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="h6 fw-bold">Grand Total</span>
                    <span class="h4 fw-bold text-orange text-success" id="cartGrandTotal" style="color: var(--primary-orange) !important;">Rs. 0.00</span>
                </div>
                <a href="checkout.php" class="btn btn-primary-orange w-100 py-3 fw-bold disabled" id="btnProceedCheckout">
                    Proceed to Checkout <i class="bi bi-arrow-right-short fs-5 ms-1"></i>
                </a>
            </div>

        </div>
    </div>

    <!-- MANDATORY LOCATION SELECTOR MODAL -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true" data-has-location="<?= $has_location ?>">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 location-modal shadow-lg">
                <div class="location-header">
                    <i class="bi bi-geo-alt-fill fs-1"></i>
                    <h3 class="mt-2 mb-1">Set Delivery Location</h3>
                    <p class="mb-0 small">Please select your area to see the menu & delivery fee.</p>
                </div>
                <div class="location-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">CITY</label>
                        <select class="form-select form-control-lg" disabled style="background-color: #f8f9fa;">
                            <option>Faisalabad</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">CHOOSE DELIVERY AREA</label>
                        <select class="form-select form-control-lg" id="areaSelect" required>
                            <option value="">-- Choose your area --</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= (isset($_SESSION['delivery_area_id']) && $_SESSION['delivery_area_id'] == $area['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($area['area_name']) ?> (Delivery Fee: Rs. <?= number_format($area['delivery_fee'], 0) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary-orange w-100 py-3 fw-bold" id="btnConfirmLocation">
                        Confirm Location & Start Ordering
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DevtaSoft Professional Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row g-4 text-start">
                <!-- Column 1: Brand Info -->
                <div class="col-lg-4 col-md-6">
                    <span class="fs-4 fw-bold text-white"><?= sanitize(get_setting('restaurant_name', 'Cravers')) ?>.</span>
                    <p class="small text-muted mt-2">
                        Cravers brings the taste of fresh, premium warm food right to your doorstep in Faisalabad. From masterfully crafted zingers to authentic brick-oven pizzas, satisfaction is just a click away.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-muted"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter fs-5"></i></a>
                    </div>
                </div>
                
                <!-- Column 2: Popular Categories -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h6 class="text-white fw-bold mb-3">Our Categories</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="index.php" class="text-muted small">Burgers</a></li>
                        <li class="mb-2"><a href="index.php" class="text-muted small">Hot Pizzas</a></li>
                        <li class="mb-2"><a href="index.php" class="text-muted small">Crispy Zingers</a></li>
                        <li class="mb-2"><a href="index.php" class="text-muted small">Hot Wings</a></li>
                        <li class="mb-2"><a href="index.php" class="text-muted small">Alfredo Pasta</a></li>
                        <li class="mb-2"><a href="index.php" class="text-muted small">Cold Drinks</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Corporate Policy Info -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h6 class="text-white fw-bold mb-3">Help & Policies</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="#" class="text-muted small">Return & Refund Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">Terms of Service</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">Delivery Locations Map</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">FAQs & Support</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact & Hotlines -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Contact Support</h6>
                    <ul class="list-unstyled mb-0 text-muted small">
                        <li class="mb-2"><i class="bi bi-geo-alt text-orange me-2"></i> Faisalabad, Punjab, Pakistan</li>
                        <li class="mb-2"><i class="bi bi-telephone text-orange me-2"></i> Hotline: <?= sanitize(get_setting('contact_number', '+92 300 123 4567')) ?></li>
                        <li class="mb-2"><i class="bi bi-envelope text-orange me-2"></i> support@cravers.com</li>
                        <li class="mt-3">
                            <span class="badge bg-success py-2 px-3 rounded-pill"><i class="bi bi-check-circle me-1"></i> Kitchen is Open</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="main-footer-bottom text-center small text-muted">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-2 mb-md-0">
                        © <?= date('Y') ?> <?= sanitize(get_setting('restaurant_name', 'Cravers')) ?>. All Rights Reserved.
                    </div>
                    <div class="col-md-6 text-md-end">
                        Developed with passion by <strong style="color: var(--primary-orange);">DevtaSoft</strong>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle with Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Wishlist Toggler Script -->
    <script>
        document.querySelectorAll('.wishlist-heart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.getAttribute('data-product-id');
                const btnEl = this;

                const params = new URLSearchParams();
                params.append('product_id', productId);

                fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: data.message
                        });
                        
                        if (data.is_added) {
                            btnEl.classList.add('active');
                            btnEl.innerHTML = '<i class="bi bi-heart-fill"></i>';
                        } else {
                            btnEl.classList.remove('active');
                            btnEl.innerHTML = '<i class="bi bi-heart"></i>';
                        }
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Wishlist Account Access',
                            text: data.message,
                            confirmButtonColor: '#FF6B00'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
