<?php
// admin/includes/auth.php
// ============================================================
//  Admin Authentication Guard — Secure Version
//  ✅ Session validation
//  ✅ Session hijack prevention (IP + User-Agent binding)
//  ✅ Session timeout (30 mins idle)
//  ✅ Redirects to clean URL (no .php)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,            // until browser close
        'path'     => '/',
        'secure'   => false,        // set true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// ── Session Timeout: 30 minutes idle ────────────────────────
$session_timeout = 1800; // 30 mins in seconds
if (isset($_SESSION['admin_last_activity'])) {
    if ((time() - $_SESSION['admin_last_activity']) > $session_timeout) {
        // Session expired — destroy and redirect
        session_unset();
        session_destroy();
        header("Location: /Resturant-Web/admin/login?reason=timeout");
        exit();
    }
}
$_SESSION['admin_last_activity'] = time();

// ── Session Hijack Prevention ────────────────────────────────
// Bind session to the browser fingerprint
$current_fingerprint = md5(
    ($_SERVER['HTTP_USER_AGENT'] ?? '') .
    ($_SERVER['REMOTE_ADDR']     ?? '')
);

if (isset($_SESSION['admin_fingerprint'])) {
    if ($_SESSION['admin_fingerprint'] !== $current_fingerprint) {
        // Fingerprint mismatch — possible hijack attempt
        session_unset();
        session_destroy();
        header("Location: /Resturant-Web/admin/login?reason=security");
        exit();
    }
} else {
    $_SESSION['admin_fingerprint'] = $current_fingerprint;
}

// ── Check if admin is logged in ──────────────────────────────
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: /Resturant-Web/admin/login");
    exit();
}

// ── Role Check Helper (optional usage) ───────────────────────
function require_role($required_role) {
    $role_hierarchy = ['staff' => 1, 'admin' => 2, 'superadmin' => 3];
    $user_role  = $_SESSION['admin_role'] ?? 'staff';
    $user_level = $role_hierarchy[$user_role]  ?? 0;
    $req_level  = $role_hierarchy[$required_role] ?? 99;
    if ($user_level < $req_level) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;text-align:center;margin-top:100px;">⛔ Access Denied — Insufficient permissions.</h2>');
    }
}
?>
