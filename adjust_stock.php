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
$action = $_POST['action'];
$quantity = $_POST['quantity'];
$reason = $_POST['reason'] ?? '';

// Validate required fields
if (empty($product_id) || empty($action) || empty($quantity)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

// Get current stock
$current_query = $conn->prepare("SELECT stock FROM products WHERE id = ? AND user_id = ?");
$current_query->bind_param("ii", $product_id, $user_id);
$current_query->execute();
$current_result = $current_query->get_result();

if ($current_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$current_data = $current_result->fetch_assoc();
$current_stock = $current_data['stock'];
$new_stock = $current_stock;

// Calculate new stock based on action
switch ($action) {
    case 'add':
        $new_stock = $current_stock + $quantity;
        break;
    case 'remove':
        $new_stock = $current_stock - $quantity;
        if ($new_stock < 0)
            $new_stock = 0;
        break;
    case 'set':
        $new_stock = $quantity;
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
}

// Update stock
$stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $new_stock, $product_id, $user_id);

if ($stmt->execute()) {
    // Log the stock adjustment (optional)
    $log_stmt = $conn->prepare("INSERT INTO stock_adjustments (user_id, product_id, action, quantity, reason, previous_stock, new_stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $log_stmt->bind_param("iissiii", $user_id, $product_id, $action, $quantity, $reason, $current_stock, $new_stock);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating stock: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>