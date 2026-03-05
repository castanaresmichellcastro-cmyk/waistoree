<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['fullName'];
$store_name = $_POST['storeName'];
$username = $_POST['username'];
$email = $_POST['email'];
$address = $_POST['address'];

// Check if username or email already exists (excluding current user)
$check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ssi", $username, $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Username or email already exists.";
    header('Location: account.php');
    exit();
}

// Update user information
$update_query = "UPDATE users SET full_name = ?, store_name = ?, username = ?, email = ?, address = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssssi", $full_name, $store_name, $username, $email, $address, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Account information updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update account information.";
}

// Handle password change if provided
if (!empty($_POST['currentPassword']) && !empty($_POST['newPassword'])) {
    $current_password = $_POST['currentPassword'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
        header('Location: account.php');
        exit();
    }
    
    // Verify current password
    $password_query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($password_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_password_query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Password updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update password.";
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect.";
    }
}

$stmt->close();
$conn->close();

header('Location: account.php');
exit();
?>