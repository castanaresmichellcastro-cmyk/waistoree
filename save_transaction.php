<?php
session_start();
header('Content-Type: application/json');
require_once 'audit_logger.php';

// Database connection
$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

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

$user_id = $_SESSION['user_id'];

// Get POST data
$transaction_data = json_decode($_POST['transaction_data'], true);

if (!$transaction_data) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction data']);
    exit;
}

try {
    $conn->begin_transaction();

    // Insert transaction
    $customer_name = $conn->real_escape_string($transaction_data['customer_name'] ?? '');
    $total_amount = floatval($transaction_data['total_amount']);
    $payment_method = $conn->real_escape_string($transaction_data['payment_method']);
    $status = $conn->real_escape_string($transaction_data['status']);
    $phone = $conn->real_escape_string($transaction_data['phone'] ?? '');

    $transaction_sql = "INSERT INTO transactions (user_id, customer_name, total_amount, payment_method, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($transaction_sql);
    $stmt->bind_param("issss", $user_id, $customer_name, $total_amount, $payment_method, $status);
    $stmt->execute();
    $transaction_id = $stmt->insert_id;
    $stmt->close();

    // Insert transaction items and update stock
    $items = $transaction_data['items'];
    foreach ($items as $item) {
        // Insert transaction item
        $item_sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, price) 
                     VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($item_sql);
        $stmt->bind_param("iiid", $transaction_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
        $stmt->close();

        // Update product stock (decrease stock)
        $update_stock_sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_stock_sql);
        $stmt->bind_param("iii", $item['quantity'], $item['id'], $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update stock for product ID: " . $item['id']);
        }

        // Check if stock was actually updated
        if ($stmt->affected_rows === 0) {
            throw new Exception("Product not found or insufficient stock for product ID: " . $item['id']);
        }
        $stmt->close();

        // Insert inventory log for stock out
        $log_sql = "INSERT INTO inventory_logs (user_id, product_id, action, quantity_change, created_at) 
                    VALUES (?, ?, 'stock_out', ?, NOW())";
        $stmt = $conn->prepare($log_sql);
        $quantity_change = -$item['quantity'];
        $stmt->bind_param("iii", $user_id, $item['id'], $quantity_change);
        $stmt->execute();
        $stmt->close();
    }

    // If it's a credit sale, create a debt record
    if ($payment_method === 'credit' && $status === 'debt') {
        $debt_sql = "INSERT INTO debts (user_id, customer_name, amount, status, due_date, phone, created_at) 
                     VALUES (?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY), ?, NOW())";
        $stmt = $conn->prepare($debt_sql);
        $stmt->bind_param("isds", $user_id, $customer_name, $total_amount, $phone);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    // Log the transaction in audit
    logAuditMysqli(
        $conn,
        'sale',
        'transaction',
        $transaction_id,
        'POS Sale: ₱' . number_format($total_amount, 2) . ' | Customer: ' . ($customer_name ?: 'Walk-in Suki') . ' | Payment: ' . $payment_method,
        $user_id
    );

    // Create Notification
    $notif_title = "New Sale: ₱" . number_format($total_amount, 2);
    $notif_msg = "Successfully processed " . strtoupper($payment_method) . " payment" . ($customer_name ? " for $customer_name" : "") . ".";
    $notif_type = "success";

    $notif_sql = "INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($notif_sql);
    $stmt->bind_param("isss", $user_id, $notif_type, $notif_title, $notif_msg);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Transaction saved successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>