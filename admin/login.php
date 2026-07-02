<?php
// admin/login.php
require_once dirname(__DIR__) . '/includes/functions.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('dashboard.php');
        } else {
            $error_msg = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cravers</title>
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
                <svg width="50" height="50" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-3">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                </svg>
                <h4 class="fw-bold">Cravers Admin</h4>
                <p class="text-muted small">Enter your credentials to manage restaurant operations.</p>
            </div>

            <form action="login.php" method="POST" id="loginForm">
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
