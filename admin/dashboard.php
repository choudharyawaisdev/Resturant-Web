<?php
// admin/dashboard.php
require_once __DIR__ . '/includes/header.php';

// 1. Calculate Stats
// Total Orders
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Today's Orders
$todays_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Lifetime Revenue (excludes cancelled)
$total_revenue = $pdo->query("SELECT SUM(grand_total) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
$total_revenue = $total_revenue ? floatval($total_revenue) : 0.00;

// Pending Orders
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

// 2. Fetch Recent Orders (last 8)
$recent_stmt = $pdo->query("
    SELECT o.*, a.area_name 
    FROM orders o
    JOIN areas a ON o.area_id = a.id
    ORDER BY o.id DESC 
    LIMIT 8
");
$recent_orders = $recent_stmt->fetchAll();
?>

<!-- SUMMARY METRICS CARDS -->
<div class="row g-4 mb-4">
    <!-- Total Orders -->
    <div class="col-md-3">
        <div class="card admin-card-stat bg-white text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL ORDERS</h6>
                    <h3 class="fw-bold mb-0 text-dark"><?= $total_orders ?></h3>
                </div>
                <div class="bg-light p-3 rounded-circle text-primary">
                    <i class="bi bi-cart-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Orders -->
    <div class="col-md-3">
        <div class="card admin-card-stat bg-white text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">TODAY'S ORDERS</h6>
                    <h3 class="fw-bold mb-0 text-warning"><?= $todays_orders ?></h3>
                </div>
                <div class="bg-light p-3 rounded-circle text-warning">
                    <i class="bi bi-calendar-event fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="col-md-3">
        <div class="card admin-card-stat bg-white text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL REVENUE</h6>
                    <h3 class="fw-bold mb-0 text-success">Rs. <?= number_format($total_revenue, 0) ?></h3>
                </div>
                <div class="bg-light p-3 rounded-circle text-success">
                    <i class="bi bi-cash-coin fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="col-md-3">
        <div class="card admin-card-stat bg-white text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted small fw-bold mb-1">PENDING ORDERS</h6>
                    <h3 class="fw-bold mb-0 text-danger"><?= $pending_orders ?></h3>
                </div>
                <div class="bg-light p-3 rounded-circle text-danger">
                    <i class="bi bi-bell-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ORDERS TABLE -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-orange me-2"></i> Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-primary-orange">View All Orders</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Delivery Area</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date/Time</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $ord): ?>
                        <tr>
                            <td><strong>#<?= $ord['id'] ?></strong></td>
                            <td><?= sanitize($ord['customer_name']) ?></td>
                            <td><?= sanitize($ord['phone']) ?></td>
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
                            <td class="small text-muted"><?= date('d M, Y h:i A', strtotime($ord['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="orders.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-eye-fill"></i> View & Process
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
