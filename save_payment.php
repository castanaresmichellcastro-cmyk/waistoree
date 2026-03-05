<?php
header('Content-Type: application/json');
session_start();

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$debt_id = intval($_POST['debt_id'] ?? 0);
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');
$payment_method = $_POST['payment_method'] ?? 'cash';
$notes = $_POST['notes'] ?? '';

if ($debt_id <= 0 || $amount_paid <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid debt ID or payment amount']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, get the current debt information
    $debt_sql = "SELECT amount, amount_paid, status FROM debts WHERE id = ? AND user_id = ?";
    $debt_stmt = $conn->prepare($debt_sql);
    $debt_stmt->bind_param("ii", $debt_id, $_SESSION['user_id']);
    $debt_stmt->execute();
    $debt_result = $debt_stmt->get_result();
    
    if ($debt_result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Debt not found']);
        exit;
    }
    
    $debt = $debt_result->fetch_assoc();
    $debt_stmt->close();
    
    $total_amount = floatval($debt['amount']);
    $current_paid = floatval($debt['amount_paid']);
    $new_total_paid = $current_paid + $amount_paid;
    
    // Check if payment exceeds debt amount
    if ($new_total_paid > $total_amount) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds remaining debt']);
        exit;
    }
    
    // Insert payment record
    $payment_sql = "INSERT INTO debt_payments (debt_id, amount_paid, payment_date, payment_method, notes) 
                    VALUES (?, ?, ?, ?, ?)";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("idsss", $debt_id, $amount_paid, $payment_date, $payment_method, $notes);
    
    if (!$payment_stmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording payment: ' . $payment_stmt->error]);
        exit;
    }
    $payment_stmt->close();
    
    // Update debt status and amount_paid
    $new_status = 'partial';
    if ($new_total_paid >= $total_amount) {
        $new_status = 'paid';
        $new_total_paid = $total_amount; // Prevent overpayment
    } elseif ($new_total_paid > 0) {
        $new_status = 'partial';
    }
    
    $update_sql = "UPDATE debts SET amount_paid = ?, status = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dsi", $new_total_paid, $new_status, $debt_id);
    
    if (!$update_stmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error updating debt: ' . $update_stmt->error]);
        exit;
    }
    $update_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment recorded successfully',
        'new_status' => $new_status,
        'total_paid' => $new_total_paid,
        'remaining' => $total_amount - $new_total_paid
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>