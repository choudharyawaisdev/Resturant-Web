<?php
// admin/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Strict']);
    session_start();
}
require_once dirname(__DIR__) . '/includes/functions.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /Resturant-Web/admin/dashboard');
    exit();
}

$error_msg = '';
$lockout_remaining = 0;

// ── Brute-force Protection ───────────────────────────────────
// Max 5 failed attempts → 15-minute lockout (stored in session)
$max_attempts  = 5;
$lockout_secs  = 900; // 15 minutes

if (!isset($_SESSION['login_attempts']))  $_SESSION['login_attempts']  = 0;
if (!isset($_SESSION['login_lockout_until'])) $_SESSION['login_lockout_until'] = 0;

$is_locked_out = (time() < $_SESSION['login_lockout_until']);
if ($is_locked_out) {
    $lockout_remaining = $_SESSION['login_lockout_until'] - time();
    $error_msg = 'Too many failed attempts. Try again in ' . ceil($lockout_remaining / 60) . ' minute(s).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked_out) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';   // raw — password_verify needs original

    if (empty($username) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // ✅ Success — reset attempts
            $_SESSION['login_attempts']     = 0;
            $_SESSION['login_lockout_until']= 0;

            $_SESSION['admin_logged_in']  = true;
            $_SESSION['admin_id']         = $admin['id'];
            $_SESSION['admin_username']   = $admin['username'];
            $_SESSION['admin_role']       = $admin['role'];
            $_SESSION['admin_last_activity'] = time();
            // Session fingerprint to prevent hijacking
            $_SESSION['admin_fingerprint'] = md5(
                ($_SERVER['HTTP_USER_AGENT'] ?? '') .
                ($_SERVER['REMOTE_ADDR']     ?? '')
            );
            // Regenerate session ID on login
            session_regenerate_id(true);

            // Update last login timestamp
            $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")
                ->execute([$admin['id']]);

            header('Location: /Resturant-Web/admin/dashboard');
            exit();
        } else {
            // ❌ Failed attempt
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['login_lockout_until'] = time() + $lockout_secs;
                $_SESSION['login_attempts']      = 0;
                $error_msg = 'Too many failed attempts. Locked out for 15 minutes.';
            } else {
                $remaining = $max_attempts - $_SESSION['login_attempts'];
                $error_msg = 'Invalid username or password. ' . $remaining . ' attempt(s) remaining.';
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
    <title>Admin Login - Café-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-warm);
        }
        .login-card {
            width: 400px;
            border-radius: var(--border-radius-lg);
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            border: none;
            background-color: #fff;
            padding: 30px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="../assets/images/logo.png" alt="Café-Chinos" class="mb-3" style="height:60px; width:auto; object-fit:contain; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.2));">
                <h4 class="fw-bold">Café-Chinos Admin</h4>
                <p class="text-muted small">Enter your credentials to manage restaurant operations.</p>
            </div>

            <form action="/Resturant-Web/admin/login" method="POST" id="loginForm" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold text-muted">USERNAME</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="username" id="username" class="form-control border-start-0" placeholder="Enter username" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-muted">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="Enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-orange w-100 py-2.5 fw-bold">
                    Log In <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle with Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($error_msg)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Authentication Error',
                text: '<?= sanitize($error_msg) ?>',
                confirmButtonColor: '#FF6B00'
            });
        </script>
    <?php endif; ?>
</body>
</html>
