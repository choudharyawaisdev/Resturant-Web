<?php
// policies.php
require_once __DIR__ . '/includes/functions.php';

$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'refund';

// Set page content variables
$page_title = 'Policies - Café-Chinos';
$header_title = 'Policy Information';
$content_html = '';

if ($type === 'refund') {
    $page_title = 'Return & Refund Policy - Café-Chinos';
    $header_title = 'Return & Refund Policy';
    $content_html = '
        <h4 class="fw-bold mb-3" style="color: var(--primary-orange);">1. Freshness Guarantee</h4>
        <p>At Café-Chinos, we take absolute pride in the quality and freshness of our meals. Every order is prepared warm and cooked to order using the finest ingredients. If your order does not meet your expectations, we want to make it right.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">2. Cancellation Policy</h4>
        <p>Orders can only be cancelled or modified before our kitchen updates the status to <strong>"Preparing"</strong>. Once the food is in preparation or out for delivery, cancellations cannot be accepted, and payment is due in full upon delivery.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">3. Wrong, Cold, or Damaged Items</h4>
        <p>If you receive items that are incorrect, damaged during transit, or not hot, please contact our Chiniot helpline at <strong>' . sanitize(get_setting('contact_number', '0311 7593578')) . '</strong> within <strong>30 minutes</strong> of delivery. We will verify the details and arrange:</p>
        <ul>
            <li>A complimentary replacement item delivered immediately.</li>
            <li>A voucher code or credit towards your next order.</li>
            <li>A cash refund at your doorstep for cash-on-delivery orders.</li>
        </ul>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">4. Refusals at Doorstep</h4>
        <p>Refusing to accept a correctly prepared order without valid cause may result in the suspension of your guest account and your delivery address from future orders on our platform.</p>
    ';
} elseif ($type === 'terms') {
    $page_title = 'Terms of Service - Café-Chinos';
    $header_title = 'Terms & Conditions of Service';
    $content_html = '
        <h4 class="fw-bold mb-3" style="color: var(--primary-orange);">1. Acceptance of Terms</h4>
        <p>By accessing our website or placing an food order at Café-Chinos, you agree to comply with and be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">2. Service Coverage Sectors</h4>
        <p>Delivery is currently restricted to active pre-configured sectors in <strong>Chiniot</strong>. If your delivery address does not fall within our active zones, we reserve the right to decline your order. Delivery charges vary by distance and sector location.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">3. User Responsibility & Verification</h4>
        <p>When ordering as a guest or logged-in account, you agree to provide an accurate contact phone number and detailed delivery street address. We may call your number to verify the order before the kitchen starts preparation.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">4. Modifications to Prices & Menu</h4>
        <p>Café-Chinos reserves the right to modify prices, customization addon fees, delivery charges, and menu selections at any time without prior notice. All active orders placed prior to price adjustments will be honored at their original checkout values.</p>
    ';
} elseif ($type === 'privacy') {
    $page_title = 'Privacy Policy - Café-Chinos';
    $header_title = 'Privacy Policy';
    $content_html = '
        <h4 class="fw-bold mb-3" style="color: var(--primary-orange);">1. Information We Collect</h4>
        <p>To process your delicious meals and deliver them safely, we collect the following basic information:</p>
        <ul>
            <li>Full Name (for order receipt and tracking)</li>
            <li>Contact Phone Number (critical for delivery verification)</li>
            <li>Street Address and Selected Delivery Sector (for delivery mapping)</li>
            <li>Email Address (for security credentials and order confirmation logs)</li>
        </ul>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">2. Data Storage & Security</h4>
        <p>All passwords stored in our system are encrypted using industry-standard bcrypt hashes. We take server-side security measures to prevent unauthorized data access, leakage, or loss.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">3. Sharing of Information</h4>
        <p>Café-Chinos does not sell, rent, or lease your personal information to third parties. Your address and contact details are only shared internally with our kitchen dispatchers and designated riders solely for the purpose of completing your order.</p>

        <h4 class="fw-bold mb-3 mt-4" style="color: var(--primary-orange);">4. Cookies and Sessions</h4>
        <p>Our website uses session parameters and local storage to save your configured cart contents and remember your designated delivery location area between page reloads.</p>
    ';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS with Cache Buster -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index">
                <img src="assets/images/logo.png" alt="Café-Chinos" class="navbar-logo" style="height:56px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            
            <div class="d-flex align-items-center">
                <a href="index" class="btn btn-outline-orange btn-sm"><i class="bi bi-arrow-left"></i> Back to Menu Directory</a>
            </div>
        </div>
    </nav>

    <!-- CONTENT BODY -->
    <div class="container my-5" style="max-width: 850px;">
        <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="index" class="text-orange text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Help & Policies</li>
                </ol>
            </nav>
            
            <h1 class="fw-bold mb-4 border-bottom pb-3 text-dark"><?= $header_title ?></h1>
            
            <div class="policy-body text-dark" style="line-height: 1.7; font-size: 15px;">
                <?= $content_html ?>
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
</body>
</html>

