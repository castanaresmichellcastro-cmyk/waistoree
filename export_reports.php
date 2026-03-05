<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$report_type = $_GET['type'] ?? 'sales';
$format = $_GET['format'] ?? 'csv'; // Default to CSV for better compatibility

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $report_type . '_report_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

try {
    switch ($report_type) {
        case 'sales':
            exportSalesReport($pdo, $output, $user_id);
            break;
        case 'inventory':
            exportInventoryReport($pdo, $output, $user_id);
            break;
        case 'debts':
            exportDebtsReport($pdo, $output, $user_id);
            break;
        case 'customers':
            exportCustomersReport($pdo, $output, $user_id);
            break;
        default:
            exportSalesReport($pdo, $output, $user_id);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error generating report: ' . $e->getMessage()]);
}

fclose($output);

function exportSalesReport($pdo, $output, $user_id)
{
    fputcsv($output, ["Sales Report - " . date('F Y')]);
    fputcsv($output, []); // Empty line
    fputcsv($output, ["Date", "Transactions", "Daily Sales"]);

    // Get sales data
    $query = "SELECT DATE(created_at) as date, COUNT(*) as transactions, SUM(total_amount) as daily_sales 
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' 
              GROUP BY DATE(created_at) 
              ORDER BY date DESC 
              LIMIT 30";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['date'],
            $row['transactions'],
            number_format($row['daily_sales'], 2)
        ]);
    }
}

function exportInventoryReport($pdo, $output, $user_id)
{
    fputcsv($output, ["Inventory Report - " . date('F Y')]);
    fputcsv($output, []); // Empty line
    fputcsv($output, ["Product Name", "Category", "Purchase Price", "Selling Price", "Stock", "Status"]);

    $query = "SELECT * FROM products WHERE user_id = ? ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($product['stock'] > 20) {
            $stock_status = "In Stock";
        } elseif ($product['stock'] > 5) {
            $stock_status = "Low Stock";
        } else {
            $stock_status = "Critical";
        }

        fputcsv($output, [
            $product['name'],
            $product['category'] ?? 'General',
            number_format(($product['purchase_price'] ?? 0), 2),
            number_format($product['price'], 2),
            $product['stock'],
            $stock_status
        ]);
    }
}

function exportDebtsReport($pdo, $output, $user_id)
{
    fputcsv($output, ["Debts Report - " . date('F Y')]);
    fputcsv($output, []); // Empty line
    fputcsv($output, ["Customer", "Total Amount", "Amount Paid", "Amount Due", "Due Date", "Status"]);

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

        if ($debt['status'] == 'paid') {
            $status_text = "Paid";
        } elseif ($total_paid > 0 && $total_paid < $debt['amount']) {
            $status_text = "Partial";
        } else {
            $status_text = "Pending";
        }

        fputcsv($output, [
            $debt['customer_name'],
            number_format($debt['amount'], 2),
            number_format($total_paid, 2),
            number_format($amount_due, 2),
            date('M j, Y', strtotime($debt['due_date'] ?: $debt['created_at'] . ' +7 days')),
            $status_text
        ]);
    }
}

function exportCustomersReport($pdo, $output, $user_id)
{
    fputcsv($output, ["Customers Report - " . date('F Y')]);
    fputcsv($output, []); // Empty line
    fputcsv($output, ["Customer", "Transactions", "Total Spent", "Average Purchase"]);

    $query = "SELECT customer_name, COUNT(*) as transaction_count, SUM(total_amount) as total_spent
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' AND customer_name IS NOT NULL
              GROUP BY customer_name 
              ORDER BY total_spent DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $avg_purchase = $row['total_spent'] / $row['transaction_count'];
        fputcsv($output, [
            $row['customer_name'],
            $row['transaction_count'],
            number_format($row['total_spent'], 2),
            number_format($avg_purchase, 2)
        ]);
    }
}
?>