<?php
// checkout.php
require_once __DIR__ . '/includes/functions.php';

// If no delivery area is selected, redirect to menu
if (!isset($_SESSION['delivery_area_id'])) {
    redirect('index.php');
}

// Hydrate cart details
$cart_details = get_cart_details($pdo);

// If cart is empty, redirect to menu
if (empty($cart_details['items'])) {
    redirect('index.php');
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
    <title>Checkout - Cravers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg width="35" height="35" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                </svg>
                <span class="fs-4 fw-bold text-dark">Cravers<span style="color: var(--primary-orange);">.</span></span>
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
                                <input type="text" class="form-control form-control-lg border-radius-md bg-light" value="Faisalabad" readonly>
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
                                <i class="bi bi-wallet2 fs-5 me-2 text-success"></i> Cash on Delivery (COD)
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

    <!-- DevtaSoft Footer -->
    <footer class="main-footer">
        <div class="container text-center">
            <div class="row align-items-center">
                <div class="col-md-4 text-md-start mb-3 mb-md-0">
                    <span class="fs-5 fw-bold text-white"><?= sanitize(get_setting('restaurant_name', 'Cravers')) ?>.</span>
                    <p class="small text-muted mt-1 mb-0">Delivering fresh warm food daily in Faisalabad.</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <a href="index.php" class="mx-2 small text-muted">Home</a> | 
                    <a href="profile.php" class="mx-2 small text-muted">My Dashboard</a> | 
                    <a href="login.php" class="mx-2 small text-muted">Accounts</a>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="small text-muted">Support hotline: <strong><?= sanitize(get_setting('contact_number', '+92 300 123 4567')) ?></strong></span>
                </div>
            </div>
            <div class="main-footer-bottom text-center small text-muted">
                © <?= date('Y') ?> Cravers Restaurant. All Rights Reserved. <br>
                <span class="mt-1 d-inline-block">Developed by <strong style="color: var(--primary-orange);">DevtaSoft</strong></span>
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
            fetch('api/place_order.php', {
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
                        window.location.href = 'order-success.php?id=' + data.order_id;
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
