<?php
// admin/products.php
require_once __DIR__ . '/includes/header.php';

$upload_dir = dirname(__DIR__) . '/assets/images/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$error = '';
$success = '';
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'products';

// -------------------------------------------------------------------------
// DATABASE ACTION HANDLING (PRODUCTS, ADDONS, DRINKS)
// -------------------------------------------------------------------------

// --- ADDONS ACTIONS ---
if ($active_tab === 'addons') {
    // Addon Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete_addon' && isset($_GET['id'])) {
        $del_id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM addons WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = 'Add-on deleted successfully!';
    }
    // Addon Post (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'addon') {
        $addon_name = sanitize($_POST['name'] ?? '');
        $addon_price = floatval($_POST['price'] ?? 0);
        $addon_status = sanitize($_POST['status'] ?? 'active');
        $addon_id = intval($_POST['addon_id'] ?? 0);

        if (empty($addon_name)) {
            $error = 'Add-on name is required.';
        } else {
            if ($addon_id > 0) {
                $stmt = $pdo->prepare("UPDATE addons SET name = ?, price = ?, status = ? WHERE id = ?");
                $stmt->execute([$addon_name, $addon_price, $addon_status, $addon_id]);
                $success = 'Add-on updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO addons (name, price, status) VALUES (?, ?, ?)");
                $stmt->execute([$addon_name, $addon_price, $addon_status]);
                $success = 'Add-on added successfully!';
            }
        }
    }
}

// --- DRINKS ACTIONS ---
if ($active_tab === 'drinks') {
    // Drink Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete_drink' && isset($_GET['id'])) {
        $del_id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM drinks WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = 'Drink deleted successfully!';
    }
    // Drink Post (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'drink') {
        $drink_name = sanitize($_POST['name'] ?? '');
        $drink_price = floatval($_POST['price'] ?? 0);
        $drink_status = sanitize($_POST['status'] ?? 'active');
        $drink_id = intval($_POST['drink_id'] ?? 0);

        if (empty($drink_name)) {
            $error = 'Drink name is required.';
        } else {
            if ($drink_id > 0) {
                $stmt = $pdo->prepare("UPDATE drinks SET name = ?, price = ?, status = ? WHERE id = ?");
                $stmt->execute([$drink_name, $drink_price, $drink_status, $drink_id]);
                $success = 'Drink updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO drinks (name, price, status) VALUES (?, ?, ?)");
                $stmt->execute([$drink_name, $drink_price, $drink_status]);
                $success = 'Drink added successfully!';
            }
        }
    }
}

// --- PRODUCTS ACTIONS ---
if ($active_tab === 'products') {
    // Product Delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete_product' && isset($_GET['id'])) {
        $del_id = intval($_GET['id']);
        $img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $img_stmt->execute([$del_id]);
        $img_name = $img_stmt->fetchColumn();

        if ($img_name && file_exists($upload_dir . $img_name)) {
            unlink($upload_dir . $img_name);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$del_id]);
        $success = 'Product deleted successfully!';
    }
    
    // Product Post (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'product') {
        $prod_id = intval($_POST['product_id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $base_price = floatval($_POST['base_price'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        
        $posted_addons = $_POST['addons'] ?? [];
        $posted_drinks = $_POST['drinks'] ?? [];
        
        $size_names = $_POST['size_name'] ?? [];
        $size_prices = $_POST['size_price'] ?? [];

        if (empty($name) || $category_id <= 0) {
            $error = 'Product name and category are required.';
        } else {
            // Upload Image
            $image_filename = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed)) {
                    $image_filename = time() . '_' . uniqid() . '.' . $file_ext;
                    move_uploaded_file($file_tmp, $upload_dir . $image_filename);
                    
                    if ($prod_id > 0) {
                        $old_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                        $old_stmt->execute([$prod_id]);
                        $old_img = $old_stmt->fetchColumn();
                        if ($old_img && file_exists($upload_dir . $old_img)) {
                            unlink($upload_dir . $old_img);
                        }
                    }
                } else {
                    $error = 'Invalid product image type.';
                }
            }

            if (empty($error)) {
                $pdo->beginTransaction();
                try {
                    if ($prod_id > 0) {
                        // Update
                        if (!empty($image_filename)) {
                            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, image = ?, base_price = ?, status = ? WHERE id = ?");
                            $stmt->execute([$category_id, $name, $description, $image_filename, $base_price, $status, $prod_id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, base_price = ?, status = ? WHERE id = ?");
                            $stmt->execute([$category_id, $name, $description, $base_price, $status, $prod_id]);
                        }
                        $product_id = $prod_id;
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, image, base_price, status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$category_id, $name, $description, $image_filename, $base_price, $status]);
                        $product_id = $pdo->lastInsertId();
                    }

                    // 1. Sync Sizes
                    $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?")->execute([$product_id]);
                    if (!empty($size_names)) {
                        $size_stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_name, price) VALUES (?, ?, ?)");
                        foreach ($size_names as $idx => $s_name) {
                            $s_name = sanitize($s_name);
                            $s_price = floatval($size_prices[$idx] ?? 0);
                            if (!empty($s_name)) {
                                $size_stmt->execute([$product_id, $s_name, $s_price]);
                            }
                        }
                    }

                    // 2. Sync Addons
                    $pdo->prepare("DELETE FROM product_addons WHERE product_id = ?")->execute([$product_id]);
                    if (!empty($posted_addons)) {
                        $add_map_stmt = $pdo->prepare("INSERT INTO product_addons (product_id, addon_id) VALUES (?, ?)");
                        foreach ($posted_addons as $add_id) {
                            $add_map_stmt->execute([$product_id, intval($add_id)]);
                        }
                    }

                    // 3. Sync Drinks
                    $pdo->prepare("DELETE FROM product_drinks WHERE product_id = ?")->execute([$product_id]);
                    if (!empty($posted_drinks)) {
                        $drk_map_stmt = $pdo->prepare("INSERT INTO product_drinks (product_id, drink_id) VALUES (?, ?)");
                        foreach ($posted_drinks as $drk_id) {
                            $drk_map_stmt->execute([$product_id, intval($drk_id)]);
                        }
                    }

                    $pdo->commit();
                    $success = 'Product catalog updated successfully!';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error storing product details: ' . $e->getMessage();
                }
            }
        }
    }
}

// -------------------------------------------------------------------------
// DATA FETCHES FOR EDIT MODE / UI RENDERING
// -------------------------------------------------------------------------

// Edit prefill states
$edit_prod_mode = false;
$prod_edit_id = 0;
$prod_name = '';
$prod_cat_id = 0;
$prod_desc = '';
$prod_base_price = '';
$prod_status = 'active';
$prod_image = '';
$prod_sizes = [];
$prod_addons = [];
$prod_drinks = [];

if ($active_tab === 'products' && isset($_GET['action']) && $_GET['action'] === 'edit_product' && isset($_GET['id'])) {
    $prod_edit_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$prod_edit_id]);
    $prod = $stmt->fetch();
    
    if ($prod) {
        $edit_prod_mode = true;
        $prod_name = $prod['name'];
        $prod_cat_id = $prod['category_id'];
        $prod_desc = $prod['description'];
        $prod_base_price = $prod['base_price'];
        $prod_status = $prod['status'];
        $prod_image = $prod['image'];
        
        // Fetch sizes
        $prod_sizes = $pdo->query("SELECT * FROM product_sizes WHERE product_id = $prod_edit_id")->fetchAll();
        // Fetch addons mapping
        $prod_addons = $pdo->query("SELECT addon_id FROM product_addons WHERE product_id = $prod_edit_id")->fetchAll(PDO::FETCH_COLUMN);
        // Fetch drinks mapping
        $prod_drinks = $pdo->query("SELECT drink_id FROM product_drinks WHERE product_id = $prod_edit_id")->fetchAll(PDO::FETCH_COLUMN);
    }
}

$edit_addon_mode = false;
$addon_edit_id = 0;
$addon_edit_name = '';
$addon_edit_price = 0.00;
$addon_edit_status = 'active';

if ($active_tab === 'addons' && isset($_GET['action']) && $_GET['action'] === 'edit_addon' && isset($_GET['id'])) {
    $addon_edit_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM addons WHERE id = ?");
    $stmt->execute([$addon_edit_id]);
    $addon = $stmt->fetch();
    if ($addon) {
        $edit_addon_mode = true;
        $addon_edit_name = $addon['name'];
        $addon_edit_price = $addon['price'];
        $addon_edit_status = $addon['status'];
    }
}

$edit_drink_mode = false;
$drink_edit_id = 0;
$drink_edit_name = '';
$drink_edit_price = 0.00;
$drink_edit_status = 'active';

if ($active_tab === 'drinks' && isset($_GET['action']) && $_GET['action'] === 'edit_drink' && isset($_GET['id'])) {
    $drink_edit_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM drinks WHERE id = ?");
    $stmt->execute([$drink_edit_id]);
    $drink = $stmt->fetch();
    if ($drink) {
        $edit_drink_mode = true;
        $drink_edit_name = $drink['name'];
        $drink_edit_price = $drink['price'];
        $drink_edit_status = $drink['status'];
    }
}

// Global list fetches
$all_categories = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
$all_addons = $pdo->query("SELECT * FROM addons ORDER BY name ASC")->fetchAll();
$all_drinks = $pdo->query("SELECT * FROM drinks ORDER BY name ASC")->fetchAll();

$all_products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();
?>

<!-- Tab toggling navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'products' ? 'active fw-bold' : '' ?>" href="products?tab=products"><i class="bi bi-egg-fried text-orange me-1"></i> Products Menu</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'addons' ? 'active fw-bold' : '' ?>" href="products?tab=addons"><i class="bi bi-plus-circle text-orange me-1"></i> Global Add-ons</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $active_tab === 'drinks' ? 'active fw-bold' : '' ?>" href="products?tab=drinks"><i class="bi bi-cup-straw text-orange me-1"></i> Global Drinks</a>
    </li>
</ul>

<!-- ALERTS (SweetAlert + Bootstrap) -->
<?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?= sanitize($success) ?>',
                confirmButtonColor: '#FF6B00',
                timer: 3000
            });
        });
    </script>
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= sanitize($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Operation Failed',
                text: '<?= sanitize($error) ?>',
                confirmButtonColor: '#FF6B00'
            });
        });
    </script>
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- TAB 1: PRODUCTS CATALOG -->
<?php if ($active_tab === 'products'): ?>
    <div class="row g-4">
        <!-- Add/Edit product form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4">
                    <i class="bi <?= $edit_prod_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-orange me-2"></i>
                    <?= $edit_prod_mode ? 'Edit Product' : 'Create Product' ?>
                </h5>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_type" value="product">
                    <input type="hidden" name="product_id" value="<?= $prod_edit_id ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label small fw-bold text-muted">PRODUCT NAME</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Classic Beef Burger" value="<?= sanitize($prod_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label small fw-bold text-muted">CATEGORY</label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($all_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $prod_cat_id == $cat['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="base_price" class="form-label small fw-bold text-muted">BASE STARTING PRICE (Rs.)</label>
                            <input type="number" name="base_price" id="base_price" class="form-control" value="<?= sanitize($prod_base_price) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label small fw-bold text-muted">STATUS</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active" <?= $prod_status === 'active' ? 'selected' : '' ?>>Active (In Stock)</option>
                                <option value="inactive" <?= $prod_status === 'inactive' ? 'selected' : '' ?>>Inactive (Out of Stock)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label small fw-bold text-muted">SHORT DESCRIPTION</label>
                            <textarea name="description" id="description" rows="2" class="form-control" placeholder="e.g. Battered crispy breast fillet topped with cheese slice..."><?= sanitize($prod_desc) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="image" class="form-label small fw-bold text-muted">PRODUCT PHOTO</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*">
                            <?php if ($edit_prod_mode && !empty($prod_image)): ?>
                                <div class="mt-2">
                                    <span class="small text-muted d-block">Current Cover:</span>
                                    <img src="../assets/images/uploads/<?= $prod_image ?>" class="img-thumbnail mt-1" style="max-height: 80px;" onerror="this.src='https://placehold.co/100x100/FFF8F0/FF6B00?text=Food'">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- CUSTOM SIZES MANAGER -->
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-aspect-ratio text-orange me-1"></i> Custom Sizes</h6>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" id="btnAddSizeRow">+ Add Size Row</button>
                            </div>
                            
                            <div id="sizesContainer">
                                <?php if ($edit_prod_mode && !empty($prod_sizes)): ?>
                                    <?php foreach ($prod_sizes as $s_info): ?>
                                        <div class="row g-2 mb-2 align-items-center size-row">
                                            <div class="col-6">
                                                <input type="text" name="size_name[]" class="form-control form-control-sm" placeholder="e.g. Medium 10\"" value="<?= sanitize($s_info['size_name']) ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" name="size_price[]" class="form-control form-control-sm" placeholder="Price Offset" value="<?= $s_info['price'] ?>">
                                            </div>
                                            <div class="col-2 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger btnRemoveSizeRow"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default empty size row -->
                                    <div class="row g-2 mb-2 align-items-center size-row">
                                        <div class="col-6">
                                            <input type="text" name="size_name[]" class="form-control form-control-sm" placeholder="e.g. Regular" value="Regular">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" name="size_price[]" class="form-control form-control-sm" placeholder="Price Offset" value="0">
                                        </div>
                                        <div class="col-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveSizeRow" disabled><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ADDONS SELECTION -->
                        <div class="col-12 border-top pt-3">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-plus-circle text-orange me-1"></i> Map Add-ons</h6>
                            <div class="row g-2" style="max-height: 120px; overflow-y: auto;">
                                <?php foreach ($all_addons as $add): ?>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="addons[]" id="chk_addon_<?= $add['id'] ?>" value="<?= $add['id'] ?>" <?= in_array($add['id'], $prod_addons) ? 'checked' : '' ?>>
                                            <label class="form-check-label small text-dark" for="chk_addon_<?= $add['id'] ?>">
                                                <?= sanitize($add['name']) ?> (+Rs. <?= number_format($add['price'], 0) ?>)
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- DRINKS SELECTION -->
                        <div class="col-12 border-top pt-3">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-cup-straw text-orange me-1"></i> Map Drinks Options</h6>
                            <div class="row g-2" style="max-height: 120px; overflow-y: auto;">
                                <?php foreach ($all_drinks as $drk): ?>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="drinks[]" id="chk_drink_<?= $drk['id'] ?>" value="<?= $drk['id'] ?>" <?= in_array($drk['id'], $prod_drinks) ? 'checked' : '' ?>>
                                            <label class="form-check-label small text-dark" for="chk_drink_<?= $drk['id'] ?>">
                                                <?= sanitize($drk['name']) ?> (+Rs. <?= number_format($drk['price'], 0) ?>)
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4 pt-2">
                        <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                            <?= $edit_prod_mode ? 'Save Product Changes' : 'Create Menu Item' ?>
                        </button>
                        <?php if ($edit_prod_mode): ?>
                            <a href="products.php?tab=products" class="btn btn-outline-secondary w-100 py-2.5">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products table list -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4"><i class="bi bi-list-task text-orange me-2"></i> Active Menu Items</h5>
                
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Cover</th>
                                <th scope="col">Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Price</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No products created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_products as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($p['image']) && file_exists($upload_dir . $p['image'])): ?>
                                                <img src="../assets/images/uploads/<?= $p['image'] ?>" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <img src="https://placehold.co/50x50/FFF8F0/FF6B00?text=Food" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block"><?= sanitize($p['name']) ?></strong>
                                            <span class="text-muted small" style="font-size: 10px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; max-width: 150px;"><?= sanitize($p['description']) ?></span>
                                        </td>
                                        <td><?= sanitize($p['category_name']) ?></td>
                                        <td class="fw-bold">Rs. <?= number_format($p['base_price'], 0) ?></td>
                                        <td>
                                            <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $p['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="products.php?tab=products&action=edit_product&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-dark me-1" title="Edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger btn-delete-product" data-id="<?= $p['id'] ?>" title="Delete">
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
<?php endif; ?>

<!-- TAB 2: GLOBAL ADDONS -->
<?php if ($active_tab === 'addons'): ?>
    <div class="row g-4">
        <!-- Add/Edit Addon form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4">
                    <i class="bi <?= $edit_addon_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-orange me-2"></i>
                    <?= $edit_addon_mode ? 'Edit Add-on' : 'Create Add-on' ?>
                </h5>
                
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="addon">
                    <input type="hidden" name="addon_id" value="<?= $addon_edit_id ?>">
                    
                    <div class="mb-3">
                        <label for="addon_name" class="form-label small fw-bold text-muted">ADD-ON TOPPING NAME</label>
                        <input type="text" name="name" id="addon_name" class="form-control" placeholder="e.g. Extra Cheese Slice" value="<?= sanitize($addon_edit_name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="addon_price" class="form-label small fw-bold text-muted">PRICE (Rs.)</label>
                        <input type="number" name="price" id="addon_price" class="form-control" placeholder="e.g. 70" value="<?= $addon_edit_price ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">STATUS</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $addon_edit_status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $addon_edit_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                            <?= $edit_addon_mode ? 'Save Changes' : 'Add Toppings' ?>
                        </button>
                        <?php if ($edit_addon_mode): ?>
                            <a href="products.php?tab=addons" class="btn btn-outline-secondary w-100 py-2.5">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Addons list -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4"><i class="bi bi-list-task text-orange me-2"></i> Global Add-ons List</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_addons)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No global addons created.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_addons as $add): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= sanitize($add['name']) ?></strong></td>
                                        <td class="fw-bold">Rs. <?= number_format($add['price'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $add['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $add['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="products.php?tab=addons&action=edit_addon&id=<?= $add['id'] ?>" class="btn btn-sm btn-outline-dark me-1" title="Edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger btn-delete-addon" data-id="<?= $add['id'] ?>" title="Delete">
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
<?php endif; ?>

<!-- TAB 3: GLOBAL DRINKS -->
<?php if ($active_tab === 'drinks'): ?>
    <div class="row g-4">
        <!-- Add/Edit Drink form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4">
                    <i class="bi <?= $edit_drink_mode ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-orange me-2"></i>
                    <?= $edit_drink_mode ? 'Edit Drink' : 'Create Drink' ?>
                </h5>
                
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="drink">
                    <input type="hidden" name="drink_id" value="<?= $drink_edit_id ?>">
                    
                    <div class="mb-3">
                        <label for="drink_name" class="form-label small fw-bold text-muted">DRINK BEVERAGE NAME</label>
                        <input type="text" name="name" id="drink_name" class="form-control" placeholder="e.g. Coca-Cola" value="<?= sanitize($drink_edit_name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="drink_price" class="form-label small fw-bold text-muted">EXTRA PRICE OFFSET (Rs.)</label>
                        <input type="number" name="price" id="drink_price" class="form-control" placeholder="e.g. 0" value="<?= $drink_edit_price ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">STATUS</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $drink_edit_status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $drink_edit_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                            <?= $edit_drink_mode ? 'Save Changes' : 'Add Drink' ?>
                        </button>
                        <?php if ($edit_drink_mode): ?>
                            <a href="products.php?tab=drinks" class="btn btn-outline-secondary w-100 py-2.5">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Drinks list -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h5 class="fw-bold mb-4"><i class="bi bi-list-task text-orange me-2"></i> Global Drinks List</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Price Offset</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_drinks)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No global drinks created.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_drinks as $drk): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?= sanitize($drk['name']) ?></strong></td>
                                        <td class="fw-bold">Rs. <?= number_format($drk['price'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $drk['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $drk['status'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <a href="products.php?tab=drinks&action=edit_drink&id=<?= $drk['id'] ?>" class="btn btn-sm btn-outline-dark me-1" title="Edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger btn-delete-drink" data-id="<?= $drk['id'] ?>" title="Delete">
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
<?php endif; ?>

<!-- SIZES ROW ADD/REMOVE FRONTEND SCRIPT (PRODUCTS FORM) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('btnAddSizeRow');
    const container = document.getElementById('sizesContainer');

    if (addBtn && container) {
        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 align-items-center size-row';
            row.innerHTML = `
                <div class="col-6">
                    <input type="text" name="size_name[]" class="form-control form-control-sm" placeholder="e.g. Extra Large" required>
                </div>
                <div class="col-4">
                    <input type="number" name="size_price[]" class="form-control form-control-sm" placeholder="Price Offset" value="0" required>
                </div>
                <div class="col-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btnRemoveSizeRow"><i class="bi bi-trash"></i></button>
                </div>
            `;
            container.appendChild(row);
            bindSizeDeleteButtons();
        });
    }

    function bindSizeDeleteButtons() {
        document.querySelectorAll('.btnRemoveSizeRow').forEach(btn => {
            btn.onclick = function () {
                const rows = container.querySelectorAll('.size-row');
                if (rows.length > 1) {
                    this.closest('.size-row').remove();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Remove',
                        text: 'At least one size definition is required.',
                        confirmButtonColor: '#FF6B00'
                    });
                }
            };
        });
    }

    bindSizeDeleteButtons();

    // CONFIRMATION POPUPS FOR DELETIONS
    document.querySelectorAll('.btn-delete-product').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: 'Delete Product?',
                text: "This will remove this product completely from the customer menus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B00',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `products?tab=products&action=delete_product&id=${id}`;
                }
            });
        });
    });

    document.querySelectorAll('.btn-delete-addon').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: 'Delete Add-on?',
                text: "Any products currently mapped to this add-on will lose it.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B00',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `products?tab=addons&action=delete_addon&id=${id}`;
                }
            });
        });
    });

    document.querySelectorAll('.btn-delete-drink').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: 'Delete Drink?',
                text: "This will remove this drink choice from all mapped products.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B00',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `products?tab=drinks&action=delete_drink&id=${id}`;
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
