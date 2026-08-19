<?php
// checkout.php
require_once __DIR__ . '/includes/functions.php';

// If no delivery area is selected, redirect to menu
if (!isset($_SESSION['delivery_area_id'])) {
    redirect('index');
}

// Hydrate cart details
$cart_details = get_cart_details($pdo);

// If cart is empty, redirect to menu
if (empty($cart_details['items'])) {
    redirect('index');
}

// Fetch areas to get selected area name
$stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
$stmt->execute([$_SESSION['delivery_area_id']]);
$selected_area = $stmt->fetch();

// Fetch user profile if logged in
$user_info = null;
if (is_user_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([get_logged_in_user_id()]);
    $user_info = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Café-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index">
                <img src="assets/images/logo.png" alt="Café-Chinos" class="navbar-logo" style="height:56px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            <span class="text-muted"><i class="bi bi-shield-lock-fill me-1 text-success"></i> Secure Checkout</span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-4">
            
            <!-- Left: Checkout Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h4 class="fw-bold mb-4"><i class="bi bi-truck text-orange me-2" style="color: var(--primary-orange);"></i> Delivery Information</h4>
                    
                    <form id="checkoutForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label small fw-bold text-muted">FULL NAME</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control form-control-lg border-radius-md" placeholder="e.g. Hassan Ahmed" value="<?= $user_info ? sanitize($user_info['name']) : '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label small fw-bold text-muted">CONTACT NUMBER</label>
                                <input type="tel" name="phone" id="phone" class="form-control form-control-lg border-radius-md" placeholder="e.g. 03001234567" value="<?= $user_info ? sanitize($user_info['phone'] ?? '') : '' ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">CITY</label>
                                <input type="text" class="form-control form-control-lg border-radius-md bg-light" value="Chiniot" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">DELIVERY AREA</label>
                                <input type="text" class="form-control form-control-lg border-radius-md bg-light" value="<?= sanitize($selected_area['area_name']) ?>" readonly>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label small fw-bold text-muted">DETAILED STREET ADDRESS (HOUSE#, STREET#)</label>
                                <textarea name="address" id="address" rows="3" class="form-control border-radius-md" placeholder="Provide details like house/flat number, block, street, landmark..." required><?= $user_info ? sanitize($user_info['address'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="col-12">
                                <label for="order_notes" class="form-label small fw-bold text-muted">SPECIAL NOTES / INSTRUCTIONS (OPTIONAL)</label>
                                <textarea name="order_notes" id="order_notes" rows="2" class="form-control border-radius-md" placeholder="e.g. Please bring change for Rs. 5000, don't ring the bell, etc."></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h4 class="fw-bold mb-3"><i class="bi bi-credit-card text-orange me-2" style="color: var(--primary-orange);"></i> Payment Method</h4>
                        <div class="p-3 border rounded-3 d-flex align-items-center bg-light">
                            <input class="form-check-input me-3" type="radio" name="payment_method" id="payment_cod" checked>
                            <label class="form-check-label fw-bold text-dark d-flex align-items-center" for="payment_cod">
                                <i class="bi bi-wallet2 fs-5 me-2 text-success"></i> Cash on Delivery (Cash only)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary-orange w-100 py-3 mt-4 fw-bold fs-5">
                            Confirm Order & Pay on Delivery <i class="bi bi-check-circle-fill ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white sticky-lg-top" style="top: 100px; z-index: 10;">
                    <h4 class="fw-bold mb-4"><i class="bi bi-receipt text-orange me-2" style="color: var(--primary-orange);"></i> Order Summary</h4>

                    <div class="checkout-items-list mb-4" style="max-height: 350px; overflow-y: auto;">
                        <?php foreach ($cart_details['items'] as $item): ?>
                            <div class="d-flex align-items-start py-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1 small text-dark"><?= sanitize($item['product_name']) ?> <span class="text-orange">x<?= $item['quantity'] ?></span></h6>
                                    <p class="text-muted mb-0" style="font-size: 11px;">
                                        Size: <?= sanitize($item['size_name']) ?>
                                        <?php if (!empty($item['drink_name'])): ?>
                                            | Drink: <?= sanitize($item['drink_name']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['addons'])): ?>
                                            <br>Addons: <?= implode(', ', array_map(function($a) { return sanitize($a['name']); }, $item['addons'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="fw-bold small text-dark ms-2">Rs. <?= number_format($item['line_total'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="checkout-totals pt-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Subtotal</span>
                            <span class="fw-bold small text-dark">Rs. <?= number_format($cart_details['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Delivery Charges (<?= sanitize($selected_area['area_name']) ?>)</span>
                            <span class="fw-bold small text-dark">Rs. <?= number_format($cart_details['delivery_fee'], 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Total Amount</h5>
                            <h4 class="fw-bold text-success mb-0" style="color: var(--primary-orange) !important;">Rs. <?= number_format($cart_details['grand_total'], 2) ?></h4>
                        </div>
                    </div>
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
                    <a href="index">
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
                <!-- Column 2: Popular Categories -->
                <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                    <h6 class="text-white fw-bold mb-3">Our Categories</h6>
                    <ul class="list-unstyled mb-0">
                        <?php
                        try {
                            $footer_cats = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id ASC")->fetchAll();
                            foreach ($footer_cats as $fc) {
                                echo '<li class="mb-2.5"><a href="index#category-' . intval($fc['id']) . '" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>' . sanitize($fc['name']) . '</span></a></li>';
                            }
                        } catch (Exception $e) {
                            echo '<li class="mb-2.5"><a href="index" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>Menu</span></a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <!-- Column 3: Corporate Policy Info -->
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <h6 class="text-white fw-bold mb-3">Help & Policies</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2.5"><a href="policies.php?type=refund" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>Return & Refund Policy</span></a></li>
                        <li class="mb-2.5"><a href="policies.php?type=terms" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>Terms of Service</span></a></li>
                        <li class="mb-2.5"><a href="policies.php?type=privacy" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>Privacy Policy</span></a></li>
                        <li class="mb-2.5"><a href="policies.php?type=delivery" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>Delivery Locations Map</span></a></li>
                        <li class="mb-2.5"><a href="policies.php?type=faq" class="footer-link small"><i class="bi bi-chevron-right me-2" style="color: var(--primary-orange); font-size: 11px; flex-shrink: 0;"></i> <span>FAQs & Support</span></a></li>
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

    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form fields
            const customerName = document.getElementById('customer_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const orderNotes = document.getElementById('order_notes').value.trim();

            if (!customerName || !phone || !address) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    text: 'Please fill in all the required delivery details.',
                    confirmButtonColor: '#FF6B00'
                });
                return;
            }

            // Show loading alert
            Swal.fire({
                title: 'Processing Order...',
                text: 'Please wait while we record your delicious order.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Build request params
            const params = new URLSearchParams();
            params.append('customer_name', customerName);
            params.append('phone', phone);
            params.append('address', address);
            params.append('order_notes', orderNotes);

            // AJAX request to place_order
            fetch('api/place_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '🎉 Order Placed Successfully!',
                        text: 'Your order #' + data.order_id + ' has been placed.',
                        confirmButtonColor: '#FF6B00'
                    }).then(() => {
                        window.location.href = 'order-success?id=' + data.order_id;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout Failed',
                        text: data.message || 'There was a problem placing your order.',
                        confirmButtonColor: '#FF6B00'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'An unexpected connection error occurred.',
                    confirmButtonColor: '#FF6B00'
                });
            });
        });
    </script>
</body>
</html>

