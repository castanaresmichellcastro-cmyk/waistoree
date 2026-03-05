<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=admin_export_' . $type . '_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

$report_type = $_GET['report_type'] ?? '';

switch ($type) {
    case 'transactions':
        $payment = $_GET['payment'] ?? '';
        exportTransactions($pdo, $output, $search, $status, $payment, $date_from, $date_to);
        break;
    case 'products':
        exportProducts($pdo, $output, $search, $category);
        break;
    case 'debts':
        exportDebts($pdo, $output, $search, $status);
        break;
    case 'audit':
    case 'logs':
        exportAuditLogs($pdo, $output, $search);
        break;
    case 'report':
    case 'reports':
        if ($report_type) {
            exportDetailedReport($pdo, $output, $report_type, $date_from, $date_to);
        } else {
            exportReportsSummary($pdo, $output);
        }
        break;
    case 'users':
        exportUsers($pdo, $output, $search);
        break;
    default:
        fputcsv($output, ['Invalid export type: ' . $type]);
}


fclose($output);

function exportTransactions($pdo, $output, $search, $status, $payment, $date_from, $date_to)
{
    fputcsv($output, ['ID', 'Store', 'User', 'Customer', 'Items', 'Total Amount', 'Payment Method', 'Status', 'Date']);

    $query = "SELECT t.*, u.store_name, u.username, 
              (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count
              FROM transactions t 
              LEFT JOIN users u ON t.user_id = u.id 
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (t.customer_name LIKE ? OR u.store_name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }

    if ($payment) {
        $query .= " AND t.payment_method = ?";
        $params[] = $payment;
    }

    if ($date_from) {
        $query .= " AND DATE(t.created_at) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $query .= " AND DATE(t.created_at) <= ?";
        $params[] = $date_to;
    }

    $query .= " ORDER BY t.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['store_name'] ?? 'N/A',
            $row['username'],
            $row['customer_name'] ?? 'Walk-in',
            $row['item_count'],
            number_format($row['total_amount'], 2),
            ucfirst($row['payment_method']),
            ucfirst($row['status']),
            $row['created_at']
        ]);
    }
}

function exportProducts($pdo, $output, $search, $category)
{
    fputcsv($output, ['ID', 'Product Name', 'Store', 'Category', 'Price', 'Stock', 'Barcode', 'Created At']);

    $query = "SELECT p.*, u.store_name, u.username 
              FROM products p 
              LEFT JOIN users u ON p.user_id = u.id 
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (p.name LIKE ? OR p.barcode LIKE ? OR u.store_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category) {
        $query .= " AND p.category = ?";
        $params[] = $category;
    }

    $query .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['store_name'] ?? 'N/A',
            $row['category'] ?? 'General',
            number_format($row['price'], 2),
            $row['stock'],
            $row['barcode'] ?? 'N/A',
            $row['created_at']
        ]);
    }
}

function exportDebts($pdo, $output, $search, $status)
{
    fputcsv($output, ['ID', 'Customer', 'Store', 'Amount', 'Paid', 'Due', 'Due Date', 'Status', 'Created At']);

    $query = "SELECT d.*, u.store_name, 
              (SELECT SUM(amount_paid) FROM debt_payments dp WHERE dp.debt_id = d.id) as total_paid
              FROM debts d 
              LEFT JOIN users u ON d.user_id = u.id 
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (d.customer_name LIKE ? OR u.store_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status) {
        $query .= " AND d.status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY d.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_paid = $row['total_paid'] ?: 0;
        $amount_due = $row['amount'] - $total_paid;

        fputcsv($output, [
            $row['id'],
            $row['customer_name'],
            $row['store_name'] ?? 'N/A',
            number_format($row['amount'], 2),
            number_format($total_paid, 2),
            number_format($amount_due, 2),
            $row['due_date'],
            ucfirst($row['status']),
            $row['created_at']
        ]);
    }
}

function exportAuditLogs($pdo, $output, $search)
{
    fputcsv($output, ['ID', 'Admin User', 'Action', 'Entity Type', 'Entity ID', 'Details', 'IP Address', 'Date']);

    $query = "SELECT a.*, au.username as admin_username 
              FROM audit_logs a 
              LEFT JOIN admin_users au ON a.admin_id = au.id 
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (a.action LIKE ? OR a.details LIKE ? OR au.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['admin_username'] ?? 'System/Deleted',
            $row['action'],
            $row['entity_type'],
            $row['entity_id'],
            $row['details'],
            $row['ip_address'],
            $row['created_at']
        ]);
    }
}

function exportReportsSummary($pdo, $output)
{
    fputcsv($output, ['Category', 'Value']);

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    fputcsv($output, ['Total Users', $stmt->fetch()['total']]);

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    fputcsv($output, ['Total Products', $stmt->fetch()['total']]);

    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(total_amount) as revenue FROM transactions WHERE status = 'paid'");
    $res = $stmt->fetch();
    fputcsv($output, ['Total Transactions', $res['total']]);
    fputcsv($output, ['Total Revenue', number_format($res['revenue'], 2)]);

    $stmt = $pdo->query("SELECT SUM(amount) as total FROM debts WHERE status != 'paid'");
    fputcsv($output, ['Total Outstanding Debts', number_format($stmt->fetch()['total'] ?? 0, 2)]);
}

function exportUsers($pdo, $output, $search)
{
    fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'Phone', 'Store Name', 'Address', 'User Type', 'Status', 'Created At']);

    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (username LIKE ? OR full_name LIKE ? OR store_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['store_name'],
            $row['address'],
            $row['user_type'],
            $row['status'] ?? 'Active',
            $row['created_at']
        ]);
    }
}

function exportDetailedReport($pdo, $output, $report_type, $date_from, $date_to)
{
    switch ($report_type) {
        case 'sales':
            fputcsv($output, ['Sales Analytics Report']);
            fputcsv($output, ['Date Range', ($date_from ?: 'All Time') . ' to ' . ($date_to ?: 'Present')]);
            fputcsv($output, []);
            fputcsv($output, ['Date', 'Transactions', 'Total Sales', 'Average Sale']);

            $query = "SELECT DATE(created_at) as date, COUNT(*) as transaction_count, SUM(total_amount) as total_sales, AVG(total_amount) as avg_sale 
                      FROM transactions WHERE status = 'paid'";
            $params = [];
            if ($date_from) {
                $query .= " AND DATE(created_at) >= ?";
                $params[] = $date_from;
            }
            if ($date_to) {
                $query .= " AND DATE(created_at) <= ?";
                $params[] = $date_to;
            }
            $query .= " GROUP BY DATE(created_at) ORDER BY date DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [$row['date'], $row['transaction_count'], number_format($row['total_sales'], 2), number_format($row['avg_sale'], 2)]);
            }
            break;

        case 'inventory':
            fputcsv($output, ['System-wide Inventory Report']);
            fputcsv($output, []);
            fputcsv($output, ['Product', 'Category', 'Store', 'Stock', 'Price', 'Created At']);

            $stmt = $pdo->query("SELECT p.*, u.store_name FROM products p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.stock ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['name'],
                    $row['category'],
                    $row['store_name'],
                    $row['stock'],
                    number_format($row['price'], 2),
                    $row['created_at']
                ]);
            }
            break;

        case 'users':
            fputcsv($output, ['User Activity Report']);
            fputcsv($output, []);
            fputcsv($output, ['Store Name', 'Username', 'Total Transactions', 'Total Revenue']);

            $stmt = $pdo->query("SELECT u.store_name, u.username, COUNT(t.id) as trans_count, SUM(t.total_amount) as total_rev 
                                FROM users u 
                                LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'paid' 
                                GROUP BY u.id 
                                ORDER BY total_rev DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['store_name'],
                    $row['username'],
                    $row['trans_count'],
                    number_format($row['total_rev'] ?: 0, 2)
                ]);
            }
            break;

        default:
            fputcsv($output, ['Unknown report type: ' . $report_type]);
    }
}
