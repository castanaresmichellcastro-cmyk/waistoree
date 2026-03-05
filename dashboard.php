<?php
session_start();
require_once 'appearance.php';
require_once 'helpers.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection - UPDATED FOR INFINITYFREE
$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name, store_name, has_seen_tutorial FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $full_name = $user['full_name'];
    $store_name = $user['store_name'];
    $has_seen_tutorial = $user['has_seen_tutorial'];
} else {
    $full_name = "User";
    $store_name = "Store";
    $has_seen_tutorial = false;
}
$stmt->close();

// NEW: Check if we should auto-start tutorial
$auto_start_tutorial = false;
if (isset($_SESSION['show_tutorial']) && $_SESSION['show_tutorial'] && !$has_seen_tutorial) {
    $auto_start_tutorial = true;
    unset($_SESSION['show_tutorial']); // Clear the flag
}

// Handle tour completion
if (isset($_POST['complete_tour'])) {
    // Update database to mark tutorial as seen
    $update_query = "UPDATE users SET has_seen_tutorial = TRUE WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $has_seen_tutorial = true;
}

// Initialize variables
$today_sales = 0;
$sales_change_text = "No data for comparison";
$total_items = 0;
$low_stock = 0;
$total_debts = 0;
$debt_count = 0;
$weekly_profit = 0;

try {
    // Calculate today's sales with prepared statement
    $today = date('Y-m-d');
    $sales_query = "SELECT SUM(total_amount) as today_sales FROM transactions 
                   WHERE DATE(created_at) = ? AND status = 'paid' AND user_id = ?";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param("si", $today, $user_id);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    $today_sales_data = $sales_result->fetch_assoc();
    $today_sales = $today_sales_data['today_sales'] ?? 0;
    $stmt->close();

    // Calculate yesterday's sales for comparison
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterday_sales_query = "SELECT SUM(total_amount) as yesterday_sales FROM transactions 
                             WHERE DATE(created_at) = ? AND status = 'paid' AND user_id = ?";
    $stmt = $conn->prepare($yesterday_sales_query);
    $stmt->bind_param("si", $yesterday, $user_id);
    $stmt->execute();
    $yesterday_result = $stmt->get_result();
    $yesterday_data = $yesterday_result->fetch_assoc();
    $yesterday_sales = $yesterday_data['yesterday_sales'] ?? 0;
    $stmt->close();

    // Calculate percentage change
    if ($yesterday_sales > 0) {
        $sales_change = (($today_sales - $yesterday_sales) / $yesterday_sales) * 100;
        $sales_change_text = ($sales_change >= 0 ? "+" : "") . number_format($sales_change, 1) . "% from yesterday";
    } else {
        $sales_change_text = "No data for comparison";
    }

    // Get inventory stats with prepared statement
    $inventory_query = "SELECT COUNT(*) as total_items, 
                       SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as low_stock 
                       FROM products WHERE user_id = ?";
    $stmt = $conn->prepare($inventory_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $inventory_result = $stmt->get_result();
    $inventory = $inventory_result->fetch_assoc();
    $total_items = $inventory['total_items'] ?? 0;
    $low_stock = $inventory['low_stock'] ?? 0;
    $stmt->close();

    // Get outstanding debts with prepared statement
    $debts_query = "SELECT SUM(amount) as total_debts, COUNT(*) as debt_count 
                   FROM debts WHERE user_id = ? AND status != 'paid'";
    $stmt = $conn->prepare($debts_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $debts_result = $stmt->get_result();
    $debts = $debts_result->fetch_assoc();
    $total_debts = $debts['total_debts'] ?? 0;
    $debt_count = $debts['debt_count'] ?? 0;
    $stmt->close();

    // Calculate weekly profit with prepared statement
    $week_start = date('Y-m-d', strtotime('-7 days'));

    // Get weekly sales
    $profit_query = "SELECT SUM(total_amount) as weekly_sales FROM transactions 
                   WHERE user_id = ? AND status = 'paid' AND created_at >= ?";
    $stmt = $conn->prepare($profit_query);
    $stmt->bind_param("is", $user_id, $week_start);
    $stmt->execute();
    $profit_result = $stmt->get_result();
    $weekly_sales_data = $profit_result->fetch_assoc();
    $weekly_sales = $weekly_sales_data['weekly_sales'] ?? 0;
    $stmt->close();

    // Get product costs with proper user filtering through transactions join
    $cost_query = "SELECT SUM(p.price * ti.quantity) as total_cost 
                  FROM transaction_items ti
                  JOIN products p ON ti.product_id = p.id
                  JOIN transactions t ON ti.transaction_id = t.id
                  WHERE t.user_id = ? AND t.status = 'paid' AND t.created_at >= ?";
    $stmt = $conn->prepare($cost_query);
    $stmt->bind_param("is", $user_id, $week_start);
    $stmt->execute();
    $cost_result = $stmt->get_result();
    $cost_data = $cost_result->fetch_assoc();
    $total_cost = $cost_data['total_cost'] ?? 0;
    $stmt->close();

    // Calculate profit (sales - cost)
    $weekly_profit = $weekly_sales - $total_cost;

} catch (Exception $e) {
    // Log error but don't show to user for security
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Smart Grocery Store Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <link rel="stylesheet" href="themes.css">
    <style>
        /* Base styles only, variables handled by themes.css */

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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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

        nav a:hover {
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

        .sales-icon {
            background-color: var(--primary);
        }

        .inventory-icon {
            background-color: var(--accent);
        }

        .debt-icon {
            background-color: var(--warning);
        }

        .profit-icon {
            background-color: var(--secondary);
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

        /* Quick Actions */
        .quick-actions {
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

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .action-desc {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Recent Transactions */
        .recent-transactions {
            margin-bottom: 30px;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .transaction-table th,
        .transaction-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .transaction-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .transaction-table tr:last-child td {
            border-bottom: none;
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

        /* Welcome Message and Tour */
        .welcome-message {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(45, 91, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .welcome-message::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .tour-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-tour {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-tour:hover {
            background-color: #e58e0c;
            transform: translateY(-2px);
        }

        .btn-tour-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-tour-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Tour Modal */
        .tour-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .tour-modal.active {
            display: flex;
        }

        .tour-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .tour-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tour-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .tour-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        .tour-body {
            padding: 25px;
        }

        .tour-step {
            margin-bottom: 25px;
        }

        .tour-step:last-child {
            margin-bottom: 0;
        }

        .step-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-description {
            color: var(--dark);
            line-height: 1.6;
        }

        .tour-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tour-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-dots {
            display: flex;
            gap: 8px;
        }

        .progress-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e0e0e0;
            transition: background 0.3s;
        }

        .progress-dot.active {
            background: var(--primary);
        }

        /* Highlight elements during tour */
        .tour-highlight {
            position: relative;
            z-index: 1001;
            box-shadow: 0 0 0 4px var(--secondary), 0 0 20px rgba(255, 158, 26, 0.5) !important;
            border-radius: 8px;
            transition: all 0.3s;
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

            .stats-grid,
            .action-grid {
                grid-template-columns: 1fr;
            }

            .transaction-table {
                font-size: 0.9rem;
            }

            .footer-content {
                flex-direction: column;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .tour-buttons {
                flex-direction: column;
            }

            .btn-tour {
                justify-content: center;
            }
        }
    </style>
</head>

</head>

<body class="<?php echo $theme_class; ?>">

    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="WAIS_LOGO.png" alt="WAISTORE Logo" style="height: 60px; width: 150;">
                    <span>WAISTORE</span>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="inventory.php"><i class="fas fa-box"></i> Inventory</a></li>
                        <li><a href="pos.php"><i class="fas fa-cash-register"></i> POS</a></li>
                        <li><a href="debts.php"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
                        <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="notifications.php" class="btn btn-outline"><i class="fas fa-bell"></i></a>
                    <a href="settings.php" class="btn btn-outline"><i class="fas fa-cog"></i></a>
                    <a href="account.php" class="btn btn-primary"><i class="fas fa-user"></i> My Account</a>
                    <a href="logout.php" class="btn btn-outline" style="margin-left:10px;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="container">
            <!-- Welcome Message -->
            <?php if (!$has_seen_tutorial): ?>
                <div class="welcome-message" id="welcomeMessage"
                    style="background: var(--gradient-primary); padding: 40px; border-radius: var(--radius-2xl); position: relative; overflow: hidden; box-shadow: 0 20px 40px var(--primary-glow); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 40px;">
                    <!-- Decotative background elements -->
                    <div
                        style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; blur: 40px;">
                    </div>
                    <div
                        style="position: absolute; bottom: -30px; left: 10%; width: 100px; height: 100px; background: rgba(245, 158, 11, 0.2); border-radius: 30%; transform: rotate(45deg);">
                    </div>

                    <div class="welcome-content" style="position: relative; z-index: 2;">
                        <div style="display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;">
                            <div
                                style="background: white; width: 80px; height: 80px; border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; box-shadow: var(--shadow-xl);">
                                👋
                            </div>
                            <div style="flex: 1; min-width: 300px;">
                                <h2 class="welcome-title"
                                    style="font-size: 2.25rem !important; font-weight: 800 !important; margin-bottom: 12px !important; color: white !important; letter-spacing: -0.03em;">
                                    Welcome, <?php echo htmlspecialchars($full_name); ?>!
                                </h2>
                                <p class="welcome-subtitle"
                                    style="font-size: 1.25rem !important; opacity: 0.9 !important; margin-bottom: 32px !important; max-width: 700px; line-height: 1.6;">
                                    Your store, <strong><?php echo htmlspecialchars($store_name); ?></strong>, is now online
                                    and ready for business. 🎉
                                    Let's show you around your new smart dashboard!
                                </p>
                                <div class="tour-buttons" style="display: flex; gap: 16px; flex-wrap: wrap;">
                                    <button class="btn-tour" onclick="startTour()"
                                        style="background: var(--secondary) !important; color: white !important; padding: 16px 32px !important; font-size: 1.1rem !important; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3) !important;">
                                        <i class="fas fa-play" style="margin-right: 8px;"></i> Start Interactive Tour
                                    </button>
                                    <button class="btn-tour btn-tour-outline" onclick="dismissWelcome()"
                                        style="border: 2px solid rgba(255,255,255,0.3) !important; color: white !important; padding: 16px 32px !important; font-size: 1.1rem !important; background: rgba(255,255,255,0.05) !important;">
                                        <i class="fas fa-times" style="margin-right: 8px;"></i> Skip for Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1>Grocery Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($full_name); ?>! Here's your tindahan overview for
                    today.</p>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card" id="statSales">
                    <div class="stat-header">
                        <h3>Today's Sales (Benta Ngayong Araw)</h3>
                        <div class="stat-icon sales-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($today_sales, 2); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($sales_change_text); ?></div>
                </div>

                <div class="stat-card" id="statInventory">
                    <div class="stat-header">
                        <h3>Products in Stock (Mga Produkto)</h3>
                        <div class="stat-icon inventory-icon">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_items; ?> items</div>
                    <div class="stat-label"><?php echo $low_stock; ?> products low in stock</div>
                </div>

                <div class="stat-card" id="statDebts">
                    <div class="stat-header">
                        <h3>Outstanding Utang (Natitirang Utang)</h3>
                        <div class="stat-icon debt-icon">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($total_debts, 2); ?></div>
                    <div class="stat-label">From <?php echo $debt_count; ?> suki (customers)</div>
                </div>

                <div class="stat-card" id="statProfit">
                    <div class="stat-header">
                        <h3>Weekly Profit (Kita sa Linggo)</h3>
                        <div class="stat-icon profit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($weekly_profit, 2); ?></div>
                    <div class="stat-label">This week's earnings</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions" id="quickActions">
                <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions (Mabilisang Aksyon)</h2>
                <div class="action-grid">
                    <a href="pos.php" style="text-decoration:none; color:inherit;">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <h3 class="action-title">Start Selling</h3>
                            <p class="action-desc">Open the POS cashier</p>
                        </div>
                    </a>

                    <a href="inventory.php" style="text-decoration: none; color: inherit;">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-boxes-stacked"></i>
                            </div>
                            <h3 class="action-title">Check Stock</h3>
                            <p class="action-desc">I-check ang mga produkto</p>
                        </div>
                    </a>

                    <a href="debts.php" style="text-decoration: none; color: inherit;">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                            <h3 class="action-title">Utang Records</h3>
                            <p class="action-desc">I-track ang mga utang ng suki</p>
                        </div>
                    </a>

                    <a href="reports.php" style="text-decoration: none; color: inherit;">
                        <div class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3 class="action-title">Sales Reports</h3>
                            <p class="action-desc">Ulat ng benta at kita</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="recent-transactions" id="recentTransactions">
                <h2 class="section-title"><i class="fas fa-history"></i> Recent Transactions (Mga Kamakailan na
                    Transaksyon)</h2>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Get recent transactions with prepared statement
                            $transactions_query = "SELECT t.id, t.customer_name, t.total_amount, t.payment_method, t.status, t.created_at,
                                                 COUNT(ti.id) as item_count
                                                 FROM transactions t
                                                 LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
                                                 WHERE t.user_id = ?
                                                 GROUP BY t.id
                                                 ORDER BY t.created_at DESC
                                                 LIMIT 10";
                            $stmt = $conn->prepare($transactions_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $transactions_result = $stmt->get_result();

                            if ($transactions_result->num_rows > 0) {
                                while ($transaction = $transactions_result->fetch_assoc()) {
                                    $status_class = "";
                                    switch ($transaction['status']) {
                                        case 'paid':
                                            $status_class = "status-paid";
                                            break;
                                        case 'pending':
                                            $status_class = "status-pending";
                                            break;
                                        case 'debt':
                                            $status_class = "status-debt";
                                            break;
                                    }

                                    $txn_date = date('M j, Y', strtotime($transaction['created_at']));
                                    $txn_time = date('g:i A', strtotime($transaction['created_at']));

                                    echo "<tr>";
                                    echo "<td>#TRX-" . str_pad($transaction['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                    echo "<td><div style='line-height:1.3'><strong>" . $txn_date . "</strong><br><small style='color:var(--gray)'>" . $txn_time . "</small></div></td>";
                                    echo "<td>" . htmlspecialchars($transaction['customer_name'] ?: 'Walk-in Suki') . "</td>";
                                    echo "<td>" . $transaction['item_count'] . " items</td>";
                                    echo "<td>₱" . number_format($transaction['total_amount'], 2) . "</td>";
                                    echo "<td>" . ucfirst($transaction['payment_method']) . "</td>";
                                    echo "<td><span class='status-badge $status_class'>" . ucfirst($transaction['status']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align: center;'>No recent transactions</td></tr>";
                            }
                            $stmt->close();

                        } catch (Exception $e) {
                            error_log("Recent transactions error: " . $e->getMessage());
                            echo "<tr><td colspan='7' style='text-align: center; color: var(--danger);'>Error loading transactions</td></tr>";
                        }

                        // Close database connection
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Tour Modal -->
    <div class="tour-modal" id="tourModal">
        <div class="tour-content">
            <div class="tour-header">
                <h3 class="tour-title">Dashboard Tour</h3>
                <button class="tour-close" onclick="closeTour()">&times;</button>
            </div>
            <div class="tour-body">
                <div class="tour-step" id="step1">
                    <h4 class="step-title"><i class="fas fa-chart-bar"></i> Sales Overview</h4>
                    <p class="step-description">
                        This card shows your today's sales performance. Track your daily earnings
                        and compare them with yesterday's sales to monitor your store's growth.
                    </p>
                </div>
                <div class="tour-step" id="step2" style="display: none;">
                    <h4 class="step-title"><i class="fas fa-boxes"></i> Inventory Management</h4>
                    <p class="step-description">
                        Keep track of your total products and low stock items. This helps you
                        maintain optimal inventory levels and avoid running out of popular items.
                    </p>
                </div>
                <div class="tour-step" id="step3" style="display: none;">
                    <h4 class="step-title"><i class="fas fa-file-invoice-dollar"></i> Debt Tracking</h4>
                    <p class="step-description">
                        Monitor outstanding debts from customers. This section shows the total
                        amount and number of customers with pending payments.
                    </p>
                </div>
                <div class="tour-step" id="step4" style="display: none;">
                    <h4 class="step-title"><i class="fas fa-chart-line"></i> Profit Analysis</h4>
                    <p class="step-description">
                        View your net profit for the week. This calculates your actual earnings
                        after deducting product costs from your sales.
                    </p>
                </div>
                <div class="tour-step" id="step5" style="display: none;">
                    <h4 class="step-title"><i class="fas fa-bolt"></i> Quick Actions</h4>
                    <p class="step-description">
                        Access frequently used features quickly. From recording new sales to
                        managing inventory and debts, everything is just one click away.
                    </p>
                </div>
                <div class="tour-step" id="step6" style="display: none;">
                    <h4 class="step-title"><i class="fas fa-history"></i> Recent Activity</h4>
                    <p class="step-description">
                        Stay updated with your latest transactions. This table shows your most
                        recent sales, helping you track customer activity and payment status.
                    </p>
                </div>
            </div>
            <div class="tour-footer">
                <div class="tour-progress-container">
                    <div class="tour-progress-text">
                        <span>Step <strong id="currentStepNum">1</strong> of <strong
                                id="totalStepsNum">6</strong></span>
                        <span id="stepProgressPercent">16%</span>
                    </div>
                    <div class="progress-dots">
                        <div class="progress-dot active" data-step="1"></div>
                        <div class="progress-dot" data-step="2"></div>
                        <div class="progress-dot" data-step="3"></div>
                        <div class="progress-dot" data-step="4"></div>
                        <div class="progress-dot" data-step="5"></div>
                        <div class="progress-dot" data-step="6"></div>
                    </div>
                </div>
                <div class="tour-actions"
                    style="display: flex; justify-content: space-between; gap: 12px; width: 100%;">
                    <button class="btn-tour btn-tour-outline" id="prevBtn" onclick="prevStep()"
                        style="flex: 1; display: none; justify-content: center; align-items: center;">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button class="btn-tour" id="nextBtn" onclick="nextStep()"
                        style="flex: 1; justify-content: center; align-items: center;">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn-tour" id="completeBtn" onclick="completeTour()"
                        style="flex: 1; display: none; justify-content: center; align-items: center;">
                        <i class="fas fa-check"></i> Complete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>WAISTORE</h3>
                    <p>Smart Grocery Store Management System</p>
                    <p>Empowering Filipino grocery store owners with digital tools for sales, inventory, and
                        customer credit management.</p>
                    <p style="margin-top: 8px; font-size: 0.85rem; color: #888;"><i class="fas fa-tag"></i> Version
                        2.0 &mdash; Grocery Edition</p>
                </div>
                <div class="footer-section">
                    <h3>Contact & Support</h3>
                    <p><i class="fas fa-envelope"></i> support@waistore.com</p>
                    <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="about_us.php" style="color: #ccc;">About Us</a></p>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                    <p><a href="terms_of_service.php" style="color: #ccc;">Terms of Service</a></p>
                    <p><a href="faqs.php" style="color: #ccc;">FAQs & Help</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024-2026 WAISTORE &mdash; Kasangga ng Tindahan Mo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tour functionality
        let currentStep = 1;
        const totalSteps = 6;
        const tourElements = [
            'statSales',
            'statInventory',
            'statDebts',
            'statProfit',
            'quickActions',
            'recentTransactions'
        ];

        function startTour() {
            currentStep = 1;
            document.getElementById('tourModal').classList.add('active');
            updateTour();
            highlightCurrentElement();
        }

        function closeTour() {
            document.getElementById('tourModal').classList.remove('active');
            removeHighlights();
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateTour();
                highlightCurrentElement();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateTour();
                highlightCurrentElement();
            }
        }

        function updateTour() {
            // Update progress text
            const currentStepNum = document.getElementById('currentStepNum');
            const totalStepsNum = document.getElementById('totalStepsNum');
            const stepProgressPercent = document.getElementById('stepProgressPercent');

            if (currentStepNum) currentStepNum.textContent = currentStep;
            if (totalStepsNum) totalStepsNum.textContent = totalSteps;
            if (stepProgressPercent) stepProgressPercent.textContent = Math.round((currentStep / totalSteps) * 100) + '%';

            // Hide all steps with a fade-out effect and staggered transform
            const steps = document.querySelectorAll('.tour-step');
            steps.forEach(step => {
                step.style.display = 'none';
                step.style.opacity = '0';
                step.style.transform = 'translateY(15px)';
                step.style.transition = 'all 0.4s var(--transition-base)';
            });

            // Show current step with a graceful fade-in
            const activeStep = document.getElementById(`step${currentStep}`);
            if (activeStep) {
                activeStep.style.display = 'block';
                // Delay to ensure the browser processes display: block before animating
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        activeStep.style.opacity = '1';
                        activeStep.style.transform = 'translateY(0)';
                    }, 50);
                });
            }

            // Update progress dots with active classes
            document.querySelectorAll('.progress-dot').forEach((dot, index) => {
                if (index + 1 === currentStep) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });

            // Update button visibility using flex for better layout control
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const completeBtn = document.getElementById('completeBtn');

            if (prevBtn) prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
            if (nextBtn) nextBtn.style.display = currentStep < totalSteps ? 'flex' : 'none';
            if (completeBtn) completeBtn.style.display = currentStep === totalSteps ? 'flex' : 'none';
        }


        function highlightCurrentElement() {
            removeHighlights();

            const elementId = tourElements[currentStep - 1];
            const element = document.getElementById(elementId);

            if (element) {
                // Added a slight delay for better synchronization with modal transitions
                setTimeout(() => {
                    element.classList.add('tour-highlight');

                    // Precision scroll to element
                    const offset = 100; // Offset for header or spacing
                    const elementPosition = element.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - (window.innerHeight / 2) + (element.offsetHeight / 2);

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 100);
            }
        }


        function removeHighlights() {
            tourElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.classList.remove('tour-highlight');
                }
            });
        }

        function completeTour() {
            // Submit form to mark tour as completed
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'complete_tour';
            input.value = 'true';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function dismissWelcome() {
            document.getElementById('welcomeMessage').style.display = 'none';
        }

        // Auto-start tour if it's the user's first time
        window.onload = function () {
            <?php if ($auto_start_tutorial): ?>
                // Auto-start the tour after a short delay
                setTimeout(() => {
                    startTour();
                }, 1000);
            <?php endif; ?>
        };
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>