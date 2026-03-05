<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';
require_once 'audit_logger.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle user actions
$success = null;
$error = null;
$temp_password_display = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (!hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        switch ($action) {
            case 'create_user':
                $new_username = trim($_POST['new_username'] ?? '');
                $new_fullname = trim($_POST['new_fullname'] ?? '');
                $new_email = trim($_POST['new_email'] ?? '');
                $new_store_name = trim($_POST['new_store_name'] ?? '');
                $new_phone = trim($_POST['new_phone'] ?? '');
                $new_password = $_POST['new_password'] ?? '';

                if (empty($new_username) || empty($new_fullname) || empty($new_email) || empty($new_password)) {
                    $error = "Username, full name, email, and password are required.";
                } elseif (strlen($new_password) < 6) {
                    $error = "Password must be at least 6 characters.";
                } else {
                    try {
                        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $check->execute([$new_username, $new_email]);
                        if ($check->fetch()) {
                            $error = "Username or email already exists.";
                        } else {
                            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, store_name, phone, password, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                            $stmt->execute([$new_username, $new_fullname, $new_email, $new_store_name, $new_phone, $hashed]);
                            $new_id = $pdo->lastInsertId();
                            logAudit($pdo, 'create_user', 'user', $new_id, 'Admin created user: "' . $new_username . '" for store: "' . $new_store_name . '"');
                            $success = 'User "' . htmlspecialchars($new_username) . '" created successfully! They can now log in.';
                        }
                    } catch (PDOException $e) {
                        $error = "Failed to create user: " . $e->getMessage();
                    }
                }
                break;

            case 'reset_password':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    try {
                        $temp_password = 'Wai' . rand(1000, 9999) . '!';
                        $hashed = password_hash($temp_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$hashed, $user_id]);

                        $uname_stmt = $pdo->prepare("SELECT username, store_name FROM users WHERE id = ?");
                        $uname_stmt->execute([$user_id]);
                        $u = $uname_stmt->fetch();

                        logAudit($pdo, 'reset_password', 'user', $user_id, 'Admin reset password for: "' . ($u['username'] ?? '') . '"');
                        $temp_password_display = $temp_password;
                        $success = 'Password for "<strong>' . htmlspecialchars($u['username'] ?? '') . '</strong>" (' . htmlspecialchars($u['store_name'] ?? '') . ') has been reset. New temporary password is shown below.';
                    } catch (PDOException $e) {
                        $error = "Failed to reset password: " . $e->getMessage();
                    }
                }
                break;

            case 'toggle_verification':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET is_verified = NOT is_verified WHERE id = ?");
                        $stmt->execute([$user_id]);
                        logAudit($pdo, 'toggle_verification', 'user', $user_id, 'Admin toggled verification status');
                        $success = "User verification status updated.";
                    } catch (PDOException $e) {
                        $error = "Failed to update user: " . $e->getMessage();
                    }
                }
                break;

            case 'delete_user':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    try {
                        $del_stmt = $pdo->prepare("SELECT username, store_name FROM users WHERE id = ?");
                        $del_stmt->execute([$user_id]);
                        $del_user = $del_stmt->fetch();

                        $pdo->beginTransaction();
                        $pdo->prepare("DELETE FROM debts WHERE user_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM transaction_items WHERE transaction_id IN (SELECT id FROM transactions WHERE user_id = ?)")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM products WHERE user_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                        $pdo->commit();

                        logAudit($pdo, 'delete_user', 'user', $user_id, 'Admin deleted user: "' . ($del_user['username'] ?? '') . '" from store: "' . ($del_user['store_name'] ?? '') . '"');
                        $success = "User and all related data deleted.";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $error = "Failed to delete user: " . $e->getMessage();
                    }
                }
                break;
        }

        // Redirect for non-display actions
        if (!in_array($action, ['reset_password', 'create_user']) || $error) {
            header("Location: admin_users.php?" . http_build_query($_GET));
            exit();
        }
    }
}

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$count_q = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (username LIKE ? OR full_name LIKE ? OR store_name LIKE ? OR email LIKE ?)";
    $count_q .= " AND (username LIKE ? OR full_name LIKE ? OR store_name LIKE ? OR email LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s, $s];
}
if ($status_filter === 'verified') {
    $query .= " AND is_verified = 1";
    $count_q .= " AND is_verified = 1";
} elseif ($status_filter === 'pending') {
    $query .= " AND is_verified = 0";
    $count_q .= " AND is_verified = 0";
}

$cs = $pdo->prepare($count_q);
$cs->execute($params);
$total_users = $cs->fetch()['total'];
$total_pages = ceil($total_users / $limit);

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $error ?? ("Database error: " . $e->getMessage());
    $users = [];
}

function maskEmail($email)
{
    if (empty($email))
        return 'N/A';
    $parts = explode('@', $email);
    if (count($parts) !== 2)
        return '***';
    return substr($parts[0], 0, 2) . str_repeat('*', max(3, strlen($parts[0]) - 2)) . '@' . $parts[1];
}
function maskPhone($phone)
{
    if (empty($phone))
        return 'N/A';
    $c = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($c) < 4)
        return '***';
    return substr($c, 0, 4) . str_repeat('*', strlen($c) - 6) . substr($c, -2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE Admin - User Management</title>
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
            background: rgba(255, 255, 255, 0.15);
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--admin-dark);
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
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #e0352b;
        }

        .btn-success {
            background: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background: #2daa4c;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            opacity: 0.9;
        }

        .dashboard {
            padding: 30px 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .dashboard-header h1 {
            font-size: 2rem;
        }

        .filters {
            background: var(--card-bg);
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
            background: var(--admin-primary);
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

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-verified {
            background: rgba(52, 199, 89, 0.2);
            color: var(--accent);
        }

        .status-pending {
            background: rgba(255, 149, 0, 0.2);
            color: var(--warning);
        }

        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 5px 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .edit-btn:hover {
            background: var(--primary-dark);
        }

        .delete-btn {
            background: var(--danger);
            color: white;
        }

        .delete-btn:hover {
            background: #e0352b;
        }

        .toggle-btn {
            background: var(--warning);
            color: white;
        }

        .reset-btn {
            background: #6C3483;
            color: white;
        }

        .reset-btn:hover {
            background: var(--admin-primary);
        }

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

        /* Temp Password Display */
        .temp-password-box {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            border: 2px solid var(--warning);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .temp-password-box .password-value {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 3px;
            background: var(--dark);
            color: #FFD700;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }

        .temp-password-box .warning-text {
            color: var(--danger);
            font-weight: 600;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        /* Modal - use .admin-modal to avoid conflict with waistore-global.css .modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex !important;
        }

        .admin-modal {
            display: block !important;
            background: white !important;
            border-radius: 16px;
            width: 95%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
            position: relative;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            font-size: 1.3rem;
            color: var(--admin-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body .form-group {
            margin-bottom: 18px;
        }

        .modal-body label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .modal-body input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .modal-body input:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 4px;
        }

        /* Privacy indicator */
        .privacy-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            background: rgba(142, 68, 173, 0.1);
            color: var(--admin-primary);
            padding: 2px 6px;
            border-radius: 4px;
        }

        footer {
            background: var(--dark);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }

        .footer-bottom {
            text-align: center;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            nav ul {
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
                font-size: 0.85rem;
            }

            .data-table {
                display: block;
                overflow-x: auto;
                font-size: 0.85rem;
            }

            .filters {
                flex-direction: column;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
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
                        <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="admin_products.php"><i class="fas fa-box"></i> Products</a></li>
                        <li><a href="admin_transactions.php"><i class="fas fa-cash-register"></i> Transactions</a></li>
                        <li><a href="admin_debts.php"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
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

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    <p style="color: var(--gray);">Create, manage, and reset passwords for grocery store owners</p>
                </div>
                <button class="btn btn-primary" onclick="openCreateModal()"
                    style="font-size: 1rem; padding: 12px 24px;">
                    <i class="fas fa-user-plus"></i> Create New User
                </button>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Temp Password Display -->
            <?php if ($temp_password_display): ?>
                <div class="temp-password-box">
                    <h3><i class="fas fa-key"></i> New Temporary Password</h3>
                    <p>Share this password securely with the store owner. They should change it after logging in.</p>
                    <div class="password-value" id="tempPwd"><?php echo htmlspecialchars($temp_password_display); ?></div>
                    <br>
                    <button class="btn btn-primary" onclick="copyPassword()"><i class="fas fa-copy"></i> Copy
                        Password</button>
                    <p class="warning-text"><i class="fas fa-exclamation-triangle"></i> This password will NOT be shown
                        again after you leave this page!</p>
                </div>
            <?php endif; ?>

            <!-- Privacy Notice -->
            <div
                style="background: rgba(142,68,173,0.05); border: 1px solid rgba(142,68,173,0.2); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shield-alt" style="color: var(--admin-primary); font-size: 1.2rem;"></i>
                <div>
                    <strong style="color: var(--admin-primary);">Privacy Protection Active</strong>
                    <span style="color: var(--gray); font-size: 0.85rem;"> — Email and phone numbers are masked. Hover
                        to reveal temporarily. All admin actions are logged in the Audit trail.</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="search-box">
                    <input type="text" id="search" placeholder="Search by username, name, or store..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </div>
                <select id="status-filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified
                    </option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <button class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-filter"></i> Filter</button>
            </div>

            <!-- Users Table -->
            <div style="margin-bottom: 10px; color: var(--gray); font-size: 0.9rem;">
                <i class="fas fa-users"></i> Showing <?php echo count($users); ?> of <?php echo $total_users; ?> users
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Grocery Store</th>
                        <th>Email <span class="privacy-badge"><i class="fas fa-lock"></i> Masked</span></th>
                        <th>Phone <span class="privacy-badge"><i class="fas fa-lock"></i> Masked</span></th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users"
                                    style="font-size: 3rem; color: var(--gray); display: block; margin-bottom: 10px;"></i>
                                No users found. Click "Create New User" to add a grocery store owner.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['store_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="masked-data"
                                        title="<?php echo htmlspecialchars($user['email']); ?>"><?php echo maskEmail($user['email']); ?></span>
                                </td>
                                <td>
                                    <span class="masked-data"
                                        title="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"><?php echo maskPhone($user['phone'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?php echo $user['is_verified'] ? 'status-verified' : 'status-pending'; ?>">
                                        <?php echo $user['is_verified'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <!-- Toggle Verification -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="toggle_verification"
                                            class="action-btn toggle-btn"
                                            title="<?php echo $user['is_verified'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $user['is_verified'] ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Reset Password -->
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('Reset password for <?php echo htmlspecialchars($user['username']); ?>? A new temporary password will be generated.');">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="reset_password" class="action-btn reset-btn"
                                            title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('⚠️ DELETE user <?php echo htmlspecialchars($user['username']); ?> and ALL their data (products, transactions, debts)? This CANNOT be undone!');">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="delete_user" class="action-btn delete-btn"
                                            title="Delete User">
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
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <button class="pagination-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"
                        onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CREATE USER MODAL -->
    <div class="modal-overlay" id="createUserModal">
        <div class="admin-modal">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Create New Store Owner</h2>
                <button class="modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                    <input type="hidden" name="action" value="create_user">

                    <div class="form-group">
                        <label for="new_username"><i class="fas fa-user"></i> Username *</label>
                        <input type="text" id="new_username" name="new_username" placeholder="e.g. juanstore" required>
                        <div class="form-hint">Login username for the store owner</div>
                    </div>

                    <div class="form-group">
                        <label for="new_fullname"><i class="fas fa-id-card"></i> Full Name *</label>
                        <input type="text" id="new_fullname" name="new_fullname" placeholder="e.g. Juan Dela Cruz"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="new_email"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="new_email" name="new_email" placeholder="e.g. juan@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label for="new_store_name"><i class="fas fa-store"></i> Grocery Store Name (Pangalan ng
                            Tindahan)</label>
                        <input type="text" id="new_store_name" name="new_store_name" placeholder="e.g. Juan's Grocery">
                    </div>

                    <div class="form-group">
                        <label for="new_phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="new_phone" name="new_phone" placeholder="e.g. 09171234567">
                    </div>

                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> Initial Password *</label>
                        <input type="text" id="new_password" name="new_password" placeholder="Min 6 characters" required
                            minlength="6">
                        <div class="form-hint">This is the temporary password. The store owner should change it after
                            first login.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeCreateModal()"
                        style="background: #eee; color: var(--dark);">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024-2026 WAISTORE Admin &mdash; Smart Grocery Store Management System</p>
            </div>
        </div>
    </footer>

    <script>
        // Modal handlers
        function openCreateModal() {
            document.getElementById('createUserModal').classList.add('active');
        }
        function closeCreateModal() {
            document.getElementById('createUserModal').classList.remove('active');
        }
        // Close modal on overlay click
        document.getElementById('createUserModal').addEventListener('click', function (e) {
            if (e.target === this) closeCreateModal();
        });
        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeCreateModal();
        });

        // Copy temp password
        function copyPassword() {
            const pwd = document.getElementById('tempPwd');
            if (pwd) {
                navigator.clipboard.writeText(pwd.textContent).then(() => {
                    alert('Password copied to clipboard! Share it securely with the store owner.');
                });
            }
        }

        // Filter handlers
        function applyFilters() {
            const params = new URLSearchParams();
            const search = document.getElementById('search').value;
            const status = document.getElementById('status-filter').value;
            if (search) params.set('search', search);
            if (status) params.set('status', status);
            window.location.href = 'admin_users.php?' + params.toString();
        }
        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        // Search on Enter
        document.getElementById('search').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') applyFilters();
        });

        // Privacy: reveal masked data on hover
        document.querySelectorAll('.masked-data').forEach(el => {
            const full = el.getAttribute('title');
            const masked = el.textContent;
            el.addEventListener('mouseenter', () => { el.textContent = full; el.style.color = 'var(--admin-primary)'; });
            el.addEventListener('mouseleave', () => { el.textContent = masked; el.style.color = ''; });
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>