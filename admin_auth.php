<?php
// Admin authentication check
session_start();

// If no admin session exists, redirect to login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if admin session is still valid
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > 3600)) {
    // Session expired after 1 hour
    session_unset();
    session_destroy();
    header("Location: admin_login.php?expired=1");
    exit();
}

// Update last activity time
$_SESSION['admin_last_activity'] = time();

// CSRF token generation
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
?>