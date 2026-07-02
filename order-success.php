<?php
// order-success.php
require_once __DIR__ . '/includes/functions.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    redirect('index.php');
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
    echo '<div class="container my-5 text-center"><h3>Order not found.</h3><a href="index.php">Return to Home</a></div>';
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
    <title>Order #<?= $order['id'] ?> - Cravers</title>
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
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg width="35" height="35" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                </svg>
                <span class="fs-4 fw-bold text-dark">Cravers<span style="color: var(--primary-orange);">.</span></span>
            </a>
            <a href="index.php" class="btn btn-outline-yellow btn-sm"><i class="bi bi-house-door-fill me-1"></i> Order More</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Thank You Card -->
                <div class="card border-0 shadow-sm p-5 rounded-4 bg-white mb-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-patch-check-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="fw-bold text-dark">Thank You for Your Order!</h2>
                    <p class="text-muted">Your order has been received and is sent directly to the kitchen. Order ID is <strong>#<?= $order['id'] ?></strong></p>
                    <span class="badge px-4 py-2 mt-2" style="background-color: var(--primary-orange); font-size: 14px;">Cash on Delivery: <?= format_price($order['grand_total']) ?></span>
                </div>

                <!-- Live Tracker Card -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
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

                <!-- Order Details Receipt -->
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="bi bi-receipt-cutoff text-orange me-2" style="color: var(--primary-orange);"></i> Order Details</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <span class="text-muted d-block small">CUSTOMER NAME</span>
                            <strong class="text-dark"><?= sanitize($order['customer_name']) ?></strong>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">CONTACT PHONE</span>
                            <strong class="text-dark"><?= sanitize($order['phone']) ?></strong>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <span class="text-muted d-block small">DELIVERY ADDRESS</span>
                            <strong class="text-dark"><?= sanitize($order['address']) ?>, <?= sanitize($order['area_name']) ?>, Faisalabad</strong>
                        </div>
                    </div>

                    <?php if (!empty($order['order_notes'])): ?>
                        <div class="mb-4">
                            <span class="text-muted d-block small">SPECIAL INSTRUCTIONS</span>
                            <span class="text-dark italic small">"<?= sanitize($order['order_notes']) ?>"</span>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <h6 class="fw-bold mb-3">Items Purchased</h6>
                    <?php foreach ($order_items as $item): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom-dashed">
                            <div>
                                <strong class="text-dark"><?= sanitize($item['product_name']) ?></strong> 
                                <span class="text-orange">x<?= $item['quantity'] ?></span>
                                <span class="text-muted small d-block">
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
                                </span>
                            </div>
                            <span class="text-dark fw-bold">Rs. <?= number_format($item['line_total'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4 pt-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="text-dark fw-bold">Rs. <?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Delivery Charges</span>
                            <span class="text-dark fw-bold">Rs. <?= number_format($order['delivery_fee'], 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Grand Total Paid</h6>
                            <h5 class="fw-bold text-success mb-0" style="color: var(--primary-orange) !important;">Rs. <?= number_format($order['grand_total'], 2) ?></h5>
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
                        <?php
                        try {
                            $footer_cats = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id ASC")->fetchAll();
                            foreach ($footer_cats as $fc) {
                                echo '<li class="mb-2"><a href="index.php#category-' . intval($fc['id']) . '" class="text-muted small">' . sanitize($fc['name']) . '</a></li>';
                            }
                        } catch (Exception $e) {
                            echo '<li class="mb-2"><a href="index.php" class="text-muted small">Menu</a></li>';
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
