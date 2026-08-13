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
    <style>
        .admin-sidebar {
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="admin-wrapper">
        
        <!-- SIDEBAR -->
        <div class="admin-sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
            <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <img src="../assets/images/logo.png" alt="Café-Chinos" style="height:45px; width:auto; object-fit:contain; filter: drop-shadow(0 1px 3px rgba(255,255,255,0.2));" class="me-2">
                <span class="fs-5 fw-bold">Café-Chinos Admin</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mb-1">
                    <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="mb-1">
                    <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                </li>
                <li class="mb-1">
                    <a href="products.php" class="nav-link <?= $current_page == 'products.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-egg-fried me-2"></i> Products
                    </a>
                </li>
                <li class="mb-1">
                    <a href="orders.php" class="nav-link <?= $current_page == 'orders.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-receipt me-2"></i> Orders
                    </a>
                </li>
                <li class="mb-1">
                    <a href="areas.php" class="nav-link <?= $current_page == 'areas.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-geo-alt me-2"></i> Areas & Delivery
                    </a>
                </li>
                <li class="mb-1">
                    <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 me-2"></i>
                    <strong><?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear-fill me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Sign out</a></li>
                </ul>
            </div>
        </div>

        <!-- CONTENT AREA -->
        <div class="admin-content">
            <!-- Header top bar -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                <h1 class="h2 text-dark fw-bold">
                    <?php
                    switch($current_page) {
                        case 'dashboard.php': echo 'Dashboard Overview'; break;
                        case 'categories.php': echo 'Category Management'; break;
                        case 'products.php': echo 'Product Management'; break;
                        case 'orders.php': echo 'Orders Processing'; break;
                        case 'areas.php': echo 'Delivery Fees & Areas'; break;
                        case 'settings.php': echo 'Restaurant Settings'; break;
                        default: echo 'Management';
                    }
                    ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-eye-fill me-1"></i> Visit Customer Site</a>
                </div>
            </div>
