<?php
// admin/orders.php
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';

// 1. UPDATE ORDER STATUS (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status'] ?? '');

    $allowed_statuses = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];
    if (in_array($new_status, $allowed_statuses) && $order_id > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = "Order #{$order_id} status updated to '{$new_status}' successfully!";
    } else {
        $error = "Invalid order ID or status selection.";
    }
}

// 2. DETAILED ORDER VIEW
$view_order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($view_order_id > 0) {
    // Fetch Order details
    $stmt = $pdo->prepare("
        SELECT o.*, a.area_name 
        FROM orders o
        JOIN areas a ON o.area_id = a.id
        WHERE o.id = ?
    ");
    $stmt->execute([$view_order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $error = "Order #{$view_order_id} not found.";
        $view_order_id = 0; // Reset to list view
    } else {
        // Fetch order items
        $item_stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $item_stmt->execute([$view_order_id]);
        $items = $item_stmt->fetchAll();
    }
}

// 3. FETCH ORDERS LIST (Default View)
if ($view_order_id <= 0) {
    $filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    
    $query = "
        SELECT o.*, a.area_name 
        FROM orders o
        JOIN areas a ON o.area_id = a.id
    ";
    
    if (!empty($filter_status)) {
        $query .= " WHERE o.status = :status";
    }
    
    $query .= " ORDER BY o.id DESC";
    
    $stmt = $pdo->prepare($query);
    if (!empty($filter_status)) {
        $stmt->bindValue(':status', $filter_status);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();
}
?>

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

<!-- =========================================================================
     VIEW MODE: SINGLE ORDER DETAIL
     ========================================================================= -->
<?php if ($view_order_id > 0 && isset($order)): ?>
    <div class="mb-3">
        <a href="orders.php" class="btn btn-sm btn-outline-dark"><i class="bi bi-arrow-left"></i> Back to Orders List</a>
    </div>

    <div class="row g-4">
        <!-- Left: Receipt Card -->
        <div class="col-lg-8" id="invoicePanel">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-0">INVOICE ORDER #<?= $order['id'] ?></h4>
                        <span class="text-muted small">Placed on: <?= date('d M, Y h:i A', strtotime($order['created_at'])) ?></span>
                    </div>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none"><i class="bi bi-printer-fill me-1"></i> Print Invoice</button>
                </div>

                <!-- Customer Details -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <span class="text-muted small d-block">DELIVER TO:</span>
                        <h6 class="fw-bold mb-1"><?= sanitize($order['customer_name']) ?></h6>
                        <span class="d-block text-muted small"><?= sanitize($order['address']) ?></span>
                        <span class="d-block text-muted small"><?= sanitize($order['area_name']) ?>, Faisalabad</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-muted small d-block">CONTACT PHONE:</span>
                        <h6 class="fw-bold mb-3"><?= sanitize($order['phone']) ?></h6>
                        <span class="text-muted small d-block">PAYMENT TYPE:</span>
                        <span class="badge bg-light text-dark fw-bold border">Cash on Delivery</span>
                    </div>
                </div>

                <?php if (!empty($order['order_notes'])): ?>
                    <div class="p-3 bg-light border rounded-3 mb-4">
                        <span class="text-muted small d-block fw-bold mb-1"><i class="bi bi-chat-left-text-fill text-warning me-1"></i> Customer Order Notes:</span>
                        <span class="text-dark small">"<?= sanitize($order['order_notes']) ?>"</span>
                    </div>
                <?php endif; ?>

                <!-- Table items -->
                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Product Item</th>
                                <th scope="col" class="text-center">Qty</th>
                                <th scope="col" class="text-end">Price</th>
                                <th scope="col" class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?= sanitize($item['product_name'] ?? 'Custom Product') ?></strong>
                                        <span class="text-muted small" style="font-size: 11px;">
                                            Size: <?= sanitize($item['size_name']) ?>
                                            <?php if (!empty($item['drink_name'])): ?>
                                                | Drink: <?= sanitize($item['drink_name']) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($item['addons'])): ?>
                                                <?php 
                                                $addons_arr = json_decode($item['addons'], true);
                                                if (is_array($addons_arr)) {
                                                    echo '<br>Add-ons: ' . implode(', ', array_map(function($a) { return sanitize($a['name']) . " (+Rs." . number_format($a['price'], 0) . ")"; }, $addons_arr));
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">x<?= $item['quantity'] ?></td>
                                    <td class="text-end">Rs. <?= number_format($item['item_price'], 2) ?></td>
                                    <td class="text-end fw-bold">Rs. <?= number_format($item['line_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="text-dark fw-bold">Rs. <?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Delivery Charges (<?= sanitize($order['area_name']) ?>):</span>
                            <span class="text-dark fw-bold">Rs. <?= number_format($order['delivery_fee'], 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Total Invoice Amount:</h6>
                            <h5 class="fw-bold text-success mb-0">Rs. <?= number_format($order['grand_total'], 2) ?></h5>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right: Status Control Panel -->
        <div class="col-lg-4 d-print-none">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-activity text-orange me-2"></i> Order Status Action</h5>
                
                <div class="mb-4">
                    <span class="text-muted small d-block">CURRENT STATUS:</span>
                    <?php
                    $badge_class = 'bg-secondary';
                    if ($order['status'] === 'Pending') $badge_class = 'bg-danger';
                    elseif ($order['status'] === 'Preparing') $badge_class = 'bg-warning text-dark';
                    elseif ($order['status'] === 'Out for Delivery') $badge_class = 'bg-info text-dark';
                    elseif ($order['status'] === 'Delivered') $badge_class = 'bg-success';
                    elseif ($order['status'] === 'Cancelled') $badge_class = 'bg-dark';
                    ?>
                    <h3 class="mt-1"><span class="badge <?= $badge_class ?>"><?= $order['status'] ?></span></h3>
                </div>

                <form action="orders.php?id=<?= $order['id'] ?>" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="status_select" class="form-label small fw-bold text-muted">UPDATE ORDER STATUS</label>
                        <select name="status" id="status_select" class="form-select">
                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Preparing" <?= $order['status'] === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="Out for Delivery" <?= $order['status'] === 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                            <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-orange w-100 py-2.5 fw-bold">
                        Update Status <i class="bi bi-arrow-right-short fs-5 ms-1"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- =========================================================================
     LIST MODE: ALL ORDERS TABLE
     ========================================================================= -->
<?php else: ?>
    <!-- Status Filter tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 bg-white p-3 rounded-4 shadow-sm border-0">
        <a href="orders.php" class="btn btn-sm <?= empty($filter_status) ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
        <a href="orders.php?status=Pending" class="btn btn-sm <?= $filter_status === 'Pending' ? 'btn-danger' : 'btn-outline-danger' ?>">Pending</a>
        <a href="orders.php?status=Preparing" class="btn btn-sm <?= $filter_status === 'Preparing' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Preparing</a>
        <a href="orders.php?status=Out%20for%20Delivery" class="btn btn-sm <?= $filter_status === 'Out for Delivery' ? 'btn-info text-dark' : 'btn-outline-info' ?>">Out for Delivery</a>
        <a href="orders.php?status=Delivered" class="btn btn-sm <?= $filter_status === 'Delivered' ? 'btn-success' : 'btn-outline-success' ?>">Delivered</a>
        <a href="orders.php?status=Cancelled" class="btn btn-sm <?= $filter_status === 'Cancelled' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Cancelled</a>
    </div>

    <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
        <h5 class="fw-bold mb-4"><i class="bi bi-receipt text-orange me-2"></i> All Orders Log</h5>
        
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Customer Name</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Delivery Area</th>
                        <th scope="col">Grand Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date/Time</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No orders matching criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
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
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
