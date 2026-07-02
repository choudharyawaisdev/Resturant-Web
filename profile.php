<?php
// profile.php
require_once __DIR__ . '/includes/functions.php';

// Guard check
if (!is_user_logged_in()) {
    redirect('login.php');
}

$user_id = get_logged_in_user_id();
$success = '';
$error = '';
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'orders';

// Fetch customer information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('login.php');
}

// Fetch Faisalabad areas for settings dropdown
$areas = $pdo->query("SELECT * FROM areas WHERE status = 'active' ORDER BY area_name ASC")->fetchAll();

// --- POST HANDLING: UPDATE PROFILE OR UPDATE PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);

    // 1. UPDATE PROFILE SETTINGS
    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $area_id = intval($_POST['area_id'] ?? 0);
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name) || empty($email)) {
            $error = 'Name and Email are required fields.';
        } else {
            // Check email unique (excluding self)
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->fetchColumn() > 0) {
                $error = 'This email address is already used by another customer.';
            } else {
                $up = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, area_id = ?, address = ? 
                    WHERE id = ?
                ");
                $up->execute([
                    $name, $email, $phone, 
                    $area_id > 0 ? $area_id : null, 
                    $address, $user_id
                ]);
                
                // Update session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                if ($area_id > 0) {
                    $_SESSION['delivery_area_id'] = $area_id;
                }
                
                $success = 'Profile details updated successfully!';
                
                // Refresh local user details
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        }
    }

    // 2. CHANGE PASSWORD
    if ($action === 'change_password') {
        $active_tab = 'settings';
        $curr_pass = sanitize($_POST['curr_pass'] ?? '');
        $new_pass  = sanitize($_POST['new_pass'] ?? '');
        $conf_pass = sanitize($_POST['conf_pass'] ?? '');

        if (empty($curr_pass) || empty($new_pass) || empty($conf_pass)) {
            $error = 'All password fields are required.';
        } elseif ($new_pass !== $conf_pass) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            if (password_verify($curr_pass, $user['password_hash'])) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $up->execute([$new_hash, $user_id]);
                $success = 'Your account password has been changed successfully!';
            } else {
                $error = 'Incorrect current password.';
            }
        }
    }
}

// Fetch stats: total orders, total spend (excludes cancelled), wishlist count
$stats = get_user_stats($pdo, $user_id);

// Fetch orders list
$order_stmt = $pdo->prepare("
    SELECT o.*, a.area_name 
    FROM orders o 
    JOIN areas a ON o.area_id = a.id 
    WHERE o.user_id = ? 
    ORDER BY o.id DESC
");
$order_stmt->execute([$user_id]);
$orders = $order_stmt->fetchAll();

// Fetch wishlist items
$wish_stmt = $pdo->prepare("
    SELECT w.id as wishlist_id, p.*, c.name as category_name 
    FROM wishlists w 
    JOIN products p ON w.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE w.user_id = ? 
    ORDER BY w.id DESC
");
$wish_stmt->execute([$user_id]);
$wishlist_items = $wish_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Cravers</title>
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

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg width="40" height="40" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
                    <circle cx="50" cy="50" r="45" fill="#FF6B00"/>
                    <path d="M50 20C40 20 30 35 30 50C30 65 40 80 50 80C60 80 70 65 70 50C70 35 60 20 50 20ZM45 60H37V40H45V60ZM63 60H55V40H63V60Z" fill="#FFF8F0"/>
                </svg>
                <span class="fs-4 fw-bold" style="color: var(--text-dark); letter-spacing: -0.5px;"><?= sanitize(get_setting('restaurant_name', 'Cravers')) ?><span style="color: var(--primary-orange);">.</span></span>
            </a>
            
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-orange me-2 btn-sm"><i class="bi bi-shop"></i> Order Food</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Sign out</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <!-- Welcome banner with stats -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <span class="text-muted small fw-bold">WELCOME BACK,</span>
                    <h2 class="fw-bold mb-0 text-dark"><?= sanitize($user['name']) ?></h2>
                    <span class="text-muted small"><?= sanitize($user['email']) ?></span>
                </div>
                <div class="col-md-6">
                    <div class="row text-center g-2">
                        <div class="col-4 border-end">
                            <span class="text-muted small d-block mb-1">TOTAL ORDERS</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= $stats['total_orders'] ?></h4>
                        </div>
                        <div class="col-4 border-end">
                            <span class="text-muted small d-block mb-1">ALL-TIME SPENT</span>
                            <h4 class="fw-bold mb-0 text-success">Rs. <?= number_format($stats['total_spent'], 0) ?></h4>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small d-block mb-1">WISHLIST</span>
                            <h4 class="fw-bold mb-0 text-danger"><?= $stats['total_wishlist'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= sanitize($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Split Layout Tabs -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm border-0 gap-2">
            <li class="nav-item">
                <a class="nav-link text-dark <?= $active_tab === 'orders' ? 'active bg-warning fw-bold' : '' ?>" href="profile.php?tab=orders">
                    <i class="bi bi-receipt me-1"></i> My Orders Log
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark <?= $active_tab === 'wishlist' ? 'active bg-warning fw-bold' : '' ?>" href="profile.php?tab=wishlist">
                    <i class="bi bi-heart-fill text-danger me-1"></i> My Wishlist
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark <?= $active_tab === 'settings' ? 'active bg-warning fw-bold' : '' ?>" href="profile.php?tab=settings">
                    <i class="bi bi-person-gear me-1"></i> Profile Settings
                </a>
            </li>
        </ul>

        <!-- TAB 1: PAST ORDERS LOG -->
        <?php if ($active_tab === 'orders'): ?>
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4"><i class="bi bi-receipt text-orange me-2"></i> Orders History</h5>
                
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Date</th>
                                <th scope="col">Delivery Area</th>
                                <th scope="col">Grand Total</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No orders placed yet. <a href="index.php">Start shopping!</a></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td><strong>#<?= $ord['id'] ?></strong></td>
                                        <td class="small text-muted"><?= date('d M, Y h:i A', strtotime($ord['created_at'])) ?></td>
                                        <td><?= sanitize($ord['area_name']) ?></td>
                                        <td class="fw-bold">Rs. <?= number_format($ord['grand_total'], 2) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-secondary';
                                            if ($ord['status'] === 'Pending') $badge_class = 'bg-danger';
                                            elseif ($ord['status'] === 'Preparing') $badge_class = 'bg-warning text-dark';
                                            elseif ($ord['status'] === 'Out for Delivery') $badge_class = 'bg-info text-dark';
                                            elseif ($ord['status'] === 'Delivered') $badge_class = 'bg-success';
                                            elseif ($ord['status'] === 'Cancelled') $badge_class = 'bg-dark';
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= $ord['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="order-success.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark">
                                                <i class="bi bi-eye-fill"></i> Track Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- TAB 2: MY WISHLIST ITEMS -->
        <?php if ($active_tab === 'wishlist'): ?>
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4"><i class="bi bi-heart-fill text-danger me-2"></i> My Wishlist Menu</h5>
                
                <?php if (empty($wishlist_items)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-heart fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">Your wishlist is empty. Favorite foods to save them here!</p>
                        <a href="index.php" class="btn btn-primary-orange">Explore Menu</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($wishlist_items as $item): ?>
                            <!-- Map local images with category fallback -->
                            <?php
                            $default_images = [
                                1 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=400&q=80',
                                2 => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80',
                                3 => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=400&q=80',
                                4 => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=400&q=80',
                                5 => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=400&q=80',
                                6 => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=400&q=80'
                            ];
                            $img_url = $default_images[$item['category_id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=400&q=80';
                            if (!empty($item['image']) && file_exists(__DIR__ . '/assets/images/uploads/' . $item['image'])) {
                                $img_url = 'assets/images/uploads/' . $item['image'];
                            }
                            ?>
                            <div class="col-lg-3 col-md-4 col-sm-6" id="wishlist_row_<?= $item['id'] ?>">
                                <div class="card product-card position-relative">
                                    <button class="wishlist-heart-btn active" data-product-id="<?= $item['id'] ?>">
                                        <i class="bi bi-heart-fill"></i>
                                    </button>
                                    <div class="product-img-wrapper">
                                        <img src="<?= $img_url ?>" class="card-img-top product-img" alt="<?= sanitize($item['name']) ?>">
                                    </div>
                                    <div class="product-body">
                                        <h5 class="product-title"><?= sanitize($item['name']) ?></h5>
                                        <p class="product-desc"><?= sanitize($item['description']) ?></p>
                                        <div class="product-footer">
                                            <span class="product-price">Rs. <?= number_format($item['base_price'], 0) ?></span>
                                            <button class="btn btn-primary-orange btn-customize" data-product-id="<?= $item['id'] ?>">
                                                Customize <i class="bi bi-sliders ms-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TAB 3: ACCOUNT & PASSWORD SETTINGS -->
        <?php if ($active_tab === 'settings'): ?>
            <div class="row g-4">
                <!-- Left: Profile Settings Form -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                        <h5 class="fw-bold mb-4"><i class="bi bi-person-gear text-orange me-2"></i> Update Profile details</h5>
                        
                        <form action="profile.php?tab=settings" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="prof_name" class="form-label small fw-bold text-muted">FULL NAME</label>
                                    <input type="text" name="name" id="prof_name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="prof_email" class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                                    <input type="email" name="email" id="prof_email" class="form-control" value="<?= sanitize($user['email']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="prof_phone" class="form-label small fw-bold text-muted">PHONE NUMBER</label>
                                    <input type="tel" name="phone" id="prof_phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="prof_area" class="form-label small fw-bold text-muted">DELIVERY AREA SECTOR</label>
                                    <select name="area_id" id="prof_area" class="form-select">
                                        <option value="">-- Choose Area --</option>
                                        <?php foreach ($areas as $ar): ?>
                                            <option value="<?= $ar['id'] ?>" <?= $user['area_id'] == $ar['id'] ? 'selected' : '' ?>><?= sanitize($ar['area_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="prof_address" class="form-label small fw-bold text-muted">DETAILED STREET ADDRESS</label>
                                    <textarea name="address" id="prof_address" rows="3" class="form-control"><?= sanitize($user['address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-orange w-100 py-3 mt-4 fw-bold">
                                Save Profile Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Password Change Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                        <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock text-orange me-2"></i> Update Security Credentials</h5>
                        
                        <form action="profile.php?tab=settings" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="curr_pass" class="form-label small fw-bold text-muted">CURRENT PASSWORD</label>
                                <input type="password" name="curr_pass" id="curr_pass" class="form-control" required>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="new_pass" class="form-label small fw-bold text-muted">NEW PASSWORD</label>
                                <input type="password" name="new_pass" id="new_pass" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label for="conf_pass" class="form-label small fw-bold text-muted">CONFIRM NEW PASSWORD</label>
                                <input type="password" name="conf_pass" id="conf_pass" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold">
                                Update Password Key
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- PRODUCT DETAIL CUSTOMIZATION MODAL (POPULATED VIA AJAX) -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4"></div>
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
    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Wishlist Toggler Script -->
    <script>
        document.querySelectorAll('.wishlist-heart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.getAttribute('data-product-id');
                const btnEl = this;

                const params = new URLSearchParams();
                params.append('product_id', productId);

                fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: data.message
                        });
                        
                        if (data.is_added) {
                            btnEl.classList.add('active');
                        } else {
                            btnEl.classList.remove('active');
                            // If we are currently on the wishlist tab, remove the card row dynamically!
                            const cardRow = document.getElementById('wishlist_row_' + productId);
                            if (cardRow) {
                                cardRow.remove();
                            }
                        }
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Wishlist Account Access',
                            text: data.message,
                            confirmButtonColor: '#FF6B00'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
