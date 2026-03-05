<?php
session_start();
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_user':
            updateUser($pdo);
            break;
        case 'delete_user':
            deleteUser($pdo);
            break;
        default:
            header("HTTP/1.1 400 Bad Request");
            echo "Invalid action";
            exit;
    }
}

function updateUser($pdo) {
    $user_id = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $store_name = $_POST['store_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $is_verified = $_POST['is_verified'] ?? 0;
    
    if (empty($user_id) || empty($username) || empty($full_name) || empty($email)) {
        header("HTTP/1.1 400 Bad Request");
        echo "Missing required fields";
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, store_name = ?, email = ?, is_verified = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$username, $full_name, $store_name, $email, $is_verified, $user_id]);
        
        header("Location: admin.php?message=User updated successfully");
        exit;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Database error: " . $e->getMessage();
    }
}

function deleteUser($pdo) {
    $user_id = $_POST['user_id'] ?? 0;
    
    if (empty($user_id)) {
        header("HTTP/1.1 400 Bad Request");
        echo "Missing user ID";
        return;
    }
    
    try {
        // In a real application, you might want to soft delete instead
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        header("Location: admin.php?message=User deleted successfully");
        exit;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Database error: " . $e->getMessage();
    }
}
?>