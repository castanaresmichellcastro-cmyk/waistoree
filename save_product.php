<?php
session_start();
require_once 'audit_logger.php';

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
$product_id = $_POST['product_id'] ?? null;
$user_id = $_SESSION['user_id'];
$product_name = $_POST['product_name'];
$product_category = $_POST['product_category'] ?? '';
$purchase_price = $_POST['purchase_price'];
$selling_price = $_POST['selling_price'];
$product_stock = $_POST['product_stock'];
$purchase_date = $_POST['purchase_date'] ?? null;
$expiry_date = $_POST['expiry_date'] ?? null;
$product_description = $_POST['product_description'] ?? '';

// Validate required fields
if (empty($product_name) || empty($purchase_price) || empty($selling_price) || empty($product_stock)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header("Location: inventory.php");
    exit();
}

// Handle empty dates
if (empty($purchase_date))
    $purchase_date = null;
if (empty($expiry_date))
    $expiry_date = null;

if ($product_id) {
    // Update existing product
    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, purchase_price=?, selling_price=?, stock=?, purchase_date=?, expiry_date=?, description=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssdddssssii", $product_name, $product_category, $selling_price, $purchase_price, $selling_price, $product_stock, $purchase_date, $expiry_date, $product_description, $product_id, $user_id);
} else {
    // Insert new product
    $stmt = $conn->prepare("INSERT INTO products (user_id, name, category, price, purchase_price, selling_price, stock, purchase_date, expiry_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdddssss", $user_id, $product_name, $product_category, $selling_price, $purchase_price, $selling_price, $product_stock, $purchase_date, $expiry_date, $product_description);
}

if ($stmt->execute()) {
    $pid = $product_id ?: $conn->insert_id;
    $action = $product_id ? 'update_product' : 'create_product';
    logAuditMysqli($conn, $action, 'product', $pid, ($product_id ? 'Updated' : 'Added') . ' product: "' . $product_name . '" | Stock: ' . $product_stock . ' | Price: ₱' . number_format($selling_price, 2), $user_id);
    $_SESSION['success'] = $product_id ? 'Product updated successfully!' : 'Product added successfully!';
} else {
    $_SESSION['error'] = 'Error saving product: ' . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to inventory page
header("Location: inventory.php");
exit();
?>