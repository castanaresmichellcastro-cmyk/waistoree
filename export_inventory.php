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
header('Content-Disposition: attachment; filename=inventory_export_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header
fputcsv($output, ['Product Name', 'Category', 'Purchase Price', 'Selling Price', 'Stock', 'Status', 'Purchase Date', 'Expiry Date']);

try {
    // Get products
    $query = "SELECT * FROM products WHERE user_id = ? ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);

    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determine stock status
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
            number_format($product['purchase_price'], 2),
            number_format($product['selling_price'], 2),
            $product['stock'],
            $stock_status,
            $product['purchase_date'] ? date('M j, Y', strtotime($product['purchase_date'])) : '-',
            $product['expiry_date'] ? date('M j, Y', strtotime($product['expiry_date'])) : '-'
        ]);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error exporting data: ' . $e->getMessage()]);
}

fclose($output);
?>