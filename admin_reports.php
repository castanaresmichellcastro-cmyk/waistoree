<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

// Default date range (last 30 days)
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

// Initialize variables to prevent undefined variable warnings
$sales_data = [];
$top_products = [];
$user_activity = [];
$inventory_data = [];
$overall_stats = [
    'total_users' => 0,
    'total_products' => 0,
    'total_transactions' => 0,
    'total_sales' => 0,
    'total_debts' => 0,
    'total_debt_amount' => 0
];
$error = null;

try {
    // Sales Report
    if ($report_type === 'sales') {
        $sales_stmt = $pdo->prepare("
            SELECT 
                DATE(t.created_at) as date,
                COUNT(*) as transaction_count,
                SUM(t.total_amount) as total_sales,
                AVG(t.total_amount) as avg_sale
            FROM transactions t
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY DATE(t.created_at)
            ORDER BY date DESC
        ");
        $sales_stmt->execute([$date_from, $date_to]);
        $sales_data = $sales_stmt->fetchAll();
        
        // Top products - Fixed GROUP BY clause
        $products_stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.category,
                u.store_name,
                SUM(ti.quantity) as total_sold,
                SUM(ti.quantity * ti.price) as total_revenue
            FROM transaction_items ti
            JOIN products p ON ti.product_id = p.id
            JOIN transactions t ON ti.transaction_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY p.id, p.name, p.category, u.store_name
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $products_stmt->execute([$date_from, $date_to]);
        $top_products = $products_stmt->fetchAll();
    }
    
    // User Activity Report - Fixed GROUP BY clause
    if ($report_type === 'users') {
        $users_stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.store_name,
                u.full_name,
                u.email,
                u.created_at,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(t.total_amount), 0) as total_sales
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY u.id, u.username, u.store_name, u.full_name, u.email, u.created_at
            ORDER BY total_sales DESC
        ");
        $users_stmt->execute([$date_from, $date_to]);
        $user_activity = $users_stmt->fetchAll();
    }
    
    // Inventory Report - This query doesn't need GROUP BY fix as it doesn't use GROUP BY
    if ($report_type === 'inventory') {
        $inventory_stmt = $pdo->prepare("
            SELECT 
                p.name,
                p.category,
                u.store_name,
                p.stock,
                p.price,
                p.created_at,
                (SELECT COUNT(*) FROM transaction_items ti 
                 JOIN transactions t ON ti.transaction_id = t.id 
                 WHERE ti.product_id = p.id AND DATE(t.created_at) BETWEEN ? AND ?) as times_sold
            FROM products p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.stock ASC, times_sold DESC
        ");
        $inventory_stmt->execute([$date_from, $date_to]);
        $inventory_data = $inventory_stmt->fetchAll();
    }
    
    // Overall statistics - This query doesn't need GROUP BY fix as it uses aggregates without GROUP BY
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT p.id) as total_products,
            COUNT(DISTINCT t.id) as total_transactions,
            COALESCE(SUM(t.total_amount), 0) as total_sales,
            COUNT(DISTINCT d.id) as total_debts,
            COALESCE(SUM(d.amount), 0) as total_debt_amount
        FROM users u
        LEFT JOIN products p ON u.id = p.user_id
        LEFT JOIN transactions t ON u.id = t.user_id AND DATE(t.created_at) BETWEEN ? AND ?
        LEFT JOIN debts d ON u.id = d.user_id
    ");
    $stats_stmt->execute([$date_from, $date_to]);
    $overall_stats = $stats_stmt->fetch() ?: $overall_stats;
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Reports Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Reports & Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Your existing CSS styles remain the same */
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

        /* Report-specific styles */
        .report-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-dark));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .report-controls {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .chart-container {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            height: 400px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .report-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .report-tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .report-tab.active {
            border-bottom: 3px solid var(--admin-primary);
            color: var(--admin-primary);
        }
        
        .report-content {
            display: none;
        }
        
        .report-content.active {
            display: block;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .data-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
        }
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
                        <li><a href="admin_debts.php"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
                        <li><a href="admin_audit.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a></li>
                        <li><a href="admin_reports.php" class="active"><i class="fas fa-chart-line"></i> Reports</a></li>
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
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="report-header">
                <h1>Reports & Analytics</h1>
                <p>Comprehensive insights and analytics for WAISTORE system</p>
            </div>

            <!-- Report Controls -->
            <div class="report-controls">
                <select id="report-type" class="filter-select" onchange="changeReportType()">
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="users" <?php echo $report_type === 'users' ? 'selected' : ''; ?>>User Activity</option>
                    <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                </select>
                
                <input type="date" id="date-from" class="date-input" value="<?php echo htmlspecialchars($date_from); ?>">
                <span>to</span>
                <input type="date" id="date-to" class="date-input" value="<?php echo htmlspecialchars($date_to); ?>">
                
                <button class="btn btn-primary" onclick="applyDateRange()">
                    <i class="fas fa-filter"></i> Apply Date Range
                </button>
                
                <button class="btn btn-success" onclick="exportReport()">
                    <i class="fas fa-download"></i> Export Report
                </button>
                
                <button class="btn btn-warning" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Overall Statistics -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $overall_stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $overall_stats['total_products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $overall_stats['total_transactions']; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">₱<?php echo number_format($overall_stats['total_sales'], 2); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>

            <!-- Sales Report -->
            <div class="report-content <?php echo $report_type === 'sales' ? 'active' : ''; ?>" id="sales-report">
                <div class="section-title">
                    <i class="fas fa-chart-bar"></i> Sales Report
                </div>
                
                <div class="data-grid">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                    
                    <div>
                        <div class="section-title" style="font-size: 1.2rem;">
                            <i class="fas fa-cube"></i> Top Selling Products
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Store</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 15px;">
                                            No sales data available
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($top_products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br><small style="color: var(--gray);"><?php echo htmlspecialchars($product['category']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['store_name']); ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                        <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sales Data Table -->
                <div class="content-section">
                    <div class="section-title" style="font-size: 1.2rem;">
                        <i class="fas fa-table"></i> Daily Sales Data
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Total Sales</th>
                                <th>Average Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales_data)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 15px;">
                                        No sales data available for the selected period
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($sales_data as $sale): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($sale['date'])); ?></td>
                                    <td><?php echo $sale['transaction_count']; ?></td>
                                    <td>₱<?php echo number_format($sale['total_sales'], 2); ?></td>
                                    <td>₱<?php echo number_format($sale['avg_sale'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User Activity Report -->
            <div class="report-content <?php echo $report_type === 'users' ? 'active' : ''; ?>" id="users-report">
                <div class="section-title">
                    <i class="fas fa-users"></i> User Activity Report
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Transactions</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_activity)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-users" style="font-size: 3rem; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                    No user activity data available
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($user_activity as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['store_name'] ?? 'N/A'); ?></strong>
                                    <br><small style="color: var(--gray);">@<?php echo htmlspecialchars($user['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['transaction_count']; ?></td>
                                <td>₱<?php echo number_format($user['total_sales'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Inventory Report -->
            <div class="report-content <?php echo $report_type === 'inventory' ? 'active' : ''; ?>" id="inventory-report">
                <div class="section-title">
                    <i class="fas fa-boxes"></i> Inventory Report
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Store</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Times Sold</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory_data)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-boxes" style="font-size: 3rem; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                    No inventory data available
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($inventory_data as $item): 
                                $stock_status = $item['stock'] <= 5 ? 'Low Stock' : ($item['stock'] <= 10 ? 'Medium Stock' : 'Good Stock');
                                $status_class = $item['stock'] <= 5 ? 'stock-low' : ($item['stock'] <= 10 ? 'stock-medium' : 'stock-high');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td class="<?php echo $status_class; ?>"><?php echo $item['stock']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['times_sold']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $stock_status)); ?>">
                                        <?php echo $stock_status; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        // Report type switching
        function changeReportType() {
            const reportType = document.getElementById('report-type').value;
            const params = new URLSearchParams(window.location.search);
            params.set('report_type', reportType);
            window.location.href = 'admin_reports.php?' + params.toString();
        }

        function applyDateRange() {
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const reportType = document.getElementById('report-type').value;
            const params = new URLSearchParams();
            
            params.set('report_type', reportType);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);
            
            window.location.href = 'admin_reports.php?' + params.toString();
        }

        function exportReport() {
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            const reportType = document.getElementById('report-type').value;
            let url = 'admin_export.php?type=report&report_type=' + reportType;
            
            if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
            if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
            
            window.location.href = url;
        }

        function printReport() {
            window.print();
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($report_type === 'sales' && !empty($sales_data)): ?>
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($sale) { return "'" . date('M j', strtotime($sale['date'])) . "'"; }, array_reverse($sales_data))); ?>],
                    datasets: [{
                        label: 'Daily Sales',
                        data: [<?php echo implode(',', array_map(function($sale) { return $sale['total_sales']; }, array_reverse($sales_data))); ?>],
                        borderColor: '<?php echo "var(--admin-primary)"; ?>',
                        backgroundColor: 'rgba(142, 68, 173, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Sales: ₱' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            // Set max date for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date-to').max = today;
            document.getElementById('date-from').max = today;
        });
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>