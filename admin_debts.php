<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query for debts
$query = "SELECT d.*, u.store_name, u.username,
          (SELECT SUM(amount_paid) FROM debt_payments dp WHERE dp.debt_id = d.id) as total_paid
          FROM debts d 
          LEFT JOIN users u ON d.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (d.customer_name LIKE ? OR u.store_name LIKE ? OR u.username LIKE ? OR d.phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($status_filter)) {
    $query .= " AND d.status = ?";
    $params[] = $status_filter;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM debts d LEFT JOIN users u ON d.user_id = u.id WHERE 1=1";
$count_params = [];
if (!empty($search)) {
    $count_query .= " AND (d.customer_name LIKE ? OR u.store_name LIKE ? OR u.username LIKE ? OR d.phone LIKE ?)";
    $search_term = "%$search%";
    $count_params = array_merge($count_params, [$search_term, $search_term, $search_term, $search_term]);
}
if (!empty($status_filter)) {
    $count_query .= " AND d.status = ?";
    $count_params[] = $status_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_debts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_debts / $limit);

// Add pagination to main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);


try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $debts = $stmt->fetchAll();

    // Get debt statistics
    $stats_stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            SUM(amount_paid) as total_paid,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'partial' THEN 1 END) as partial_count
        FROM debts
    ");
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle debt actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $debt_id = intval($_POST['debt_id'] ?? 0);

    if (!hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } elseif ($debt_id > 0) {
        switch ($action) {
            case 'update_status':
                $new_status = $_POST['status'] ?? '';
                if (in_array($new_status, ['paid', 'pending', 'partial'])) {
                    try {
                        $stmt = $pdo->prepare("UPDATE debts SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $debt_id]);
                        $success = "Debt status updated.";
                    } catch (PDOException $e) {
                        $error = "Failed to update debt: " . $e->getMessage();
                    }
                }
                break;

            case 'delete_debt':
                try {
                    $pdo->beginTransaction();

                    // Delete debt payments first
                    $pdo->prepare("DELETE FROM debt_payments WHERE debt_id = ?")->execute([$debt_id]);

                    // Delete the debt
                    $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ?");
                    $stmt->execute([$debt_id]);

                    $pdo->commit();
                    $success = "Debt record deleted successfully.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Failed to delete debt: " . $e->getMessage();
                }
                break;

            case 'add_payment':
                $amount = floatval($_POST['amount'] ?? 0);
                $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
                $payment_method = $_POST['payment_method'] ?? 'cash';

                if ($amount > 0) {
                    try {
                        $pdo->beginTransaction();

                        // Add payment record
                        $stmt = $pdo->prepare("INSERT INTO debt_payments (debt_id, amount_paid, payment_date, payment_method) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$debt_id, $amount, $payment_date, $payment_method]);

                        // Update debt status and amount paid
                        $debt_stmt = $pdo->prepare("
                            UPDATE debts 
                            SET amount_paid = amount_paid + ?, 
                                status = CASE 
                                    WHEN amount_paid + ? >= amount THEN 'paid'
                                    WHEN amount_paid + ? > 0 THEN 'partial'
                                    ELSE 'pending'
                                END
                            WHERE id = ?
                        ");
                        $debt_stmt->execute([$amount, $amount, $amount, $debt_id]);

                        $pdo->commit();
                        $success = "Payment recorded successfully.";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $error = "Failed to record payment: " . $e->getMessage();
                    }
                }
                break;
        }
    }

    // Redirect to avoid form resubmission
    header("Location: admin_debts.php?" . ($_SERVER['QUERY_STRING'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Debt Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        /* Debt-specific styles */
        .debt-overdue {
            background-color: rgba(255, 59, 48, 0.05);
        }

        .debt-due-soon {
            background-color: rgba(255, 149, 0, 0.05);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--accent);
            transition: width 0.3s;
        }

        .payment-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }

        .payment-input {
            padding: 5px 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .amount-due {
            font-weight: 600;
            color: var(--danger);
        }

        .amount-paid {
            font-weight: 600;
            color: var(--accent);
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

        nav a:hover,
        nav a.active {
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

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-dark));
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: rgba(52, 199, 89, 0.2);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .status-pending {
            background-color: rgba(255, 149, 0, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status-partial {
            background-color: rgba(0, 122, 255, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        /* Action Button Variants */
        .success-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .success-btn:hover {
            background-color: #2daa4c;
        }

        /* Form Enhancements */
        .payment-form {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .payment-input {
            padding: 4px 6px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.8rem;
            background: white;
        }

        /* Responsive adjustments for payment form */
        @media (max-width: 768px) {
            .payment-form {
                flex-direction: column;
                align-items: stretch;
            }

            .payment-input {
                width: 100%;
            }
        }

        /* Additional utility classes */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Rest of CSS same as previous pages */
    </style>
</head>

<body>
    <!-- Header -->
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
                        <li><a href="admin_transactions.php"><i class="fas fa-cash-register"></i> Transactions</a></li>
                        <li><a href="admin_debts.php" class="active"><i class="fas fa-file-invoice-dollar"></i>
                                Utang</a></li>
                        <li><a href="admin_audit.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a></li>
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
                <h1>Debt Management</h1>
                <p>Manage customer debts and payment tracking</p>
            </div>

            <!-- Debt Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_count']; ?></div>
                    <div class="stat-label">Total Debts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['total_amount'], 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($stats['total_paid'], 2); ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        ₱<?php echo number_format($stats['total_amount'] - $stats['total_paid'], 2); ?></div>
                    <div class="stat-label">Outstanding</div>
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
                    <input type="text" id="search" placeholder="Search by customer or store..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </div>

                <select id="status-filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="partial" <?php echo $status_filter === 'partial' ? 'selected' : ''; ?>>Partial</option>
                </select>

                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>

                <button class="btn btn-success" onclick="exportDebts()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>

            <!-- Debts Table -->
            <div class="content-section">
                <div class="section-title">
                    <i class="fas fa-file-invoice-dollar"></i> All Debts (<?php echo $total_debts; ?>)
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Store</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($debts)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-file-invoice-dollar"
                                        style="font-size: 3rem; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                    No debts found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($debts as $debt):
                                $total_paid = $debt['total_paid'] ?? $debt['amount_paid'];
                                $remaining = $debt['amount'] - $total_paid;
                                $progress = ($total_paid / $debt['amount']) * 100;

                                // Check if debt is overdue
                                $due_date = new DateTime($debt['due_date']);
                                $today = new DateTime();
                                $is_overdue = $due_date < $today && $debt['status'] !== 'paid';
                                $is_due_soon = $due_date->diff($today)->days <= 3 && !$is_overdue && $debt['status'] !== 'paid';

                                $row_class = '';
                                if ($is_overdue)
                                    $row_class = 'debt-overdue';
                                elseif ($is_due_soon)
                                    $row_class = 'debt-due-soon';
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>#<?php echo $debt['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($debt['store_name'] ?? 'N/A'); ?>
                                        <br><small
                                            style="color: var(--gray);">@<?php echo htmlspecialchars($debt['username']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($debt['customer_name']); ?></strong>
                                        <?php if ($debt['notes']): ?>
                                            <br><small
                                                style="color: var(--gray);"><?php echo htmlspecialchars($debt['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($debt['phone'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($debt['amount'], 2); ?></td>
                                    <td>
                                        <div class="amount-paid">₱<?php echo number_format($total_paid, 2); ?></div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                        <div class="amount-due">Due: ₱<?php echo number_format($remaining, 2); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($debt['due_date'])); ?>
                                        <?php if ($is_overdue): ?>
                                            <br><small style="color: var(--danger);">Overdue</small>
                                        <?php elseif ($is_due_soon): ?>
                                            <br><small style="color: var(--warning);">Due Soon</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $debt['status']; ?>">
                                            <?php echo ucfirst($debt['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($debt['status'] !== 'paid'): ?>
                                            <form method="POST" class="payment-form">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                                <input type="hidden" name="debt_id" value="<?php echo $debt['id']; ?>">
                                                <input type="number" name="amount" class="payment-input" placeholder="Amount"
                                                    min="0.01" max="<?php echo $remaining; ?>" step="0.01" required
                                                    style="width: 80px;">
                                                <select name="payment_method" class="payment-input" style="width: 90px;">
                                                    <option value="cash">Cash</option>
                                                    <option value="gcash">GCash</option>
                                                    <option value="maya">Maya</option>
                                                    <option value="bank">Bank</option>
                                                </select>
                                                <button type="submit" name="action" value="add_payment"
                                                    class="action-btn success-btn" title="Add Payment">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <div style="display: flex; gap: 5px; margin-top: 5px;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                                <input type="hidden" name="debt_id" value="<?php echo $debt['id']; ?>">
                                                <select name="status" onchange="this.form.submit()"
                                                    style="padding: 3px 6px; border-radius: 4px; border: 1px solid #e0e0e0; margin-right: 5px;">
                                                    <option value="paid" <?php echo $debt['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                    <option value="pending" <?php echo $debt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="partial" <?php echo $debt['status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                                </select>
                                                <input type="hidden" name="action" value="update_status">
                                            </form>

                                            <button class="action-btn edit-btn"
                                                onclick="viewDebtDetails(<?php echo $debt['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this debt record? This action cannot be undone.');">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                                <input type="hidden" name="debt_id" value="<?php echo $debt['id']; ?>">
                                                <button type="submit" name="action" value="delete_debt"
                                                    class="action-btn delete-btn" title="Delete Debt">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

    <!-- Footer -->
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
            const params = new URLSearchParams();

            if (search) params.set('search', search);
            if (status) params.set('status', status);

            window.location.href = 'admin_debts.php?' + params.toString();
        }

        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function exportDebts() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            let url = 'admin_export.php?type=debts';

            if (search) url += '&search=' + encodeURIComponent(search);
            if (status) url += '&status=' + encodeURIComponent(status);

            window.location.href = url;
        }

        function viewDebtDetails(debtId) {
            // In a real application, this would open a modal with debt details
            window.location.href = 'admin_debt_details.php?id=' + debtId;
        }

        // Enter key support for search
        document.getElementById('search').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Set default payment date to today
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            const paymentInputs = document.querySelectorAll('input[name="payment_date"]');
            paymentInputs.forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>