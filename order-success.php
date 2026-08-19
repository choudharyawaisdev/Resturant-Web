<?php
// order-success.php
require_once __DIR__ . '/includes/functions.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    redirect('index');
}

// Fetch Order Details
$stmt = $pdo->prepare("
    SELECT o.*, a.area_name 
    FROM orders o 
    JOIN areas a ON o.area_id = a.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container my-5 text-center"><h3>Order not found.</h3><a href="index">Return to Home</a></div>';
    exit();
}

// Fetch Order Items
$item_stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$item_stmt->execute([$order_id]);
$order_items = $item_stmt->fetchAll();

// Handle AJAX Status Polling
if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $order['status']]);
    exit();
}

// Map status to progress step index (0 to 3)
$status_steps = [
    'Pending'          => 0,
    'Preparing'        => 1,
    'Out for Delivery' => 2,
    'Delivered'        => 3,
    'Cancelled'        => -1
];

$current_step = $status_steps[$order['status']] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> - Café-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        /* Status Tracker Styling */
        .status-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }
        .status-tracker::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 5%;
            right: 5%;
            height: 4px;
            background-color: #e9ecef;
            z-index: 1;
        }
        .tracker-progress-line {
            position: absolute;
            top: 25px;
            left: 5%;
            height: 4px;
            background-color: var(--primary-orange);
            z-index: 2;
            transition: width 0.5s ease;
        }
        .status-step {
            position: relative;
            z-index: 3;
            text-align: center;
            width: 25%;
        }
        .status-dot {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #fff;
            border: 4px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px auto;
            color: #6c757d;
            font-size: 18px;
            transition: all 0.3s;
        }
        .status-step.completed .status-dot {
            border-color: var(--primary-orange);
            background-color: var(--primary-orange);
            color: #fff;
        }
        .status-step.active .status-dot {
            border-color: var(--primary-orange);
            background-color: #fff;
            color: var(--primary-orange);
            box-shadow: 0 0 0 5px rgba(255, 107, 0, 0.2);
            animation: pulse-orange 1.5s infinite;
        }
        .status-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
        }
        .status-step.active .status-label,
        .status-step.completed .status-label {
            color: var(--text-dark);
        }
        
        @keyframes pulse-orange {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index">
                <img src="assets/images/logo.png" alt="Café-Chinos" class="navbar-logo" style="height:56px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            <a href="index" class="btn btn-outline-yellow btn-sm"><i class="bi bi-house-door-fill me-1"></i> Order More</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Thank You Card -->
                <div class="card border-0 shadow-sm p-5 rounded-4 bg-white mb-4 text-center d-print-none">
                    <div class="mb-3">
                        <i class="bi bi-patch-check-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="fw-bold text-dark">Thank You for Your Order!</h2>
                    <p class="text-muted">Your order has been received and is sent directly to the kitchen. Order ID is <strong>#<?= $order['id'] ?></strong></p>
                    <span class="badge px-4 py-2 mt-2" style="background-color: var(--primary-orange); font-size: 14px;">Cash on Delivery: <?= format_price($order['grand_total']) ?></span>
                </div>

                <!-- Live Tracker Card -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4 d-print-none">
                    <h5 class="fw-bold mb-1"><i class="bi bi-clock-history text-orange me-2" style="color: var(--primary-orange);"></i> Live Order Status</h5>
                    <p class="text-muted small">Our kitchen and rider status are updated in real-time below.</p>
                    
                    <?php if ($order['status'] === 'Cancelled'): ?>
                        <div class="alert alert-danger d-flex align-items-center mt-3" role="alert">
                            <i class="bi bi-x-circle-fill fs-4 me-2"></i>
                            <div>
                                <strong>Order Cancelled:</strong> This order has been cancelled by the administrator. Please contact support.
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Progress tracker -->
                        <div class="status-tracker">
                            <!-- Progress line width based on active step -->
                            <?php 
                                $line_widths = [0 => '0%', 1 => '33.3%', 2 => '66.6%', 3 => '100%'];
                                $width = $line_widths[$current_step] ?? '0%';
                            ?>
                            <div class="tracker-progress-line" style="width: <?= $width ?>;"></div>
                            
                            <!-- Step 1: Pending -->
                            <div class="status-step <?= $current_step >= 0 ? ($current_step == 0 ? 'active' : 'completed') : '' ?>">
                                <div class="status-dot"><i class="bi bi-journal-check"></i></div>
                                <span class="status-label d-block">Pending</span>
                            </div>

                            <!-- Step 2: Preparing -->
                            <div class="status-step <?= $current_step >= 1 ? ($current_step == 1 ? 'active' : 'completed') : '' ?>">
                                <div class="status-dot"><i class="bi bi-egg-fried"></i></div>
                                <span class="status-label d-block">Preparing</span>
                            </div>

                            <!-- Step 3: Out for Delivery -->
                            <div class="status-step <?= $current_step >= 2 ? ($current_step == 2 ? 'active' : 'completed') : '' ?>">
                                <div class="status-dot"><i class="bi bi-bicycle"></i></div>
                                <span class="status-label d-block">Out for Delivery</span>
                            </div>

                            <!-- Step 4: Delivered -->
                            <div class="status-step <?= $current_step >= 3 ? 'active' : '' ?>">
                                <div class="status-dot"><i class="bi bi-house-heart"></i></div>
                                <span class="status-label d-block">Delivered</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Details Receipt (On Screen) -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4 d-print-none" id="orderReceipt">
                    <!-- Invoice Header -->
                    <div class="row mb-4 pb-4 border-bottom align-items-center">
                        <div class="col-sm-6 mb-3 mb-sm-0 d-flex align-items-center">
                            <img src="assets/images/logo.png" alt="Café-Chinos" style="height:48px; width:auto; object-fit:contain;" class="me-3">
                            <div>
                                <h4 class="fw-bold mb-0 text-dark" style="font-family: 'Poppins', sans-serif; letter-spacing: -0.5px;">Café-Chinos</h4>
                                <span class="text-muted small">Premium Food Delivery</span>
                            </div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h5 class="fw-bold text-dark mb-1">INVOICE</h5>
                            <span class="badge bg-orange-light text-orange mb-1">ORDER #<?= $order['id'] ?></span>
                            <div class="text-muted small"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></div>
                        </div>
                    </div>

                    <!-- Client & Order Info -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="text-muted mb-2 font-weight-bold" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase;">Delivery Details</h6>
                            <div class="text-dark fw-bold"><?= sanitize($order['customer_name']) ?></div>
                            <div class="text-muted small" style="margin-top: 2px;"><?= sanitize($order['phone']) ?></div>
                            <div class="text-muted small mt-1" style="max-width: 280px;"><?= sanitize($order['address']) ?>, <?= sanitize($order['area_name']) ?>, Chiniot</div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted mb-2 font-weight-bold" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase;">Payment & Status</h6>
                            <div class="text-dark fw-bold">Method: Cash on Delivery (COD)</div>
                            <div class="mt-1"><span class="badge bg-success-light text-success"><?= $order['status'] ?></span></div>
                        </div>
                    </div>

                    <?php if (!empty($order['order_notes'])): ?>
                        <div class="p-3 bg-light rounded-3 mb-4 border-start border-3 border-warning">
                            <span class="text-muted d-block small fw-bold uppercase">Customer Note:</span>
                            <span class="text-dark small italic">"<?= sanitize($order['order_notes']) ?>"</span>
                        </div>
                    <?php endif; ?>

                    <!-- Items Purchased Table -->
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr class="border-bottom border-top" style="border-color: #f1f3f5 !important;">
                                    <th class="text-muted py-3" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase;">Item Description</th>
                                    <th class="text-muted text-center py-3" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase; width: 80px;">Qty</th>
                                    <th class="text-muted text-end py-3" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase; width: 120px;">Price</th>
                                    <th class="text-muted text-end py-3" style="font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase; width: 120px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr class="border-bottom" style="border-color: #f8f9fa !important;">
                                        <td class="py-3">
                                            <div class="fw-bold text-dark"><?= sanitize($item['product_name']) ?></div>
                                            <div class="text-muted small mt-1">
                                                Size: <?= sanitize($item['size_name']) ?>
                                                <?php if (!empty($item['drink_name'])): ?>
                                                    | Drink: <?= sanitize($item['drink_name']) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($item['addons'])): ?>
                                                    <?php 
                                                    $addons_arr = json_decode($item['addons'], true);
                                                    if (is_array($addons_arr)) {
                                                        echo ' | Addons: ' . implode(', ', array_map(function($a) { return sanitize($a['name']); }, $addons_arr));
                                                    }
                                                    ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold text-dark py-3">x<?= $item['quantity'] ?></td>
                                        <td class="text-end text-dark py-3">Rs. <?= number_format($item['item_price'], 2) ?></td>
                                        <td class="text-end fw-bold text-dark py-3">Rs. <?= number_format($item['line_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Block -->
                    <div class="row justify-content-end mt-4">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal</span>
                                <span class="text-dark fw-bold small">Rs. <?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Delivery Charges</span>
                                <span class="text-dark fw-bold small">Rs. <?= number_format($order['delivery_fee'], 2) ?></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Grand Total Paid</span>
                                <span class="fw-bold fs-5 text-orange" style="color: var(--primary-orange) !important;">Rs. <?= number_format($order['grand_total'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Details Receipt (POS Thermal Printer optimized - Hidden on screen) -->
                <div id="thermalReceiptPrint" class="d-none d-print-block thermal-receipt-print">
                    <!-- Header: Centered logo and restaurant info -->
                    <div class="text-center mb-2">
                        <h4 class="fw-bold mb-0 text-dark" style="font-size: 18px; letter-spacing: 0.5px;">CAFÉ-CHINOS</h4>
                        <div class="small">Premium Food Delivery</div>
                        <div class="small">359-V Nao Gazah Rd, Chiniot</div>
                        <div class="small">Tel: <?= sanitize(get_setting('contact_number', '0311 7593578')) ?></div>
                    </div>

                    <!-- Divider -->
                    <div class="receipt-divider">-----------------------------------------</div>

                    <!-- Ticket Meta Info -->
                    <div class="row small mb-2">
                        <div class="col-6">
                            <strong>Ticket:</strong> #<?= $order['id'] ?><br>
                            <strong>Customer:</strong> <?= sanitize($order['customer_name']) ?><br>
                            <strong>Phone:</strong> <?= sanitize($order['phone']) ?>
                        </div>
                        <div class="col-6 text-end">
                            <strong>Date:</strong> <?= date('d/m/Y', strtotime($order['created_at'])) ?><br>
                            <strong>Time:</strong> <?= date('h:i A', strtotime($order['created_at'])) ?><br>
                            <strong>Status:</strong> <?= $order['status'] ?>
                        </div>
                    </div>
                    
                    <div class="small mb-2">
                        <strong>Address:</strong> <?= sanitize($order['address']) ?>, <?= sanitize($order['area_name']) ?>
                    </div>

                    <!-- Divider -->
                    <div class="receipt-divider">-----------------------------------------</div>

                    <!-- Table Header -->
                    <div class="row small fw-bold" style="margin-bottom: 4px;">
                        <div class="col-6">Units Description</div>
                        <div class="col-3 text-end">U.Price</div>
                        <div class="col-3 text-end">Total</div>
                    </div>

                    <!-- Divider -->
                    <div class="receipt-divider">-----------------------------------------</div>

                    <!-- Table Items -->
                    <?php foreach ($order_items as $item): ?>
                        <div class="row small mb-2 align-items-start">
                            <div class="col-6">
                                <?= $item['quantity'] ?>x <?= sanitize($item['product_name']) ?>
                                <div class="text-muted" style="font-size: 9px; padding-left: 8px;">
                                    Size: <?= sanitize($item['size_name']) ?>
                                    <?php if (!empty($item['drink_name'])): ?>
                                        | Drink: <?= sanitize($item['drink_name']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($item['addons'])): ?>
                                        <?php 
                                        $addons_arr = json_decode($item['addons'], true);
                                        if (is_array($addons_arr)) {
                                            echo '<br>+ Addons: ' . implode(', ', array_map(function($a) { return sanitize($a['name']); }, $addons_arr));
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-3 text-end">
                                <?= number_format($item['item_price'], 0) ?>
                            </div>
                            <div class="col-3 text-end fw-bold">
                                <?= number_format($item['line_total'], 0) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Double Divider -->
                    <div class="receipt-divider">=========================================</div>

                    <!-- Summary -->
                    <div class="row small mb-1 justify-content-end">
                        <div class="col-6 text-end">SUB TOTAL:</div>
                        <div class="col-6 text-end fw-bold">Rs. <?= number_format($order['subtotal'], 2) ?></div>
                    </div>
                    <div class="row small mb-1 justify-content-end">
                        <div class="col-6 text-end">DELIVERY:</div>
                        <div class="col-6 text-end fw-bold">Rs. <?= number_format($order['delivery_fee'], 2) ?></div>
                    </div>

                    <!-- Divider -->
                    <div class="receipt-divider">-----------------------------------------</div>

                    <!-- Grand Total -->
                    <div class="row mb-3 justify-content-end align-items-center">
                        <div class="col-6 text-end fw-bold" style="font-size: 13px;">Total:</div>
                        <div class="col-6 text-end fw-bold" style="font-size: 15px;">Rs. <?= number_format($order['grand_total'], 2) ?></div>
                    </div>

                    <!-- Footer message -->
                    <div class="text-center small mt-4">
                        <div>Thank you!!!</div>
                        <div class="fw-bold mt-1">CAFÉ-CHINOS</div>
                        <div class="receipt-divider">-----------------------------------------</div>
                        <div style="font-size: 8px; margin-top: 5px;">Software Developed by<br>DevtaSoft Software Company<br>03085277092</div>
                    </div>
                </div>

                <!-- Download/Print Button -->
                <div class="text-center d-print-none mb-4">
                    <button onclick="window.print()" class="btn btn-primary-orange w-100 py-3 fw-bold fs-6">
                        <i class="bi bi-download me-2"></i> Download / Print Invoice
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
                        <li class="mb-2"><a href="policies?type=refund" class="text-muted small">Return & Refund Policy</a></li>
                        <li class="mb-2"><a href="policies?type=terms" class="text-muted small">Terms of Service</a></li>
                        <li class="mb-2"><a href="policies?type=privacy" class="text-muted small">Privacy Policy</a></li>
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

    <!-- AJAX Real-Time Polling for Status Updates -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const orderId = <?= $order_id ?>;
            const progressLine = document.querySelector('.tracker-progress-line');
            const steps = document.querySelectorAll('.status-step');
            
            const lineWidths = {
                'Pending': '0%',
                'Preparing': '33.3%',
                'Out for Delivery': '66.6%',
                'Delivered': '100%'
            };

            function checkOrderStatus() {
                fetch(`order-success.php?id=${orderId}&action=check_status`)
                    .then(res => res.json())
                    .then(data => {
                        const status = data.status;
                        
                        if (status === 'Cancelled') {
                            window.location.reload(); // Reload to show the cancellation notice banner
                            return;
                        }

                        // Update Progress Line Width
                        if (progressLine && lineWidths[status]) {
                            progressLine.style.width = lineWidths[status];
                        }

                        // Update steps classing
                        let stepIndex = -1;
                        if (status === 'Pending') stepIndex = 0;
                        if (status === 'Preparing') stepIndex = 1;
                        if (status === 'Out for Delivery') stepIndex = 2;
                        if (status === 'Delivered') stepIndex = 3;

                        steps.forEach((step, idx) => {
                            if (idx < stepIndex) {
                                step.classList.remove('active');
                                step.classList.add('completed');
                            } else if (idx === stepIndex) {
                                step.classList.remove('completed');
                                step.classList.add('active');
                            } else {
                                step.classList.remove('completed', 'active');
                            }
                        });
                    })
                    .catch(err => console.error('Status check error:', err));
            }

            // Poll every 8 seconds
            setInterval(checkOrderStatus, 8000);
        });
    </script>
</body>
</html>

