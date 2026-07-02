<?php
// login.php
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_user_logged_in()) {
    redirect('index.php');
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

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                if ($user['area_id'] > 0) {
                    $_SESSION['delivery_area_id'] = $user['area_id'];
                }
                $success_msg = 'Logged in successfully!';
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
                    INSERT INTO users (name, email, phone, address, area_id, password_hash)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                try {
                    $stmt->execute([$name, $email, $phone, $address, $area_id > 0 ? $area_id : null, $hash]);
                    
                    // Log in the user
                    $new_id = $pdo->lastInsertId();
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    if ($area_id > 0) {
                        $_SESSION['delivery_area_id'] = $area_id;
                    }
                    $success_msg = 'Account created and logged in!';
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
    <title>Login & Signup - Cravers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
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
                <svg width="35" height="35" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                </svg>
                <span class="fs-4 fw-bold text-dark">Cravers<span style="color: var(--primary-orange);">.</span></span>
            </a>
            <a href="index.php" class="btn btn-outline-yellow btn-sm"><i class="bi bi-arrow-left"></i> Menu Directory</a>
        </div>
    </nav>

    <div class="container auth-container">
        <div class="auth-card">
            
            <div class="auth-nav">
                <div class="auth-tab <?= $active_form === 'login' ? 'active' : '' ?>" id="tabLogin" onclick="toggleAuthForm('login')">Sign In</div>
                <div class="auth-tab <?= $active_form === 'register' ? 'active' : '' ?>" id="tabRegister" onclick="toggleAuthForm('register')">Sign Up</div>
            </div>

            <!-- LOGIN FORM -->
            <form action="login.php" method="POST" id="formLogin" style="display: <?= $active_form === 'login' ? 'block' : 'none' ?>;">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="yourname@email.com" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-muted">PASSWORD</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••" required>
                </div>

                <button type="submit" class="btn btn-primary-orange w-100 py-3 fw-bold">
                    Sign In to Order <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>

            <!-- REGISTER FORM -->
            <form action="login.php" method="POST" id="formRegister" style="display: <?= $active_form === 'register' ? 'block' : 'none' ?>;">
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
            Swal.fire({
                icon: 'error',
                title: 'Authentication Failed',
                text: '<?= sanitize($error_msg) ?>',
                confirmButtonColor: '#FF6B00'
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Welcome Back!',
                text: '<?= sanitize($success_msg) ?>',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>
    <?php endif; ?>
</body>
</html>
