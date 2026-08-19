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
    <title>Cafe Chinos - Chiniot's Premium Food Delivery</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        .product-card-link { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="has-fixed-navbar">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom navbar-fixed">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="Café-Chinos" class="navbar-logo" style="height:50px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            
            <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
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
                            <a class="nav-link fw-semibold" href="login.php"><i class="bi bi-person-circle me-1"></i> Login / Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Delivery Location Display -->
                <div class="mobile-location-box d-flex align-items-center me-lg-3 my-2 my-lg-0 p-2 p-lg-0 rounded-3">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                    <span class="text-muted small me-1">Deliver to:</span>
                    <strong class="text-dark small me-2 text-truncate" style="max-width: 180px;">
                        <?= !empty($selected_area_name) ? sanitize($selected_area_name) . ', Chiniot' : 'Select Location' ?>
                    </strong>
                    <a href="#" id="btnChangeLocation" class="link-orange small text-decoration-none ms-auto ms-lg-0" style="color: var(--primary-orange); font-weight:600;">Change</a>
                </div>

                <!-- Search Bar -->
                <form class="d-flex me-lg-3 my-2 my-lg-0 w-100-mobile" action="index" method="GET" style="min-width: 220px;">
                    <div class="input-group rounded-pill border bg-white shadow-xs overflow-hidden w-100" style="border-color: #CBD5E1 !important;">
                        <input type="text" name="search" class="form-control form-control-sm border-0 bg-transparent px-3 py-2" placeholder="Search delicious food..." value="<?= sanitize($search_query) ?>" aria-label="Search" style="font-size: 13px; outline: none; box-shadow: none;">
                        <button class="btn px-3 py-2 d-flex align-items-center justify-content-center" type="submit" style="background-color: var(--primary-orange); color: white; border: none; font-size: 14px;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Cart Button with Badge -->
                <button class="cart-icon-btn d-flex align-items-center ms-lg-2 my-2 my-lg-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-controls="cartOffcanvas">
                    <i class="bi bi-bag-heart-fill"></i>
                    <span class="cart-badge" style="display: <?= get_cart_count() > 0 ? 'flex' : 'none' ?>;"><?= get_cart_count() ?></span>
                    <span class="ms-2 d-lg-none fw-bold text-orange" style="font-size: 14px; color: var(--primary-orange);">View Shopping Cart</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container my-3">
        <!-- CATEGORY NAVIGATION CONTAINER (PILLS) -->
        <?php if (empty($search_query)): ?>
            <div class="category-pill-wrapper" id="categoryPillWrapper">
                <div class="category-pill-container">
                    <a href="#all" class="category-pill active" data-category-id="all">All</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="#category-<?= $cat['id'] ?>" class="category-pill" data-category-id="<?= $cat['id'] ?>">
                            <span><?= sanitize($cat['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- HERO CAROUSEL REDESIGN -->
        <div id="heroCarousel" class="carousel slide carousel-fade hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <!-- Slide 1: Cheese Burger (Tied to Admin settings) -->
                <div class="carousel-item active">
                    <div class="carousel-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1920&q=80" class="d-block w-100 carousel-img" alt="Premium Cheese Burger" loading="lazy">
                        <div class="carousel-overlay"></div>
                    </div>
                    <div class="carousel-caption">
                        <span class="badge bg-white text-dark mb-3 fw-bold px-3 py-2 text-uppercase" style="color: var(--primary-orange) !important;">50% Off First Order</span>
                        <h1><?= sanitize(get_setting('promo_text', 'Premium Cheese Burger')) ?></h1>
                        <p><?= sanitize(get_setting('promo_subtitle', 'Savor our masterfully crafted beef patty, layered with fresh lettuce, melted cheddar, and our signature gourmet sauce.')) ?></p>
                        <div class="d-flex gap-3">
                            <a href="#category-1" class="btn btn-primary-orange">Order Now</a>
                            <a href="index.php" class="btn btn-outline-light">View Menu</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 2: Italian Pizza -->
                <div class="carousel-item">
                    <div class="carousel-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1920&q=80" class="d-block w-100 carousel-img" alt="Italian Pizza" loading="lazy">
                        <div class="carousel-overlay"></div>
                    </div>
                    <div class="carousel-caption">
                        <span class="badge bg-white text-dark mb-3 fw-bold px-3 py-2 text-uppercase" style="color: var(--primary-orange) !important;">Chef's Special</span>
                        <h1>Authentic Italian Pizza</h1>
                        <p>Fresh hand-stretched dough topped with rich tomato sauce, premium mozzarella cheese, and fresh garden toppings baked to perfection.</p>
                        <div class="d-flex gap-3">
                            <a href="#category-2" class="btn btn-primary-orange">Order Now</a>
                            <a href="index.php" class="btn btn-outline-light">View Menu</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 3: Crispy Fried Chicken + Drinks -->
                <div class="carousel-item">
                    <div class="carousel-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=1920&q=80" class="d-block w-100 carousel-img" alt="Crispy Fried Chicken" loading="lazy">
                        <div class="carousel-overlay"></div>
                    </div>
                    <div class="carousel-caption">
                        <span class="badge bg-white text-dark mb-3 fw-bold px-3 py-2 text-uppercase" style="color: var(--primary-orange) !important;">Super Saver Combo</span>
                        <h1>Crispy Fried Chicken</h1>
                        <p>Get our golden crispy, spicy fried chicken served with seasoned fries and an ice-cold refreshing drink at an unbeatable value.</p>
                        <div class="d-flex gap-3">
                            <a href="#category-3" class="btn btn-primary-orange">Order Now</a>
                            <a href="index.php" class="btn btn-outline-light">View Menu</a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" style="z-index: 5;">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" style="z-index: 5;">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
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
                <div class="row g-2 g-md-3 g-lg-4">
                    <?php foreach ($products as $prod): ?>
                        <div class="col-6 col-md-4 col-lg-3">
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
                    <div class="row g-2 g-md-3 g-lg-4">
                        <?php foreach ($products as $prod): ?>
                            <div class="col-6 col-md-4 col-lg-3">
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
                            <option>Chiniot</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">CHOOSE DELIVERY AREA</label>
                        <select class="form-select form-control-lg" id="areaSelect" required>
                            <option value="">-- Choose your area --</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= (isset($_SESSION['delivery_area_id']) && $_SESSION['delivery_area_id'] == $area['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($area['area_name']) ?> (Delivery Fee: Rs. <?= number_format($area['delivery_charge'] ?? 0, 0) ?>)
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
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="Café-Chinos" style="height:70px; width:auto; object-fit:contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
                    </a>
                    <p class="small text-muted mt-2">
                        Café-Chinos brings the taste of fresh, premium warm food right to your doorstep in Chiniot. From masterfully crafted zingers to authentic brick-oven pizzas, satisfaction is just a click away.
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
                        <?php
                        try {
                            $footer_cats = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id ASC")->fetchAll();
                            foreach ($footer_cats as $fc) {
                                echo '<li class="mb-2.5"><a href="index#category-' . intval($fc['id']) . '" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px;"></i> ' . sanitize($fc['name']) . '</a></li>';
                            }
                        } catch (Exception $e) {
                            echo '<li class="mb-2.5"><a href="index" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px;"></i> Menu</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <!-- Column 3: Corporate Policy Info -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h6 class="text-white fw-bold mb-3">Help & Policies</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="policies.php?type=refund" class="text-muted small">Return & Refund Policy</a></li>
                        <li class="mb-2"><a href="policies.php?type=terms" class="text-muted small">Terms of Service</a></li>
                        <li class="mb-2"><a href="policies.php?type=privacy" class="text-muted small">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">Delivery Locations Map</a></li>
                        <li class="mb-2"><a href="#" class="text-muted small">FAQs & Support</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact & Hotlines -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Contact Support</h6>
                    <ul class="list-unstyled mb-0 text-muted small">
                        <li class="mb-2"><i class="bi bi-geo-alt text-orange me-2"></i> <?= sanitize(get_setting('address', '359-V Nao Gazah Rd, Chiniot, 35400')) ?></li>
                        <li class="mb-2"><i class="bi bi-telephone text-orange me-2"></i> Hotline: <?= sanitize(get_setting('contact_number', '0311 7593578')) ?></li>
                        <li class="mb-2"><i class="bi bi-clock text-orange me-2"></i> <?= sanitize(get_setting('hours', 'Open 24 hours')) ?> (<?= sanitize(get_setting('service_options', 'Cash only')) ?>)</li>
                        <li class="mt-3">
                            <span class="badge bg-success py-2 px-3 rounded-pill"><i class="bi bi-check-circle me-1"></i> Kitchen is Open 24/7</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="main-footer-bottom text-center small text-muted">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-2 mb-md-0">
                        © <?= date('Y') ?> <?= sanitize(get_setting('restaurant_name', 'Café-Chinos')) ?>. All Rights Reserved.
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

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Welcome! 🎉',
                text: '<?= addslashes(strip_tags($_SESSION['flash_success'])) ?>',
                confirmButtonColor: '#FF6B00',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#fff',
                customClass: { popup: 'rounded-4' }
            });
        });
    </script>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <!-- Category Smooth Scroll & Active Pill Script -->
    <script>
        document.querySelectorAll('.category-pill').forEach(pill => {
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');

                const targetId = this.getAttribute('href');
                if (targetId && targetId !== '#all') {
                    const targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        const yOffset = -90; 
                        const y = targetEl.getBoundingClientRect().top + window.pageYOffset + yOffset;
                        window.scrollTo({ top: y, behavior: 'smooth' });
                    }
                } else if (targetId === '#all') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });

        // Wishlist Toggler Script
        document.querySelectorAll('.wishlist-heart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.getAttribute('data-product-id');
                const btnEl = this;

                const params = new URLSearchParams();
                params.append('product_id', productId);

                fetch('api/wishlist', {
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

    <script>
        // Navbar scroll shadow effect
        window.addEventListener('scroll', function () {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
