<?php
// admin_ajax.php
require_once 'admin_auth.php';
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_user':
            $user_id = intval($_POST['user_id']);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            break;

        case 'update_user':
            $user_id = intval($_POST['user_id']);
            $username = $_POST['username'];
            $full_name = $_POST['full_name'];
            $store_name = $_POST['store_name'];
            $email = $_POST['email'];
            $is_verified = intval($_POST['is_verified']);
            
            $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, store_name = ?, email = ?, is_verified = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$username, $full_name, $store_name, $email, $is_verified, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            
            // Check if user has related data
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $product_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $transaction_count = $stmt->fetchColumn();
            
            if ($product_count > 0 || $transaction_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete user with existing products or transactions']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            }
            break;

        case 'get_transaction':
            $transaction_id = intval($_POST['transaction_id']);
            
            // Get transaction details
            $stmt = $pdo->prepare("
                SELECT t.*, u.store_name 
                FROM transactions t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.id = ?
            ");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch();
            
            if ($transaction) {
                // Get transaction items
                $stmt = $pdo->prepare("
                    SELECT ti.*, p.name as product_name 
                    FROM transaction_items ti 
                    LEFT JOIN products p ON ti.product_id = p.id 
                    WHERE ti.transaction_id = ?
                ");
                $stmt->execute([$transaction_id]);
                $items = $stmt->fetchAll();
                
                $html = "
                    <div class='form-group'>
                        <label><strong>Transaction ID:</strong> #{$transaction['id']}</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Store:</strong> {$transaction['store_name']}</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Customer:</strong> " . ($transaction['customer_name'] ?: 'N/A') . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Total Amount:</strong> ₱" . number_format($transaction['total_amount'], 2) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Payment Method:</strong> " . ucfirst($transaction['payment_method']) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Status:</strong> <span class='status-badge status-{$transaction['status']}'>" . ucfirst($transaction['status']) . "</span></label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Date:</strong> " . date('M j, Y g:i A', strtotime($transaction['created_at'])) . "</label>
                    </div>
                ";
                
                if (!empty($items)) {
                    $html .= "
                        <div class='form-group'>
                            <label><strong>Items:</strong></label>
                            <table class='data-table' style='margin-top: 10px;'>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                    ";
                    
                    foreach ($items as $item) {
                        $subtotal = $item['quantity'] * $item['price'];
                        $html .= "
                            <tr>
                                <td>{$item['product_name']}</td>
                                <td>{$item['quantity']}</td>
                                <td>₱" . number_format($item['price'], 2) . "</td>
                                <td>₱" . number_format($subtotal, 2) . "</td>
                            </tr>
                        ";
                    }
                    
                    $html .= "
                                </tbody>
                            </table>
                        </div>
                    ";
                }
                
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            }
            break;

        case 'get_debt':
            $debt_id = intval($_POST['debt_id']);
            
            // Get debt details
            $stmt = $pdo->prepare("
                SELECT d.*, u.store_name 
                FROM debts d 
                LEFT JOIN users u ON d.user_id = u.id 
                WHERE d.id = ?
            ");
            $stmt->execute([$debt_id]);
            $debt = $stmt->fetch();
            
            if ($debt) {
                // Get debt payments
                $stmt = $pdo->prepare("SELECT * FROM debt_payments WHERE debt_id = ? ORDER BY payment_date DESC");
                $stmt->execute([$debt_id]);
                $payments = $stmt->fetchAll();
                
                $balance = $debt['amount'] - $debt['amount_paid'];
                
                $html = "
                    <div class='form-group'>
                        <label><strong>Debt ID:</strong> {$debt['id']}</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Store:</strong> {$debt['store_name']}</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Customer:</strong> {$debt['customer_name']}</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Phone:</strong> " . ($debt['phone'] ?: 'N/A') . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Total Amount:</strong> ₱" . number_format($debt['amount'], 2) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Amount Paid:</strong> ₱" . number_format($debt['amount_paid'], 2) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Balance:</strong> ₱" . number_format($balance, 2) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Status:</strong> <span class='status-badge status-{$debt['status']}'>" . ucfirst($debt['status']) . "</span></label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Due Date:</strong> " . date('M j, Y', strtotime($debt['due_date'])) . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Notes:</strong> " . ($debt['notes'] ?: 'N/A') . "</label>
                    </div>
                    <div class='form-group'>
                        <label><strong>Created:</strong> " . date('M j, Y g:i A', strtotime($debt['created_at'])) . "</label>
                    </div>
                ";
                
                if (!empty($payments)) {
                    $html .= "
                        <div class='form-group'>
                            <label><strong>Payment History:</strong></label>
                            <table class='data-table' style='margin-top: 10px;'>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                    ";
                    
                    foreach ($payments as $payment) {
                        $html .= "
                            <tr>
                                <td>" . date('M j, Y', strtotime($payment['payment_date'])) . "</td>
                                <td>₱" . number_format($payment['amount_paid'], 2) . "</td>
                                <td>" . ucfirst($payment['payment_method']) . "</td>
                                <td>" . ($payment['notes'] ?: 'N/A') . "</td>
                            </tr>
                        ";
                    }
                    
                    $html .= "
                                </tbody>
                            </table>
                        </div>
                    ";
                }
                
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Debt not found']);
            }
            break;

        case 'add_debt_payment':
            $debt_id = intval($_POST['debt_id']);
            $amount_paid = floatval($_POST['amount_paid']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $notes = $_POST['notes'] ?? '';
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Insert payment record
                $stmt = $pdo->prepare("INSERT INTO debt_payments (debt_id, amount_paid, payment_date, payment_method, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$debt_id, $amount_paid, $payment_date, $payment_method, $notes]);
                
                // Update debt record
                $stmt = $pdo->prepare("
                    UPDATE debts 
                    SET amount_paid = amount_paid + ?, 
                        status = CASE 
                            WHEN (amount_paid + ?) >= amount THEN 'paid'
                            WHEN (amount_paid + ?) > 0 THEN 'partial'
                            ELSE 'pending'
                        END,
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$amount_paid, $amount_paid, $amount_paid, $debt_id]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Payment added successfully']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to add payment: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>