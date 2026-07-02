<?php
// admin/categories.php
require_once __DIR__ . '/includes/header.php';

// Create uploads folder if not exists
$upload_dir = dirname(__DIR__) . '/assets/images/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$error = '';
$success = '';

// Edit pre-fill data holder
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_display_order = 0;
$edit_status = 'active';
$edit_image = '';

// 1. DELETE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Fetch image filename first to delete it
    $img_stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
    $img_stmt->execute([$delete_id]);
    $img_name = $img_stmt->fetchColumn();

    if ($img_name && file_exists($upload_dir . $img_name)) {
        unlink($upload_dir . $img_name);
    }

    $del_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $del_stmt->execute([$delete_id]);
    $success = 'Category deleted successfully!';
}

// 2. TOGGLE STATUS ACTION
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $toggle_id = intval($_GET['id']);
    $status_stmt = $pdo->prepare("SELECT status FROM categories WHERE id = ?");
    $status_stmt->execute([$toggle_id]);
    $curr_status = $status_stmt->fetchColumn();
    $new_status = $curr_status === 'active' ? 'inactive' : 'active';
    
    $up_stmt = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
    $up_stmt->execute([$new_status, $toggle_id]);
    $success = 'Category status updated!';
}

// 3. EDIT TRIGGER (Load data into form)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $load_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $load_stmt->execute([$edit_id]);
    $cat_to_edit = $load_stmt->fetch();
    
    if ($cat_to_edit) {
        $edit_mode = true;
        $edit_name = $cat_to_edit['name'];
        $edit_display_order = $cat_to_edit['display_order'];
        $edit_status = $cat_to_edit['status'];
        $edit_image = $cat_to_edit['image'];
    }
}

// 4. POST FORM SUBMISSION (ADD or EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $posted_edit_id = intval($_POST['edit_id'] ?? 0);
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        // Image Upload process
        $image_filename = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $image_filename = time() . '_' . uniqid() . '.' . $file_ext;
                move_uploaded_file($file_tmp, $upload_dir . $image_filename);
                
                // If editing and has previous image, delete the old one
                if ($posted_edit_id > 0) {
                    $old_stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
                    $old_stmt->execute([$posted_edit_id]);
                    $old_img = $old_stmt->fetchColumn();
                    if ($old_img && file_exists($upload_dir . $old_img)) {
                        unlink($upload_dir . $old_img);
                    }
                }
            } else {
                $error = 'Invalid image type. Allowed: JPG, PNG, WEBP.';
            }
        }

        if (empty($error)) {
            if ($posted_edit_id > 0) {
                // UPDATE category
                if (!empty($image_filename)) {
                    $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, display_order = ?, status = ?, image = ? WHERE id = ?");
                    $update_stmt->execute([$name, $display_order, $status, $image_filename, $posted_edit_id]);
                } else {
                    $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, display_order = ?, status = ? WHERE id = ?");
                    $update_stmt->execute([$name, $display_order, $status, $posted_edit_id]);
                }
                $success = 'Category updated successfully!';
                $edit_mode = false; // Reset
            } else {
                // INSERT category
                $insert_stmt = $pdo->prepare("INSERT INTO categories (name, display_order, status, image) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$name, $display_order, $status, $image_filename]);
                $success = 'Category added successfully!';
            }
        }
    }
}

// Fetch all categories for display
$all_cats = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
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
    <!-- Left column: Add/Edit Category Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4">
                <i class="bi <?= $edit_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-orange me-2"></i>
                <?= $edit_mode ? 'Edit Category' : 'Create Category' ?>
            </h5>
            
            <form action="categories.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label small fw-bold text-muted">CATEGORY NAME</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Burgers" value="<?= sanitize($edit_name) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="display_order" class="form-label small fw-bold text-muted">DISPLAY ORDER</label>
                    <input type="number" name="display_order" id="display_order" class="form-control" value="<?= intval($edit_display_order) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">STATUS</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $edit_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $edit_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="image" class="form-label small fw-bold text-muted">CATEGORY COVER IMAGE</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                    <?php if ($edit_mode && !empty($edit_image)): ?>
                        <div class="mt-2">
                            <span class="small text-muted d-block">Current Cover:</span>
                            <img src="../assets/images/uploads/<?= $edit_image ?>" class="img-thumbnail mt-1" style="max-height: 80px;" onerror="this.src='https://placehold.co/100x100/FFF8F0/FF6B00?text=Food'">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                        <?= $edit_mode ? 'Save Changes' : 'Add Category' ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="categories.php" class="btn btn-outline-secondary w-100 py-2.5">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right column: Categories Table List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4"><i class="bi bi-list-task text-orange me-2"></i> Categories List</h5>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Image</th>
                            <th scope="col">Name</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_cats)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No categories added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_cats as $cat): ?>
                                <tr>
                                    <td><strong><?= $cat['display_order'] ?></strong></td>
                                    <td>
                                        <?php if (!empty($cat['image']) && file_exists($upload_dir . $cat['image'])): ?>
                                            <img src="../assets/images/uploads/<?= $cat['image'] ?>" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="https://placehold.co/50x50/FFF8F0/FF6B00?text=Food" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="text-dark"><?= sanitize($cat['name']) ?></strong></td>
                                    <td>
                                        <a href="categories.php?action=toggle&id=<?= $cat['id'] ?>" class="badge text-decoration-none <?= $cat['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $cat['status'] ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-dark me-1" title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-danger btn-delete-cat" data-id="<?= $cat['id'] ?>" title="Delete">
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
    // SweetAlert2 Confirmation for deleting category
    document.querySelectorAll('.btn-delete-cat').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "Deleting this category will also remove all products inside it!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B00',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `categories.php?action=delete&id=${id}`;
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
