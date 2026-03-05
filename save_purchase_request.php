<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$requested_quantity = $_POST['requested_quantity'];
$priority = $_POST['priority'];
$notes = $_POST['notes'] ?? '';

// Validate required fields
if (empty($product_id) || empty($requested_quantity)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header("Location: inventory.php");
    exit();
}

// Get product details
$product_query = $conn->prepare("SELECT name, stock FROM products WHERE id = ? AND user_id = ?");
$product_query->bind_param("ii", $product_id, $user_id);
$product_query->execute();
$product_result = $product_query->get_result();

if ($product_result->num_rows === 0) {
    $_SESSION['error'] = 'Product not found';
    header("Location: inventory.php");
    exit();
}

$product = $product_result->fetch_assoc();
$product_name = $product['name'];
$current_stock = $product['stock'];

// Insert purchase request
$stmt = $conn->prepare("INSERT INTO purchase_requests (user_id, product_id, product_name, current_stock, requested_quantity, priority, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisiiss", $user_id, $product_id, $product_name, $current_stock, $requested_quantity, $priority, $notes);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Purchase request submitted successfully!';
} else {
    $_SESSION['error'] = 'Error submitting request: ' . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to inventory page
header("Location: inventory.php");
exit();
?>