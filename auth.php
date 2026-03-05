<?php
session_start();
include 'db.php';
require_once 'audit_logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if user exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['store_name'] = $user['store_name'];

            // Log successful login
            logAuditMysqli($conn, 'login', 'user', $user['id'], 'User "' . $user['username'] . '" logged in from ' . $user['store_name'], $user['id']);

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        }
    }

    // Log failed login attempt
    logAuditMysqli($conn, 'login_failed', 'user', null, 'Failed login attempt for username: ' . $username);

    // If login fails, redirect back to login page with error
    header("Location: index.php?error=1");
    exit();
}
?>