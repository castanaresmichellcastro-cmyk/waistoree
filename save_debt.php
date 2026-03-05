<?php
// save_debt.php
session_start();
header('Content-Type: application/json');
require_once 'audit_logger.php';

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get POST data
$customer_name = $_POST['customer_name'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$due_date = $_POST['due_date'] ?? '';
$phone = $_POST['phone'] ?? '';
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($customer_name) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer name or amount']);
    exit;
}

try {
    $sql = "INSERT INTO debts (user_id, customer_name, amount, status, due_date, phone, notes, created_at) 
            VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsss", $user_id, $customer_name, $amount, $due_date, $phone, $notes);

    if ($stmt->execute()) {
        $debt_id = $conn->insert_id;
        logAuditMysqli($conn, 'create_utang', 'debt', $debt_id, 'New utang: ' . $customer_name . ' owes ₱' . number_format($amount, 2) . ' | Due: ' . $due_date, $user_id);
        echo json_encode(['success' => true, 'message' => 'Debt created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating debt: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>