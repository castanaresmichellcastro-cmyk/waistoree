<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// Validate required fields
if (empty($product_id)) {
    $_SESSION['error'] = 'Product ID is required';
    header("Location: inventory.php");
    exit();
}

// Delete product
$stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Product deleted successfully!';
} else {
    $_SESSION['error'] = 'Error deleting product: ' . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to inventory page
header("Location: inventory.php");
exit();
?>