<?php
// logout.php
require_once __DIR__ . '/includes/functions.php';

// Unset user sessions
$_SESSION['user_logged_in'] = false;
unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Redirect to home
redirect('index');
?>
