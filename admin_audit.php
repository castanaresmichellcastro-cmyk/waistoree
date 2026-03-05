<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

// Create audit_logs table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        admin_id INT NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created (created_at),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Table might already exist
}

// Filters
$action_filter = $_GET['action'] ?? '';
$entity_filter = $_GET['entity'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT al.*, u.username as user_name, u.store_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($action_filter)) {
    $query .= " AND al.action = ?";
    $count_query .= " AND al.action = ?";
    $params[] = $action_filter;
}

if (!empty($entity_filter)) {
    $query .= " AND al.entity_type = ?";
    $count_query .= " AND al.entity_type = ?";
    $params[] = $entity_filter;
}

if (!empty($date_from)) {
    $query .= " AND al.created_at >= ?";
    $count_query .= " AND al.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $query .= " AND al.created_at <= ?";
    $count_query .= " AND al.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

if (!empty($search)) {
    $query .= " AND (al.details LIKE ? OR u.username LIKE ? OR u.store_name LIKE ?)";
    $count_query .= " AND (al.details LIKE ? OR u.username LIKE ? OR u.store_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Get total
try {
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_logs = $stmt->fetch()['total'];
    $total_pages = ceil($total_logs / $limit);
} catch (PDOException $e) {
    $total_logs = 0;
    $total_pages = 0;
}

// Get logs
$query .= " ORDER BY al.created_at DESC LIMIT $limit OFFSET $offset";
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $logs = [];
    $error = "Database error: " . $e->getMessage();
}

// Get distinct actions and entities for filter dropdowns
try {
    $actions_stmt = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
    $available_actions = $actions_stmt->fetchAll(PDO::FETCH_COLUMN);

    $entities_stmt = $pdo->query("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type");
    $available_entities = $entities_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_actions = [];
    $available_entities = [];
}

// Get summary stats
try {
    $today_count = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $week_count = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
    $login_count = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'login' AND DATE(created_at) = CURDATE()")->fetchColumn();
    $admin_count = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE admin_id IS NOT NULL AND DATE(created_at) = CURDATE()")->fetchColumn();
} catch (PDOException $e) {
    $today_count = 0;
    $week_count = 0;
    $login_count = 0;
    $admin_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE Admin - Audit Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background-color: var(--admin-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--admin-dark);
        }

        .btn-success {
            background-color: var(--accent);
            color: white;
        }

        .dashboard {
            padding: 30px 0;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
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

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
        }

        /* Filters */
        .filters {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray);
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: var(--admin-primary);
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: rgba(0, 0, 0, 0.02);
        }

        .action-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .action-login {
            background: rgba(52, 199, 89, 0.15);
            color: #28a745;
        }

        .action-logout {
            background: rgba(142, 142, 147, 0.15);
            color: var(--gray);
        }

        .action-create {
            background: rgba(45, 91, 255, 0.15);
            color: var(--primary);
        }

        .action-update {
            background: rgba(255, 158, 26, 0.15);
            color: var(--secondary);
        }

        .action-delete {
            background: rgba(255, 59, 48, 0.15);
            color: var(--danger);
        }

        .action-sale {
            background: rgba(142, 68, 173, 0.15);
            color: var(--admin-primary);
        }

        .action-payment {
            background: rgba(52, 199, 89, 0.15);
            color: var(--accent);
        }

        .action-default {
            background: rgba(142, 142, 147, 0.1);
            color: var(--gray);
        }

        .entity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            background: rgba(0, 0, 0, 0.06);
            color: var(--dark);
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
            background: var(--admin-primary);
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
            background: rgba(52, 199, 89, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .alert-error {
            background: rgba(255, 59, 48, 0.1);
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

        .footer-bottom {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #ccc;
        }

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
                grid-template-columns: 1fr 1fr;
            }

            .data-table {
                font-size: 0.85rem;
                display: block;
                overflow-x: auto;
            }

            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
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
                        <li><a href="admin_audit.php" class="active"><i class="fas fa-clipboard-list"></i> Audit
                                Logs</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <span>Welcome, Admin</span>
                    <a href="admin_logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-clipboard-list"></i> Audit Logs</h1>
                <p>Track all system activities — logins, transactions, inventory changes, and admin actions.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Today's Activity</h3>
                        <div class="stat-icon" style="background: var(--admin-primary);"><i
                                class="fas fa-calendar-day"></i></div>
                    </div>
                    <div class="stat-value"><?php echo $today_count; ?></div>
                    <div class="stat-label">Actions logged today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>This Week</h3>
                        <div class="stat-icon" style="background: var(--primary);"><i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $week_count; ?></div>
                    <div class="stat-label">Actions this week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Logins Today</h3>
                        <div class="stat-icon" style="background: var(--accent);"><i class="fas fa-sign-in-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $login_count; ?></div>
                    <div class="stat-label">User logins today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Admin Actions</h3>
                        <div class="stat-icon" style="background: var(--warning);"><i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $admin_count; ?></div>
                    <div class="stat-label">Admin actions today</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="filter-group search-box">
                    <label>Search</label>
                    <input type="text" id="search" placeholder="Search details, user, store..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Action</label>
                    <select id="action-filter">
                        <option value="">All Actions</option>
                        <?php foreach ($available_actions as $act): ?>
                            <option value="<?php echo $act; ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                                <?php echo ucfirst($act); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Entity</label>
                    <select id="entity-filter">
                        <option value="">All Entities</option>
                        <?php foreach ($available_entities as $ent): ?>
                            <option value="<?php echo $ent; ?>" <?php echo $entity_filter === $ent ? 'selected' : ''; ?>>
                                <?php echo ucfirst($ent); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" id="date-from" value="<?php echo $date_from; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" id="date-to" value="<?php echo $date_to; ?>">
                </div>
                <button class="btn btn-primary" onclick="applyFilters()" style="align-self: flex-end;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button class="btn btn-success" onclick="exportLogs()" style="align-self: flex-end;">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>

            <!-- Audit Logs Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User / Store</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px;">
                                <i class="fas fa-clipboard-list"
                                    style="font-size:3rem; color:var(--gray); display:block; margin-bottom:10px;"></i>
                                <p style="color:var(--gray);">No audit logs found. Activity will appear here as users
                                    interact with the system.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#<?php echo $log['id']; ?></td>
                                <td>
                                    <div style="line-height:1.3">
                                        <strong><?php echo date('M j, Y', strtotime($log['created_at'])); ?></strong><br>
                                        <small
                                            style="color:var(--gray);"><?php echo date('g:i:s A', strtotime($log['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div style="line-height:1.3">
                                        <?php if ($log['user_name']): ?>
                                            <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                            <small
                                                style="color:var(--gray);"><?php echo htmlspecialchars($log['store_name'] ?? ''); ?></small>
                                        <?php elseif ($log['admin_id']): ?>
                                            <strong style="color:var(--admin-primary);">Admin</strong>
                                        <?php else: ?>
                                            <span style="color:var(--gray);">System</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $action = strtolower($log['action']);
                                    $action_class = 'action-default';
                                    if (strpos($action, 'login') !== false)
                                        $action_class = 'action-login';
                                    elseif (strpos($action, 'logout') !== false)
                                        $action_class = 'action-logout';
                                    elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false)
                                        $action_class = 'action-create';
                                    elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false)
                                        $action_class = 'action-update';
                                    elseif (strpos($action, 'delete') !== false)
                                        $action_class = 'action-delete';
                                    elseif (strpos($action, 'sale') !== false || strpos($action, 'transaction') !== false)
                                        $action_class = 'action-sale';
                                    elseif (strpos($action, 'payment') !== false || strpos($action, 'pay') !== false)
                                        $action_class = 'action-payment';
                                    ?>
                                    <span
                                        class="action-tag <?php echo $action_class; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td><span class="entity-badge"><?php echo htmlspecialchars($log['entity_type']); ?>
                                        <?php echo $log['entity_id'] ? '#' . $log['entity_id'] : ''; ?></span></td>
                                <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                    title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($log['details'] ?? '—'); ?>
                                </td>
                                <td><small
                                        style="color:var(--gray);"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></small>
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
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        (<?php echo $total_logs; ?> total)</span>
                    <button class="pagination-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                        onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024-2026 WAISTORE Admin &mdash; Grocery Store Management System</p>
            </div>
        </div>
    </footer>

    <script>
        function applyFilters() {
            const params = new URLSearchParams();
            const search = document.getElementById('search').value;
            const action = document.getElementById('action-filter').value;
            const entity = document.getElementById('entity-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;

            if (search) params.set('search', search);
            if (action) params.set('action', action);
            if (entity) params.set('entity', entity);
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo) params.set('date_to', dateTo);

            window.location.href = 'admin_audit.php?' + params.toString();
        }

        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('type', 'audit');
            window.location.href = 'admin_export.php?' + params.toString();
        }

        document.getElementById('search').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') applyFilters();
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>