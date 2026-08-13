<?php
// admin/settings.php
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';
$settings_path = dirname(__DIR__) . '/includes/settings.json';

// Fetch current JSON settings
$settings = [];
if (file_exists($settings_path)) {
    $settings = json_decode(file_get_contents($settings_path), true);
}
$restaurant_name = $settings['restaurant_name'] ?? 'Cravers';
$contact_number  = $settings['contact_number'] ?? '0311 7593578';
$address         = $settings['address'] ?? '359-V Nao Gazah Rd, Chiniot, 35400';
$city            = $settings['city'] ?? 'Chiniot';
$hours           = $settings['hours'] ?? 'Open 24 hours';
$service_options = $settings['service_options'] ?? 'Cash only';
$promo_text      = $settings['promo_text'] ?? 'Craving Something Delicious in Chiniot?';
$promo_subtitle  = $settings['promo_subtitle'] ?? 'Get the best burgers...';

// PROCESS FORMS (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);

    // 1. SAVE GENERAL SETTINGS
    if ($action === 'save_settings') {
        $restaurant_name = sanitize($_POST['restaurant_name'] ?? 'Cravers');
        $contact_number  = sanitize($_POST['contact_number'] ?? '');
        $address         = sanitize($_POST['address'] ?? '');
        $city            = sanitize($_POST['city'] ?? 'Chiniot');
        $hours           = sanitize($_POST['hours'] ?? '');
        $service_options = sanitize($_POST['service_options'] ?? '');
        $promo_text      = sanitize($_POST['promo_text'] ?? '');
        $promo_subtitle  = sanitize($_POST['promo_subtitle'] ?? '');

        if (empty($restaurant_name)) {
            $error = 'Restaurant name is required.';
        } else {
            $new_settings = [
                'restaurant_name' => $restaurant_name,
                'contact_number'  => $contact_number,
                'address'         => $address,
                'city'            => $city,
                'hours'           => $hours,
                'service_options' => $service_options,
                'promo_text'      => $promo_text,
                'promo_subtitle'  => $promo_subtitle
            ];

            if (file_put_contents($settings_path, json_encode($new_settings, JSON_PRETTY_PRINT))) {
                $success = 'General settings updated successfully!';
            } else {
                $error = 'Failed to write configurations to settings.json.';
            }
        }
    }

    // 2. CHANGE PASSWORD
    if ($action === 'change_password') {
        $curr_pass = sanitize($_POST['curr_pass'] ?? '');
        $new_pass  = sanitize($_POST['new_pass'] ?? '');
        $conf_pass = sanitize($_POST['conf_pass'] ?? '');

        if (empty($curr_pass) || empty($new_pass) || empty($conf_pass)) {
            $error = 'All password fields are required.';
        } elseif ($new_pass !== $conf_pass) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            // Retrieve current user
            $admin_id = $_SESSION['admin_id'];
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $current_hash = $stmt->fetchColumn();

            if ($current_hash && password_verify($curr_pass, $current_hash)) {
                // Hash new password and update
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                $update_stmt->execute([$new_hash, $admin_id]);
                $success = 'Admin security password changed successfully!';
            } else {
                $error = 'Incorrect current password.';
            }
        }
    }
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

<div class="row g-4">
    <!-- Left: General branding info -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4"><i class="bi bi-shop text-orange me-2"></i> General Brand Details</h5>
            
            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="mb-3">
                    <label for="restaurant_name" class="form-label small fw-bold text-muted">RESTAURANT BRAND NAME</label>
                    <input type="text" name="restaurant_name" id="restaurant_name" class="form-control" value="<?= sanitize($restaurant_name) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="contact_number" class="form-label small fw-bold text-muted">HOTLINE / PHONE NUMBER</label>
                    <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= sanitize($contact_number) ?>">
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label small fw-bold text-muted">PHYSICAL STORE ADDRESS</label>
                    <input type="text" name="address" id="address" class="form-control" value="<?= sanitize($address) ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="city" class="form-label small fw-bold text-muted">CITY</label>
                        <input type="text" name="city" id="city" class="form-control" value="<?= sanitize($city) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="hours" class="form-label small fw-bold text-muted">OPERATING HOURS</label>
                        <input type="text" name="hours" id="hours" class="form-control" value="<?= sanitize($hours) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="service_options" class="form-label small fw-bold text-muted">SERVICE / PAYMENT OPTIONS</label>
                    <input type="text" name="service_options" id="service_options" class="form-control" value="<?= sanitize($service_options) ?>">
                </div>

                <div class="mb-3">
                    <label for="promo_text" class="form-label small fw-bold text-muted">PROMO BANNER TITLE</label>
                    <input type="text" name="promo_text" id="promo_text" class="form-control" value="<?= sanitize($promo_text) ?>">
                </div>

                <div class="mb-4">
                    <label for="promo_subtitle" class="form-label small fw-bold text-muted">PROMO BANNER DESCRIPTION</label>
                    <textarea name="promo_subtitle" id="promo_subtitle" rows="3" class="form-control"><?= sanitize($promo_subtitle) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary-orange w-100 py-2.5">
                    Save Branding Configurations
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Change Admin Password -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock text-orange me-2"></i> Update Security Credentials</h5>
            
            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label for="curr_pass" class="form-label small fw-bold text-muted">CURRENT ADMIN PASSWORD</label>
                    <input type="password" name="curr_pass" id="curr_pass" class="form-control" placeholder="Enter current password" required>
                </div>

                <hr class="my-3">
                
                <div class="mb-3">
                    <label for="new_pass" class="form-label small fw-bold text-muted">NEW PASSWORD</label>
                    <input type="password" name="new_pass" id="new_pass" class="form-control" placeholder="Enter new password (min. 6 chars)" required>
                </div>

                <div class="mb-4">
                    <label for="conf_pass" class="form-label small fw-bold text-muted">CONFIRM NEW PASSWORD</label>
                    <input type="password" name="conf_pass" id="conf_pass" class="form-control" placeholder="Verify new password" required>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-2.5">
                    Update Password Key
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
