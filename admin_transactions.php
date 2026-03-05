<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query for transactions
$query = "SELECT t.*, u.store_name, u.username, 
          (SELECT COUNT(*) FROM transaction_items ti WHERE ti.transaction_id = t.id) as item_count
          FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (t.customer_name LIKE ? OR u.store_name LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($status_filter)) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

if (!empty($payment_filter)) {
    $query .= " AND t.payment_method = ?";
    $params[] = $payment_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE 1=1";
$count_params = [];
if (!empty($search)) {
    $count_query .= " AND (t.customer_name LIKE ? OR u.store_name LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $count_params = array_merge($count_params, [$search_term, $search_term, $search_term]);
}
if (!empty($status_filter)) {
    $count_query .= " AND t.status = ?";
    $count_params[] = $status_filter;
}
if (!empty($payment_filter)) {
    $count_query .= " AND t.payment_method = ?";
    $count_params[] = $payment_filter;
}
if (!empty($date_from)) {
    $count_query .= " AND DATE(t.created_at) >= ?";
    $count_params[] = $date_from;
}
if (!empty($date_to)) {
    $count_query .= " AND DATE(t.created_at) <= ?";
    $count_params[] = $date_to;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_transactions = $count_stmt->fetch()['total'];
$total_pages = ceil($total_transactions / $limit);

// Add pagination to main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);


try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get transaction statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_count,
            SUM(total_amount) as total_amount,
            AVG(total_amount) as avg_amount,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'debt' THEN 1 END) as debt_count
        FROM transactions
    ");
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle transaction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    
    if (!hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } elseif ($transaction_id > 0) {
        switch ($action) {
            case 'update_status':
                $new_status = $_POST['status'] ?? '';
                if (in_array($new_status, ['paid', 'pending', 'debt'])) {
                    try {
                        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $transaction_id]);
                        $success = "Transaction status updated.";
                    } catch (PDOException $e) {
                        $error = "Failed to update transaction: " . $e->getMessage();
                    }
                }
                break;
                
            case 'delete_transaction':
                try {
                    $pdo->beginTransaction();
                    
                    // Delete transaction items first
                    $pdo->prepare("DELETE FROM transaction_items WHERE transaction_id = ?")->execute([$transaction_id]);
                    
                    // Delete the transaction
                    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
                    $stmt->execute([$transaction_id]);
                    
                    $pdo->commit();
                    $success = "Transaction deleted successfully.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Failed to delete transaction: " . $e->getMessage();
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: admin_transactions.php?" . ($_SERVER['QUERY_STRING'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Transaction Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        /* Same CSS structure as previous pages - would be in separate CSS file */
        /* Including only additional styles specific to transactions */
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-paid {
            background-color: rgba(52, 199, 89, 0.2);
            color: var(--accent);
        }

        .status-pending {
            background-color: rgba(255, 149, 0, 0.2);
            color: var(--warning);
        }

        .status-debt {
            background-color: rgba(255, 59, 48, 0.2);
            color: var(--danger);
        }

        .date-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
        }
           :root {
            --primary: #2D5BFF;
            --primary-dark: #1A46E0;
            --secondary: #FF9E1A;
            --accent: #34C759;
            --danger: #FF3B30;
            --warning: #FF9500;
            --light: #F8F9FA;
            --dark: #1C1C1E;
            --gray: #8E8E93;
            --background: #FFFFFF;
            --card-bg: #F2F2F7;
            --admin-primary: #8E44AD;
            --admin-dark: #6C3483;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-dark));
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo i {
            font-size: 1.8rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        nav a:hover, nav a.active {
            background: rgba(255, 255, 255, 0.1);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #e58e0c;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #e0352b;
        }

        .btn-success {
            background-color: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background-color: #2daa4c;
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e0860b;
        }

        /* Dashboard Styles */
        .dashboard {
            padding: 30px 0;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        /* Content Sections */
        .content-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filters and Search */
        .filters {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            font-size: 1rem;
        }

        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-checkbox input {
            width: 18px;
            height: 18px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: var(--admin-primary);
            color: white;
            font-weight: 600;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .stock-low {
            color: var(--danger);
            font-weight: 600;
        }

        .stock-medium {
            color: var(--warning);
        }

        .stock-high {
            color: var(--accent);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .edit-btn {
            background-color: var(--primary);
            color: white;
        }

        .edit-btn:hover {
            background-color: var(--primary-dark);
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
        }

        .delete-btn:hover {
            background-color: #e0352b;
        }

        .stock-btn {
            background-color: var(--warning);
            color: white;
        }

        .stock-btn:hover {
            background-color: #e0860b;
        }

        /* Stock input */
        .stock-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination-btn:hover:not(.disabled) {
            background-color: var(--admin-primary);
            color: white;
            border-color: var(--admin-primary);
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-info {
            margin: 0 15px;
            color: var(--gray);
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(52, 199, 89, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .alert-error {
            background-color: rgba(255, 59, 48, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
        }

        .footer-section {
            flex: 1;
            min-width: 250px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .footer-section p {
            margin-bottom: 10px;
            color: #ccc;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #ccc;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {   
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .data-table {
                font-size: 0.9rem;
                display: block;
                overflow-x: auto;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        /* Rest of the CSS same as previous pages */
        /* ... */
    </style>
</head>
<body>
    <!-- Header (same as previous pages) -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="WAIS_LOGO.png" alt="WAISTORE Logo" style="height: 60px; width: 150px;">
                    <span>WAISTORE ADMIN</span>
                </div>
                <nav>
                    <ul>
                        <li><a href="admin.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                        <li><a href="admin_transactions.php" class="active"><i class="fas fa-cash-register"></i> Transactions</a></li>
                        <li><a href="admin_debts.php"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
                        <li><a href="admin_audit.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a></li>
                        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <span>Welcome, Admin</span>
                    <a href="admin_logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Transaction Management</h1>
                <p>View and manage all transactions across all stores</p>
            </div>

            <!-- Transaction Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_count']; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['total_amount'], 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['avg_amount'], 2); ?></div>
                    <div class="stat-label">Average Transaction</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['paid_count']; ?></div>
                    <div class="stat-label">Paid Transactions</div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Filters and Search -->
            <div class="filters">
                <div class="search-box">
                    <input type="text" id="search" placeholder="Search by customer or store..." value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </div>
                
                <select id="status-filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="debt" <?php echo $status_filter === 'debt' ? 'selected' : ''; ?>>Debt</option>
                </select>
                
                <select id="payment-filter" class="filter-select">
                    <option value="">All Payments</option>
                    <option value="cash" <?php echo $payment_filter === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="gcash" <?php echo $payment_filter === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                    <option value="credit" <?php echo $payment_filter === 'credit' ? 'selected' : ''; ?>>Credit</option>
                </select>
                
                <div class="date-inputs">
                    <input type="date" id="date-from" class="date-input" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                    <span>to</span>
                    <input type="date" id="date-to" class="date-input" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                </div>
                
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <button class="btn btn-success" onclick="exportTransactions()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>

            <!-- Transactions Table -->
            <div class="content-section">
                <div class="section-title">
                    <i class="fas fa-receipt"></i> All Transactions (<?php echo $total_transactions; ?>)
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Store</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-receipt" style="font-size: 3rem; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                    No transactions found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo $transaction['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['store_name'] ?? 'N/A'); ?>
                                    <br><small style="color: var(--gray);">@<?php echo htmlspecialchars($transaction['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['customer_name'] ?? 'Walk-in'); ?></td>
                                <td><?php echo $transaction['item_count']; ?> items</td>
                                <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                <td>
                                    <span style="text-transform: capitalize;">
                                        <?php echo $transaction['payment_method']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 3px 6px; border-radius: 4px; border: 1px solid #e0e0e0; margin-right: 5px;">
                                            <option value="paid" <?php echo $transaction['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="pending" <?php echo $transaction['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="debt" <?php echo $transaction['status'] === 'debt' ? 'selected' : ''; ?>>Debt</option>
                                        </select>
                                        <input type="hidden" name="action" value="update_status">
                                    </form>
                                    
                                    <button class="action-btn edit-btn" onclick="viewTransactionDetails(<?php echo $transaction['id']; ?>)"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <button type="submit" name="action" value="delete_transaction" class="action-btn delete-btn" title="Delete Transaction">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <button class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" 
                            onclick="changePage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                    
                    <button class="pagination-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" 
                            onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer (same as previous pages) -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>WAISTORE ADMIN</h3>
                    <p>Administration panel for WAISTORE sari-sari store management system</p>
                    <p>Managing stores, products, transactions and user accounts</p>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-envelope"></i> waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="admin.php" style="color: #ccc;">Dashboard</a></p>
                    <p><a href="admin_reports.php" style="color: #ccc;">System Reports</a></p>
                    <p><a href="#" style="color: #ccc;">Admin Guide</a></p>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WAISTORE Admin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Filter functions
        function applyFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            const payment = document.getElementById('payment-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const params = new URLSearchParams();
            
            if (search) params.set('search', search);
            if (status) params.set('status', status);
            if (payment) params.set('payment', payment);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            
            window.location.href = 'admin_transactions.php?' + params.toString();
        }

        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function exportTransactions() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            const payment = document.getElementById('payment-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            let url = 'admin_export.php?type=transactions';
            
            if (search) url += '&search=' + encodeURIComponent(search);
            if (status) url += '&status=' + encodeURIComponent(status);
            if (payment) url += '&payment=' + encodeURIComponent(payment);
            if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
            if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
            
            window.location.href = url;
        }

        function viewTransactionDetails(transactionId) {
            // In a real application, this would open a modal with transaction details
            window.location.href = 'admin_transaction_details.php?id=' + transactionId;
        }

        // Enter key support for search
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Set max date for date-to as today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date-to').max = today;
            document.getElementById('date-from').max = today;
        });
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>