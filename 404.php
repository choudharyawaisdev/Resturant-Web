<?php
// 404.php - Custom Page Not Found Error Page
http_response_code(404);
require_once __DIR__ . '/includes/functions.php';

$page_title = '404 - Page Not Found | Café-Chinos';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS with Cache Buster -->
    <link href="/Resturant-Web/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">

    <style>
        .error-hero {
            min-height: 65vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            position: relative;
            overflow: hidden;
        }

        .error-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 28px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.06);
            padding: 48px 36px;
            max-width: 620px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .error-code {
            font-size: clamp(80px, 15vw, 130px);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, var(--primary-orange, #FF6B00) 0%, #FF8A00 50%, #FF4500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: 'Poppins', sans-serif;
            letter-spacing: -2px;
            margin-bottom: 8px;
            text-shadow: 0 10px 25px rgba(255, 107, 0, 0.2);
            user-select: none;
        }

        .error-icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(255, 107, 0, 0.1);
            color: var(--primary-orange, #FF6B00);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin-bottom: 20px;
            border: 2px dashed rgba(255, 107, 0, 0.35);
            animation: pulseSoft 2.5s infinite ease-in-out;
        }

        @keyframes pulseSoft {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        .error-title {
            font-size: 26px;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 12px;
        }

        .error-desc {
            color: #64748B;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--primary-orange, #FF6B00) 0%, #FF8A00 100%);
            color: #FFFFFF !important;
            border: none;
            border-radius: 50px;
            padding: 12px 28px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(255, 107, 0, 0.35);
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, #E05E00 0%, #FF7A00 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 107, 0, 0.45);
        }

        .btn-action-secondary {
            background: #F1F5F9;
            color: #334155 !important;
            border: 1px solid #E2E8F0;
            border-radius: 50px;
            padding: 12px 26px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-secondary:hover {
            background: #E2E8F0;
            color: #0F172A !important;
            transform: translateY(-2px);
        }

        .decorative-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.22;
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Top Sticky Header -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/Resturant-Web/index">
                <img src="/Resturant-Web/assets/images/logo.png" alt="Café-Chinos" class="navbar-logo"
                    style="height:54px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>

            <div class="d-flex align-items-center gap-2">
                <a href="/Resturant-Web/index" class="btn btn-outline-orange btn-sm rounded-pill px-3 py-2 fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Back to Menu
                </a>
            </div>
        </div>
    </nav>

    <!-- 404 Main Container -->
    <main class="error-hero flex-grow-1">
        <!-- Decorative Ambient Glows -->
        <div class="decorative-blob" style="width: 320px; height: 320px; background: #FF6B00; top: 10%; left: 15%;">
        </div>
        <div class="decorative-blob" style="width: 280px; height: 280px; background: #FFA800; bottom: 10%; right: 15%;">
        </div>

        <div class="error-card">
            <div class="error-icon-circle">
                <i class="bi bi-cup-hot-fill"></i>
            </div>

            <div class="error-code">404</div>
            <h1 class="error-title">Oops! This Dish Isn't on the Menu</h1>
            <p class="error-desc">
                The page you are looking for might have been cooked away, had its link changed, or is temporarily
                unavailable. Let's get you back to the delicious food!
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="/Resturant-Web/index" class="btn-action-primary">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Go to Home Menu</span>
                </a>
                <a href="/Resturant-Web/policies?type=refund" class="btn-action-secondary">
                    <i class="bi bi-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </div>

            <!-- Quick Assistance Helpline -->
            <div class="mt-4 pt-3 border-top text-muted small">
                <span>Need immediate assistance? Call our Chiniot helpline: </span>
                <a href="tel:<?= sanitize(get_setting('contact_number', '03117593578')) ?>"
                    class="fw-bold text-decoration-none" style="color: var(--primary-orange);">
                    <i
                        class="bi bi-telephone-fill me-1"></i><?= sanitize(get_setting('contact_number', '0311 7593578')) ?>
                </a>
            </div>
        </div>
    </main>

    <!-- DevtaSoft Professional Footer -->
    <footer class="main-footer mt-auto">
        <div class="container">
            <div class="row g-4 text-start">
                <!-- Column 1: Brand Info -->
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <a href="/Resturant-Web/index">
                        <img src="/Resturant-Web/assets/images/logo.png" alt="Café-Chinos"
                            style="height:65px; width:auto; object-fit:contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
                    </a>
                    <p class="small text-muted mt-2">
                        Café-Chinos brings the taste of fresh, premium warm food right to your doorstep in Chiniot. From
                        masterfully crafted zingers to authentic brick-oven pizzas, satisfaction is just a click away.
                    </p>
                    <div class="footer-social-links mt-3">
                        <a href="https://facebook.com" target="_blank" rel="noopener" class="social-link-btn"
                            title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://instagram.com" target="_blank" rel="noopener" class="social-link-btn"
                            title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://wa.me/923117593578" target="_blank" rel="noopener" class="social-link-btn"
                            title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links / Categories -->
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <h6 class="footer-heading">Quick Menu</h6>
                    <ul class="footer-links">
                        <li><a href="/Resturant-Web/index#category-1"><i class="bi bi-chevron-right"></i> Hot Deals</a>
                        </li>
                        <li><a href="/Resturant-Web/index#category-2"><i class="bi bi-chevron-right"></i> Pizzas &
                                Crusts</a></li>
                        <li><a href="/Resturant-Web/index#category-3"><i class="bi bi-chevron-right"></i> Crispy
                                Burgers</a></li>
                        <li><a href="/Resturant-Web/index#category-4"><i class="bi bi-chevron-right"></i> Wings &
                                Starters</a></li>
                    </ul>
                </div>

                <!-- Column 3: Help & Policies -->
                <div class="col-lg-2 col-md-6 col-sm-6 col-12">
                    <h6 class="footer-heading">Help & Policies</h6>
                    <ul class="footer-links">
                        <li><a href="/Resturant-Web/policies?type=refund"><i class="bi bi-chevron-right"></i> Refund
                                Policy</a></li>
                        <li><a href="/Resturant-Web/policies?type=terms"><i class="bi bi-chevron-right"></i> Terms of
                                Service</a></li>
                        <li><a href="/Resturant-Web/policies?type=privacy"><i class="bi bi-chevron-right"></i> Privacy
                                Policy</a></li>
                        <li><a href="/Resturant-Web/checkout"><i class="bi bi-chevron-right"></i> Cart Checkout</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact & Hours -->
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <h6 class="footer-heading">Chiniot Branch</h6>
                    <div class="footer-contact-item">
                        <i class="bi bi-geo-alt-fill text-orange"></i>
                        <span>Near Islamia Hospital, Chiniot, Punjab</span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="bi bi-telephone-fill text-orange"></i>
                        <span>0319-7793578 / 0311-7593578</span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="bi bi-clock-fill text-orange"></i>
                        <span>Open Daily: 11:00 AM - 02:00 AM</span>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Café-Chinos (Chiniot). All rights reserved. Powered by <a
                        href="#" class="devtasoft-link">DevtaSoft</a>.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>