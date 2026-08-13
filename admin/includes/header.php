<?php
// admin/includes/header.php
require_once __DIR__ . '/auth.php';
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Café-Chinos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

    <div class="admin-wrapper">
        
        <!-- SIDEBAR (Clean White Theme with Orange Accent) -->
        <aside class="admin-sidebar">
            <a href="dashboard" class="admin-brand">
                <img src="../assets/images/logo.png" alt="Logo" style="height:46px; width:auto; object-fit:contain;">
                <div>
                    <span class="admin-brand-badge">Admin Panel</span>
                </div>
            </a>

<?php
$current_tab = $_GET['tab'] ?? 'products';
$is_products = str_contains($current_page, 'products');
?>
            <ul class="admin-nav mb-auto">
                <li class="admin-nav-item">
                    <a href="dashboard" class="admin-nav-link <?= str_contains($current_page, 'dashboard') ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="categories" class="admin-nav-link <?= str_contains($current_page, 'categories') ? 'active' : '' ?>">
                        <i class="bi bi-tags-fill"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="products?tab=products" class="admin-nav-link <?= ($is_products && $current_tab === 'products') ? 'active' : '' ?>">
                        <i class="bi bi-egg-fried"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="products?tab=addons" class="admin-nav-link <?= ($is_products && $current_tab === 'addons') ? 'active' : '' ?>">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Add-ons</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="products?tab=drinks" class="admin-nav-link <?= ($is_products && $current_tab === 'drinks') ? 'active' : '' ?>">
                        <i class="bi bi-cup-straw"></i>
                        <span>Drinks</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="orders" class="admin-nav-link <?= str_contains($current_page, 'orders') ? 'active' : '' ?>">
                        <i class="bi bi-bag-check-fill"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="areas" class="admin-nav-link <?= str_contains($current_page, 'areas') ? 'active' : '' ?>">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Areas & Delivery</span>
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="settings" class="admin-nav-link <?= str_contains($current_page, 'settings') ? 'active' : '' ?>">
                        <i class="bi bi-gear-fill"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>

            <!-- USER PROFILE AT BOTTOM -->
            <div class="admin-user-card">
                <div class="dropdown">
                    <a href="#" class="admin-user-box dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="admin-avatar">
                            <?= strtoupper(substr(sanitize($_SESSION['admin_username'] ?? 'A'), 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden me-2">
                            <div class="fw-bold text-truncate" style="font-size: 13px; color: #1E293B;"><?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></div>
                            <div class="text-muted" style="font-size: 11px;">Administrator</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 text-small" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item py-2" href="settings"><i class="bi bi-gear me-2 text-muted"></i> Settings</a></li>
                        <li><a class="dropdown-item py-2" href="../index" target="_blank"><i class="bi bi-globe me-2 text-muted"></i> Customer Site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-semibold" href="logout"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- CONTENT AREA -->
        <main class="admin-content">
            <!-- Header top bar -->
            <div class="admin-top-bar d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-0">
                        <?php
                        if (str_contains($current_page, 'dashboard')) echo 'Dashboard Overview';
                        elseif (str_contains($current_page, 'categories')) echo 'Category Management';
                        elseif ($is_products && $current_tab === 'addons') echo 'Global Add-ons Management';
                        elseif ($is_products && $current_tab === 'drinks') echo 'Global Drinks Management';
                        elseif ($is_products) echo 'Product Catalog Management';
                        elseif (str_contains($current_page, 'orders')) echo 'Orders Processing';
                        elseif (str_contains($current_page, 'areas')) echo 'Delivery Fees & Areas';
                        elseif (str_contains($current_page, 'settings')) echo 'Restaurant Settings';
                        else echo 'Management';
                        ?>
                    </h1>
                    <p class="text-muted small mb-0 mt-1">Welcome back, <strong><?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></strong>!</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="../index" target="_blank" class="btn btn-outline-orange btn-sm rounded-pill px-3 py-2">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Visit Customer Site
                    </a>
                </div>
            </div>

