<?php
// admin/areas.php
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';

$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_fee = 0.00;
$edit_status = 'active';

// 1. DELETE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    
    // Check if any orders are mapped to this area first
    $order_check = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE area_id = ?");
    $order_check->execute([$del_id]);
    $mapped_orders = $order_check->fetchColumn();

    if ($mapped_orders > 0) {
        $error = "Cannot delete area. It is mapped to {$mapped_orders} existing orders!";
    } else {
        $del_stmt = $pdo->prepare("DELETE FROM areas WHERE id = ?");
        $del_stmt->execute([$del_id]);
        $success = "Delivery area deleted successfully.";
    }
}

// 2. TOGGLE STATUS ACTION
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $toggle_id = intval($_GET['id']);
    $curr_status = $pdo->query("SELECT status FROM areas WHERE id = $toggle_id")->fetchColumn();
    $new_status = $curr_status === 'active' ? 'inactive' : 'active';
    
    $up_stmt = $pdo->prepare("UPDATE areas SET status = ? WHERE id = ?");
    $up_stmt->execute([$new_status, $toggle_id]);
    $success = "Area status updated successfully.";
}

// 3. EDIT TRIGGER (Load data into form)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM areas WHERE id = ?");
    $stmt->execute([$edit_id]);
    $area = $stmt->fetch();
    
    if ($area) {
        $edit_mode = true;
        $edit_name = $area['area_name'];
        $edit_fee = $area['delivery_fee'];
        $edit_status = $area['status'];
    }
}

// 4. POST FORM SUBMISSION (ADD or EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area_name = sanitize($_POST['area_name'] ?? '');
    $delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $posted_edit_id = intval($_POST['edit_id'] ?? 0);

    if (empty($area_name)) {
        $error = "Area name is required.";
    } else {
        if ($posted_edit_id > 0) {
            // Edit Update
            $stmt = $pdo->prepare("UPDATE areas SET area_name = ?, delivery_fee = ?, status = ? WHERE id = ?");
            $stmt->execute([$area_name, $delivery_fee, $status, $posted_edit_id]);
            $success = "Delivery area updated successfully!";
            $edit_mode = false;
        } else {
            // New Insert
            $stmt = $pdo->prepare("INSERT INTO areas (city, area_name, delivery_fee, status) VALUES ('Chiniot', ?, ?, ?)");
            $stmt->execute([$area_name, $delivery_fee, $status]);
            $success = "New delivery area added successfully!";
        }
    }
}

// Fetch all areas for display
$all_areas = $pdo->query("SELECT * FROM areas ORDER BY area_name ASC")->fetchAll();
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

<div class="row g-4">
    <!-- Left: Form Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4">
                <i class="bi <?= $edit_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-orange me-2"></i>
                <?= $edit_mode ? 'Edit Area Details' : 'Create Delivery Area' ?>
            </h5>
            
            <form action="areas.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">CITY</label>
                    <input type="text" class="form-control bg-light" value="Chiniot" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="area_name" class="form-label small fw-bold text-muted">AREA SECTOR NAME</label>
                    <input type="text" name="area_name" id="area_name" class="form-control" placeholder="e.g. Madina Town" value="<?= sanitize($edit_name) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="delivery_fee" class="form-label small fw-bold text-muted">DELIVERY FEE CHARGED (Rs.)</label>
                    <input type="number" name="delivery_fee" id="delivery_fee" class="form-control" placeholder="e.g. 100" value="<?= $edit_fee ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">STATUS</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $edit_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $edit_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                        <?= $edit_mode ? 'Save Changes' : 'Add Area' ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="areas.php" class="btn btn-outline-secondary w-100 py-2.5">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Table Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4"><i class="bi bi-geo-alt-fill text-orange me-2"></i> Delivery Areas Matrix</h5>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Area Name</th>
                            <th scope="col">Delivery Fee</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_areas)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No delivery areas configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_areas as $ar): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= sanitize($ar['area_name']) ?></strong></td>
                                    <td class="fw-bold">Rs. <?= number_format($ar['delivery_fee'], 2) ?></td>
                                    <td>
                                        <a href="areas.php?action=toggle&id=<?= $ar['id'] ?>" class="badge text-decoration-none <?= $ar['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $ar['status'] ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="areas.php?action=edit&id=<?= $ar['id'] ?>" class="btn btn-sm btn-outline-dark me-1" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-danger btn-delete-area" data-id="<?= $ar['id'] ?>" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Delete Confirmation
    document.querySelectorAll('.btn-delete-area').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: 'Delete Delivery Area?',
                text: "Deleting this area will prevent customers in this location from placing orders!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B00',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `areas.php?action=delete&id=${id}`;
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
