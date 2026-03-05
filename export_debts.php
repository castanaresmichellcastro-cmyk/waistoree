<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=debts_export_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header
fputcsv($output, ['Customer Name', 'Phone', 'Total Amount', 'Amount Paid', 'Amount Due', 'Date Issued', 'Due Date', 'Status']);

try {
    // Get debts with payment information using PDO
    $query = "SELECT d.*, 
              (SELECT SUM(amount_paid) FROM debt_payments dp WHERE dp.debt_id = d.id) as total_paid
              FROM debts d 
              WHERE d.user_id = ? 
              ORDER BY d.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    while ($debt = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_paid = $debt['total_paid'] ?: 0;
        $amount_due = $debt['amount'] - $total_paid;

        // Determine status
        if ($debt['status'] == 'paid') {
            $status_text = "Paid";
        } elseif ($total_paid > 0 && $total_paid < $debt['amount']) {
            $status_text = "Partial";
        } else {
            $due_date = strtotime($debt['due_date'] ?: $debt['created_at'] . ' +7 days');
            $today = time();

            if ($due_date < $today) {
                $status_text = "Overdue";
            } else {
                $status_text = "Pending";
            }
        }

        fputcsv($output, [
            $debt['customer_name'],
            $debt['phone'] ?? '',
            number_format($debt['amount'], 2),
            number_format($total_paid, 2),
            number_format($amount_due, 2),
            date('M j, Y', strtotime($debt['created_at'])),
            date('M j, Y', strtotime($debt['due_date'] ?: $debt['created_at'] . ' +7 days')),
            $status_text
        ]);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error exporting data: ' . $e->getMessage()]);
}

fclose($output);
?>