<?php
// profile.php
require_once __DIR__ . '/includes/functions.php';

// Guard check
if (!is_user_logged_in()) {
    redirect('login');
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
    redirect('login');
}

// Fetch Chiniot areas for settings dropdown
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
    <title>Customer Dashboard - Café-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index">
                <img src="assets/images/logo.png" alt="Café-Chinos" class="navbar-logo" style="height:56px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.25));">
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <a href="index" class="btn btn-primary-orange px-3.5 py-2 rounded-pill fw-bold text-white shadow-xs d-inline-flex align-items-center" style="font-size: 13px;">
                    <i class="bi bi-bag-fill me-1.5"></i> Order Food
                </a>
                <a href="logout" class="btn text-danger border border-danger-subtle px-3.5 py-2 rounded-pill fw-bold d-inline-flex align-items-center transition-all" style="font-size: 13px; background-color: #FEF2F2;">
                    <i class="bi bi-box-arrow-right me-1.5"></i> Sign out
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <!-- Welcome banner with stats -->
        <div class="card border-0 shadow-sm p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF9F5 100%); border: 1px solid rgba(255, 107, 0, 0.1) !important;">
            <div class="row align-items-center g-4">
                <div class="col-md-5 d-flex align-items-center">
                    <div class="avatar-circle me-3 d-flex align-items-center justify-content-center fw-bold text-white rounded-circle shadow-sm" style="width: 56px; height: 56px; background: linear-gradient(135deg, #FF6B00 0%, #FF8800 100%); font-size: 22px;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold uppercase" style="letter-spacing: 0.8px; font-size: 11px;">GUEST CUSTOMER</span>
                        <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;"><?= sanitize($user['name']) ?></h3>
                        <span class="text-muted small"><i class="bi bi-envelope me-1"></i><?= sanitize($user['email']) ?></span>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="row text-center g-3">
                        <div class="col-4">
                            <div class="p-3 rounded-4" style="background-color: #FFF4EB; border: 1px solid #FFE4D1;">
                                <span class="text-muted small d-block mb-1 fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">TOTAL ORDERS</span>
                                <h4 class="fw-extrabold mb-0" style="color: var(--primary-orange);"><?= $stats['total_orders'] ?></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-4" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                                <span class="text-muted small d-block mb-1 fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">TOTAL SPENT</span>
                                <h4 class="fw-extrabold mb-0 text-success" style="font-size: 18px;">Rs. <?= number_format($stats['total_spent'], 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-4" style="background-color: #FEF2F2; border: 1px solid #FECACA;">
                                <span class="text-muted small d-block mb-1 fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">WISHLIST</span>
                                <h4 class="fw-extrabold mb-0 text-danger"><?= $stats['total_wishlist'] ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= sanitize($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Split Layout Tabs -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm border gap-2">
            <li class="nav-item">
                <a class="nav-link px-4 py-2.5 rounded-3 fw-bold transition-all <?= $active_tab === 'orders' ? 'active text-white' : 'text-dark bg-transparent' ?>" style="<?= $active_tab === 'orders' ? 'background-color: var(--primary-orange) !important; box-shadow: 0 4px 12px rgba(255,107,0,0.25);' : '' ?>" href="profile?tab=orders">
                    <i class="bi bi-receipt me-2"></i>Orders Log
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-4 py-2.5 rounded-3 fw-bold transition-all <?= $active_tab === 'wishlist' ? 'active text-white' : 'text-dark bg-transparent' ?>" style="<?= $active_tab === 'wishlist' ? 'background-color: var(--primary-orange) !important; box-shadow: 0 4px 12px rgba(255,107,0,0.25);' : '' ?>" href="profile?tab=wishlist">
                    <i class="bi bi-heart-fill me-2"></i>Saved Wishlist
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-4 py-2.5 rounded-3 fw-bold transition-all <?= $active_tab === 'settings' ? 'active text-white' : 'text-dark bg-transparent' ?>" style="<?= $active_tab === 'settings' ? 'background-color: var(--primary-orange) !important; box-shadow: 0 4px 12px rgba(255,107,0,0.25);' : '' ?>" href="profile?tab=settings">
                    <i class="bi bi-person-gear me-2"></i>Account Settings
                </a>
            </li>
        </ul>

        <!-- TAB 1: PAST ORDERS LOG -->
        <?php if ($active_tab === 'orders'): ?>
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;"><i class="bi bi-receipt text-orange me-2" style="color: var(--primary-orange);"></i> Orders History</h5>
                    <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small"><?= count($orders) ?> Total Orders</span>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle shadow-xs" style="width: 70px; height: 70px; background-color: #FFF4EB; color: var(--primary-orange);">
                            <i class="bi bi-bag-x fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">No Orders Placed Yet</h5>
                        <p class="text-muted small mb-4" style="max-width: 380px; margin: 0 auto; line-height: 1.6;">Your order log is currently empty. Explore our delicious menu and treat yourself today!</p>
                        <a href="index" class="btn btn-primary-orange px-4 py-2.5 rounded-3 fw-bold shadow-sm text-white text-decoration-none">
                            Explore Menu <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead style="background-color: #F8FAFC; border-bottom: 2px solid #E2E8F0;">
                                <tr>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">Order ID</th>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">Date & Time</th>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">Delivery Area</th>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">Grand Total</th>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">Status</th>
                                    <th scope="col" class="py-3 px-3 small text-muted text-uppercase fw-bold text-end" style="letter-spacing: 0.5px; font-size: 11px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td class="px-3 py-3"><strong>#<?= $ord['id'] ?></strong></td>
                                        <td class="px-3 py-3 small text-muted"><?= date('d M, Y h:i A', strtotime($ord['created_at'])) ?></td>
                                        <td class="px-3 py-3 fw-medium text-dark"><?= sanitize($ord['area_name']) ?></td>
                                        <td class="px-3 py-3 fw-extrabold text-dark">Rs. <?= number_format($ord['grand_total'], 2) ?></td>
                                        <td class="px-3 py-3">
                                            <?php
                                            $badge_class = 'bg-secondary';
                                            if ($ord['status'] === 'Pending') $badge_class = 'bg-danger-subtle text-danger border border-danger-subtle';
                                            elseif ($ord['status'] === 'Preparing') $badge_class = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                            elseif ($ord['status'] === 'Out for Delivery') $badge_class = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                            elseif ($ord['status'] === 'Delivered') $badge_class = 'bg-success-subtle text-success border border-success-subtle';
                                            elseif ($ord['status'] === 'Cancelled') $badge_class = 'bg-secondary-subtle text-secondary';
                                            ?>
                                            <span class="badge px-3 py-1.5 rounded-pill fw-bold small <?= $badge_class ?>"><?= $ord['status'] ?></span>
                                        </td>
                                        <td class="px-3 py-3 text-end">
                                            <a href="order-success?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark rounded-3 px-3 py-1.5 fw-bold" style="font-size: 12px;">
                                                <i class="bi bi-eye me-1"></i> Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TAB 2: MY WISHLIST ITEMS -->
        <?php if ($active_tab === 'wishlist'): ?>
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;"><i class="bi bi-heart-fill text-danger me-2"></i> Saved Wishlist</h5>
                    <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small"><?= count($wishlist_items) ?> Favorites</span>
                </div>
                
                <?php if (empty($wishlist_items)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle shadow-xs" style="width: 70px; height: 70px; background-color: #FEF2F2; color: #DC2626;">
                            <i class="bi bi-heart fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Your Wishlist is Empty</h5>
                        <p class="text-muted small mb-4" style="max-width: 380px; margin: 0 auto; line-height: 1.6;">Save your favorite meals, burgers, and drinks here for instant 1-click ordering!</p>
                        <a href="index" class="btn btn-primary-orange px-4 py-2.5 rounded-3 fw-bold shadow-sm text-white text-decoration-none">
                            Explore Menu <i class="bi bi-arrow-right ms-1"></i>
                        </a>
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
                            <div class="col-lg-3 col-md-6 col-6" id="wishlist_row_<?= $item['id'] ?>">
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
                                            <button class="btn btn-primary-orange btn-customize px-3 py-1.5" data-product-id="<?= $item['id'] ?>" style="font-size: 12px; font-weight: 600; border-radius: 20px; white-space: nowrap;">
                                                Customize
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
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;"><i class="bi bi-person-gear me-2" style="color: var(--primary-orange);"></i> Update Profile Details</h5>
                        </div>
                        
                        <form action="profile?tab=settings" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="prof_name" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">FULL NAME</label>
                                    <input type="text" name="name" id="prof_name" class="form-control rounded-3 py-2.5" value="<?= sanitize($user['name']) ?>" required style="border-color: #CBD5E1;">
                                </div>
                                <div class="col-md-6">
                                    <label for="prof_email" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">EMAIL ADDRESS</label>
                                    <input type="email" name="email" id="prof_email" class="form-control rounded-3 py-2.5" value="<?= sanitize($user['email']) ?>" required style="border-color: #CBD5E1;">
                                </div>

                                <div class="col-md-6">
                                    <label for="prof_phone" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">PHONE NUMBER</label>
                                    <input type="tel" name="phone" id="prof_phone" class="form-control rounded-3 py-2.5" value="<?= sanitize($user['phone'] ?? '') ?>" style="border-color: #CBD5E1;">
                                </div>
                                <div class="col-md-6">
                                    <label for="prof_area" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">DELIVERY AREA SECTOR</label>
                                    <select name="area_id" id="prof_area" class="form-select rounded-3 py-2.5" style="border-color: #CBD5E1;">
                                        <option value="">-- Choose Area --</option>
                                        <?php foreach ($areas as $ar): ?>
                                            <option value="<?= $ar['id'] ?>" <?= $user['area_id'] == $ar['id'] ? 'selected' : '' ?>><?= sanitize($ar['area_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="prof_address" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">DETAILED STREET ADDRESS</label>
                                    <textarea name="address" id="prof_address" rows="3" class="form-control rounded-3" style="border-color: #CBD5E1;"><?= sanitize($user['address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-orange w-100 py-3 mt-4 fw-bold rounded-3 shadow-sm text-white">
                                Save Profile Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Password Change Form -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                        <div class="border-bottom pb-3 mb-4">
                            <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;"><i class="bi bi-shield-lock me-2" style="color: var(--primary-orange);"></i> Update Security Credentials</h5>
                        </div>
                        
                        <form action="profile?tab=settings" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="curr_pass" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">CURRENT PASSWORD</label>
                                <input type="password" name="curr_pass" id="curr_pass" class="form-control rounded-3 py-2.5" required style="border-color: #CBD5E1;">
                            </div>

                            <hr class="my-3 border-light">

                            <div class="mb-3">
                                <label for="new_pass" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">NEW PASSWORD</label>
                                <input type="password" name="new_pass" id="new_pass" class="form-control rounded-3 py-2.5" required style="border-color: #CBD5E1;">
                            </div>

                            <div class="mb-4">
                                <label for="conf_pass" class="form-label small fw-bold text-muted" style="letter-spacing: 0.5px; font-size: 11px;">CONFIRM NEW PASSWORD</label>
                                <input type="password" name="conf_pass" id="conf_pass" class="form-control rounded-3 py-2.5" required style="border-color: #CBD5E1;">
                            </div>

                            <button type="submit" class="btn btn-primary-orange w-100 py-3 fw-bold rounded-3 shadow-sm text-white">
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

                fetch('api/wishlist', {
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

