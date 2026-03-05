<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

// Get stats for dashboard
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Total transactions
    $stmt = $pdo->query("SELECT COUNT(*) as total_transactions FROM transactions");
    $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total_transactions'];
    
    // Total debts
    $stmt = $pdo->query("SELECT SUM(amount) as total_debts FROM debts WHERE status != 'paid'");
    $total_debts = $stmt->fetch(PDO::FETCH_ASSOC)['total_debts'] ?? 0;
    
    // Recent users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent transactions
    $stmt = $pdo->query("SELECT t.*, u.store_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5");
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent debts
    $stmt = $pdo->query("SELECT d.*, u.store_name FROM debts d LEFT JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC LIMIT 5");
    $recent_debts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .users-icon {
            background-color: var(--admin-primary);
        }

        .products-icon {
            background-color: var(--accent);
        }

        .transactions-icon {
            background-color: var(--primary);
        }

        .debts-icon {
            background-color: var(--warning);
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-verified {
            background-color: rgba(52, 199, 89, 0.2);
            color: var(--accent);
        }

        .status-pending {
            background-color: rgba(255, 149, 0, 0.2);
            color: var(--warning);
        }

        .status-paid {
            background-color: rgba(52, 199, 89, 0.2);
            color: var(--accent);
        }

        .status-debt {
            background-color: rgba(255, 59, 48, 0.2);
            color: var(--danger);
        }

        .status-partial {
            background-color: rgba(255, 149, 0, 0.2);
            color: var(--warning);
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

        .view-btn {
            background-color: var(--accent);
            color: white;
        }

        .view-btn:hover {
            background-color: #2daa4c;
        }

        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            border-bottom: 3px solid var(--admin-primary);
            color: var(--admin-primary);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-lg {
            max-width: 800px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--admin-primary);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        /* Alert */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
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

        /* Loading */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--admin-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Chart Container */
        .chart-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.9rem;
                display: block;
                overflow-x: auto;
            }
            
            .footer-content {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
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
                        <li><a href="admin.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                        <li><a href="admin_transactions.php"><i class="fas fa-cash-register"></i> Transactions</a></li>
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
                <h1>Admin Dashboard</h1>
                <p>Welcome to the WAISTORE Admin Panel. Here's an overview of the system.</p>
            </div>

            <!-- Alert Messages -->
            <div id="alertMessage" class="alert"></div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Users</h3>
                        <div class="stat-icon users-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Registered store owners</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Products</h3>
                        <div class="stat-icon products-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Across all stores</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Transactions</h3>
                        <div class="stat-icon transactions-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_transactions; ?></div>
                    <div class="stat-label">All-time sales</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Outstanding Debts</h3>
                        <div class="stat-icon debts-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($total_debts, 2); ?></div>
                    <div class="stat-label">Across all customers</div>
                </div>
            </div>

            <!-- Tabs for different data sections -->
            <div class="tabs">
                <div class="tab active" data-tab="users">Recent Users</div>
                <div class="tab" data-tab="transactions">Recent Transactions</div>
                <div class="tab" data-tab="debts">Recent Debts</div>
            </div>

            <!-- Recent Users -->
            <div class="tab-content active" id="users-tab">
                <div class="content-section">
                    <h2 class="section-title"><i class="fas fa-users"></i> Recent Users</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Store Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_verified'] ? 'status-verified' : 'status-pending'; ?>">
                                        <?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="action-btn edit-btn" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="tab-content" id="transactions-tab">
                <div class="content-section">
                    <h2 class="section-title"><i class="fas fa-history"></i> Recent Transactions</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Store</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['customer_name'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                <td><?php echo ucfirst($transaction['payment_method']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="action-btn view-btn" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Debts -->
            <div class="tab-content" id="debts-tab">
                <div class="content-section">
                    <h2 class="section-title"><i class="fas fa-file-invoice-dollar"></i> Recent Debts</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Store</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_debts as $debt): 
                                $balance = $debt['amount'] - $debt['amount_paid'];
                            ?>
                            <tr>
                                <td><?php echo $debt['id']; ?></td>
                                <td><?php echo htmlspecialchars($debt['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($debt['customer_name']); ?></td>
                                <td>₱<?php echo number_format($debt['amount'], 2); ?></td>
                                <td>₱<?php echo number_format($debt['amount_paid'], 2); ?></td>
                                <td>₱<?php echo number_format($balance, 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $debt['status']; ?>">
                                        <?php echo ucfirst($debt['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($debt['due_date'])); ?></td>
                                <td class="action-buttons">
                                    <button class="action-btn view-btn" onclick="viewDebt(<?php echo $debt['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn edit-btn" onclick="addDebtPayment(<?php echo $debt['id']; ?>)">
                                        <i class="fas fa-money-bill"></i> Payment
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <button class="close-btn" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_full_name">Full Name</label>
                    <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_store_name">Store Name</label>
                    <input type="text" id="edit_store_name" name="store_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_is_verified">Verification Status</label>
                    <select id="edit_is_verified" name="is_verified" class="form-control">
                        <option value="0">Pending</option>
                        <option value="1">Verified</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateUserBtn">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Transaction Modal -->
    <div class="modal" id="viewTransactionModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="modal-title">Transaction Details</h3>
                <button class="close-btn" onclick="closeModal('viewTransactionModal')">&times;</button>
            </div>
            <div id="transactionDetails">
                <!-- Transaction details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- View Debt Modal -->
    <div class="modal" id="viewDebtModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="modal-title">Debt Details</h3>
                <button class="close-btn" onclick="closeModal('viewDebtModal')">&times;</button>
            </div>
            <div id="debtDetails">
                <!-- Debt details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add Debt Payment Modal -->
    <div class="modal" id="addPaymentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Debt Payment</h3>
                <button class="close-btn" onclick="closeModal('addPaymentModal')">&times;</button>
            </div>
            <form id="addPaymentForm">
                <input type="hidden" name="action" value="add_debt_payment">
                <input type="hidden" name="debt_id" id="payment_debt_id">
                <div class="form-group">
                    <label for="payment_amount">Payment Amount</label>
                    <input type="number" id="payment_amount" name="amount_paid" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" id="payment_date" name="payment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_notes">Notes</label>
                    <textarea id="payment_notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addPaymentModal')">Cancel</button>
                    <button type="submit" class="btn btn-success" id="addPaymentBtn">Add Payment</button>
                </div>
            </form>
        </div>
    </div>

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
                    <p><a href="#" style="color: #ccc;">System Logs</a></p>
                    <p><a href="#" style="color: #ccc;">Backup & Restore</a></p>
                    <p><a href="#" style="color: #ccc;">Admin Guide</a></p>
                    <p><a href="#" style="color: #ccc;">Privacy Policy</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WAISTORE Admin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Show alert message
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alertMessage');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // User management functions
        async function editUser(userId) {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_user&user_id=${userId}`
                });
                
                const user = await response.json();
                
                if (user.success) {
                    document.getElementById('edit_user_id').value = user.data.id;
                    document.getElementById('edit_username').value = user.data.username;
                    document.getElementById('edit_full_name').value = user.data.full_name;
                    document.getElementById('edit_store_name').value = user.data.store_name || '';
                    document.getElementById('edit_email').value = user.data.email;
                    document.getElementById('edit_is_verified').value = user.data.is_verified;
                    
                    openModal('editUserModal');
                } else {
                    showAlert('Failed to load user data', 'error');
                }
            } catch (error) {
                showAlert('Error loading user data', 'error');
            }
        }

        async function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                try {
                    const response = await fetch('admin_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete_user&user_id=${userId}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('User deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(result.message || 'Failed to delete user', 'error');
                    }
                } catch (error) {
                    showAlert('Error deleting user', 'error');
                }
            }
        }

        async function viewTransaction(transactionId) {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_transaction&transaction_id=${transactionId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('transactionDetails').innerHTML = result.html;
                    openModal('viewTransactionModal');
                } else {
                    showAlert('Failed to load transaction details', 'error');
                }
            } catch (error) {
                showAlert('Error loading transaction details', 'error');
            }
        }

        async function viewDebt(debtId) {
            try {
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=get_debt&debt_id=${debtId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('debtDetails').innerHTML = result.html;
                    openModal('viewDebtModal');
                } else {
                    showAlert('Failed to load debt details', 'error');
                }
            } catch (error) {
                showAlert('Error loading debt details', 'error');
            }
        }

        function addDebtPayment(debtId) {
            document.getElementById('payment_debt_id').value = debtId;
            document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
            openModal('addPaymentModal');
        }

        // Form submissions
        document.getElementById('editUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('updateUserBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Updating...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('User updated successfully');
                    closeModal('editUserModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'Failed to update user', 'error');
                }
            } catch (error) {
                showAlert('Error updating user', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        document.getElementById('addPaymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('addPaymentBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Processing...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                const response = await fetch('admin_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Payment added successfully');
                    closeModal('addPaymentModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message || 'Failed to add payment', 'error');
                }
            } catch (error) {
                showAlert('Error adding payment', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }\
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>