<?php
// login.php
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_user_logged_in()) {
    redirect('index');
}

$error_msg = '';
$success_msg = '';
$active_form = 'login'; // 'login' or 'register'

// Fetch delivery areas for signup dropdown
$areas = $pdo->query("SELECT * FROM areas WHERE status = 'active' ORDER BY area_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // --- CUSTOMER SIGN IN ---
    if ($action === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        $password = sanitize($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error_msg = 'Please enter your email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                if ($user['area_id'] > 0) {
                    $_SESSION['delivery_area_id'] = $user['area_id'];
                }
                $_SESSION['flash_success'] = 'Welcome back, ' . $user['name'] . '! You are now logged in.';
                redirect('index');
            } else {
                $error_msg = 'Invalid email or password.';
            }
        }
    }

    // --- CUSTOMER SIGN UP ---
    if ($action === 'register') {
        $active_form = 'register';
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $area_id = intval($_POST['area_id'] ?? 0);
        $password = sanitize($_POST['password'] ?? '');
        $conf_password = sanitize($_POST['conf_password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            $error_msg = 'Full Name, Email and Password are required.';
        } elseif ($password !== $conf_password) {
            $error_msg = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Password must be at least 6 characters.';
        } else {
            // Check if email already registered
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $error_msg = 'This email is already registered. Please log in.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, address, area_id, password)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                try {
                    $stmt->execute([$name, $email, $phone, $address, $area_id > 0 ? $area_id : null, $hash]);
                    
                    // Log in the new user immediately
                    $new_id = $pdo->lastInsertId();
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    if ($area_id > 0) {
                        $_SESSION['delivery_area_id'] = $area_id;
                    }
                    $_SESSION['flash_success'] = 'Account created! Welcome, ' . $name . '!';
                    redirect('index');
                } catch (Exception $e) {
                    $error_msg = 'Error creating account: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Signup - CafÃ©-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .auth-card {
            width: 480px;
            background: #fff;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            border: none;
            padding: 30px;
        }
        .auth-nav {
            display: flex;
            border-bottom: 2px solid #f1f1f1;
            margin-bottom: 25px;
        }
        .auth-tab {
            flex-grow: 1;
            text-align: center;
            padding: 12px;
            font-weight: 600;
            cursor: pointer;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .auth-tab.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="CafÃ©-Chinos" class="navbar-logo" style="height:56px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            <a href="index" class="btn btn-outline-yellow btn-sm"><i class="bi bi-arrow-left"></i> Menu Directory</a>
        </div>
    </nav>

    <div class="container auth-container">
        <div class="auth-card">
            
            <div class="auth-nav">
                <div class="auth-tab <?= $active_form === 'login' ? 'active' : '' ?>" id="tabLogin" onclick="toggleAuthForm('login')">Sign In</div>
                <div class="auth-tab <?= $active_form === 'register' ? 'active' : '' ?>" id="tabRegister" onclick="toggleAuthForm('register')">Sign Up</div>
            </div>

            <!-- LOGIN FORM -->
            <form action="login" method="POST" id="formLogin" style="display: <?= $active_form === 'login' ? 'block' : 'none' ?>;">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="yourname@email.com" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-muted">PASSWORD</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢" required>
                </div>

                <button type="submit" class="btn btn-primary-orange w-100 py-3 fw-bold">
                    Sign In to Order <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>

            <!-- REGISTER FORM -->
            <form action="login" method="POST" id="formRegister" style="display: <?= $active_form === 'register' ? 'block' : 'none' ?>;">
                <input type="hidden" name="action" value="register">

                <div class="mb-3">
                    <label for="reg_name" class="form-label small fw-bold text-muted">FULL NAME</label>
                    <input type="text" name="name" id="reg_name" class="form-control" placeholder="e.g. Hassan Ahmed" required>
                </div>

                <div class="mb-3">
                    <label for="reg_email" class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                    <input type="email" name="email" id="reg_email" class="form-control" placeholder="yourname@email.com" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="reg_phone" class="form-label small fw-bold text-muted">PHONE NUMBER</label>
                        <input type="tel" name="phone" id="reg_phone" class="form-control" placeholder="03001234567">
                    </div>
                    <div class="col-6">
                        <label for="reg_area" class="form-label small fw-bold text-muted">DELIVERY AREA</label>
                        <select name="area_id" id="reg_area" class="form-select">
                            <option value="">-- Select Area --</option>
                            <?php foreach ($areas as $ar): ?>
                                <option value="<?= $ar['id'] ?>"><?= sanitize($ar['area_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reg_address" class="form-label small fw-bold text-muted">STREET ADDRESS DETAIL</label>
                    <textarea name="address" id="reg_address" rows="2" class="form-control" placeholder="House/Flat number, Street name..."></textarea>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <label for="reg_pass" class="form-label small fw-bold text-muted">PASSWORD</label>
                        <input type="password" name="password" id="reg_pass" class="form-control" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="col-6">
                        <label for="reg_conf" class="form-label small fw-bold text-muted">CONFIRM</label>
                        <input type="password" name="conf_password" id="reg_conf" class="form-control" placeholder="Verify password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-orange w-100 py-3 fw-bold">
                    Create Account & Log In <i class="bi bi-person-plus-fill ms-1"></i>
                </button>
            </form>

        </div>
    </div>

    <!-- DevtaSoft Professional Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row g-4 text-start">
                <!-- Column 1: Brand Info -->
                <div class="col-lg-4 col-md-6">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="CafÃ©-Chinos" style="height:70px; width:auto; object-fit:contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
                    </a>
                    <p class="small text-muted mt-2">
                        CafÃ©-Chinos brings the taste of fresh, premium warm food right to your doorstep in Chiniot. From masterfully crafted zingers to authentic brick-oven pizzas, satisfaction is just a click away.
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
                        Â© <?= date('Y') ?> <?= sanitize(get_setting('restaurant_name', 'CafÃ©-Chinos')) ?>. All Rights Reserved.
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
        function toggleAuthForm(mode) {
            const formLogin = document.getElementById('formLogin');
            const formRegister = document.getElementById('formRegister');
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');

            if (mode === 'login') {
                formLogin.style.display = 'block';
                formRegister.style.display = 'none';
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
            } else {
                formLogin.style.display = 'none';
                formRegister.style.display = 'block';
                tabLogin.classList.remove('active');
                tabRegister.classList.add('active');
            }
        }
    </script>

    <?php if (!empty($error_msg)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: '<?= addslashes(strip_tags($error_msg)) ?>',
                    confirmButtonColor: '#FF6B00',
                    confirmButtonText: 'Try Again',
                    background: '#fff',
                    customClass: { popup: 'rounded-4' }
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>


