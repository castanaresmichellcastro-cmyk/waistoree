<?php
session_start();
require_once 'appearance.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_query = "SELECT full_name, store_name FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $full_name = $user['full_name'];
    $store_name = $user['store_name'];
} else {
    $full_name = "User";
    $store_name = "Store";
}
$stmt->close();

// Get all data using the session user_id
$totalSales = getTotalSales($conn, $user_id);
$totalProfit = getTotalProfit($conn, $user_id);
$totalCustomers = getTotalCustomers($conn, $user_id);
$totalItems = getTotalItemsSold($conn, $user_id);
$salesSummary = getSalesSummary($conn, $user_id);
$productsSummary = getProductsSummary($conn, $user_id);
$categoriesSummary = getCategoriesSummary($conn, $user_id);
$salesData = getSalesChartData($conn, $user_id);
$productsData = getProductsChartData($conn, $user_id);
$categoriesData = getCategoriesChartData($conn, $user_id);

// Inventory data
$inventorySummary = getInventorySummary($conn, $user_id);
$lowStockProducts = getLowStockProducts($conn, $user_id);
$inventoryValue = getInventoryValue($conn, $user_id);

// Debts data
$debtsSummary = getDebtsSummary($conn, $user_id);
$topDebtors = getTopDebtors($conn, $user_id);
$debtsByStatus = getDebtsByStatus($conn, $user_id);

// Customer data
$customersSummary = getCustomersSummary($conn, $user_id);
$topCustomers = getTopCustomers($conn, $user_id);
$customerActivity = getCustomerActivity($conn, $user_id);

// Close connection
$conn->close();

// Function to get total sales
function getTotalSales($conn, $user_id)
{
    $query = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
              FROM transactions 
              WHERE user_id = ? AND status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return number_format($row['total_sales'], 2);
    }
    return "0.00";
}

// Function to get total profit (estimated as 30% of sales)
function getTotalProfit($conn, $user_id)
{
    $query = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
              FROM transactions 
              WHERE user_id = ? AND status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profit = $row['total_sales'] * 0.3; // 30% profit margin
        return number_format($profit, 2);
    }
    return "0.00";
}

// Function to get total customers (unique customer names in transactions and debts)
function getTotalCustomers($conn, $user_id)
{
    $query = "SELECT COUNT(DISTINCT customer_name) as total_customers 
              FROM (
                  SELECT customer_name FROM transactions WHERE user_id = ? AND customer_name IS NOT NULL
                  UNION 
                  SELECT customer_name FROM debts WHERE user_id = ?
              ) as customers";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_customers'];
    }
    return "0";
}

// Function to get total items sold
function getTotalItemsSold($conn, $user_id)
{
    $query = "SELECT COALESCE(SUM(ti.quantity), 0) as total_items 
              FROM transaction_items ti 
              JOIN transactions t ON ti.transaction_id = t.id 
              WHERE t.user_id = ? AND t.status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_items'];
    }
    return "0";
}

// Function to get sales summary
function getSalesSummary($conn, $user_id)
{
    $query = "SELECT COUNT(*) as transaction_count, 
                     COALESCE(SUM(total_amount), 0) as total_amount 
              FROM transactions 
              WHERE user_id = ? AND status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return "Total of {$row['transaction_count']} transactions with ₱" . number_format($row['total_amount'], 2) . " in sales.";
    }
    return "No sales data available.";
}

// Function to get products summary
function getProductsSummary($conn, $user_id)
{
    $query = "SELECT p.name, SUM(ti.quantity) as total_sold 
              FROM products p 
              LEFT JOIN transaction_items ti ON p.id = ti.product_id 
              LEFT JOIN transactions t ON ti.transaction_id = t.id 
              WHERE p.user_id = ? AND t.status = 'paid'
              GROUP BY p.id, p.name 
              ORDER BY total_sold DESC 
              LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $top_products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $top_products[] = $row['name'] . " (" . $row['total_sold'] . ")";
        }
    }
    return count($top_products) > 0 ? implode(", ", $top_products) . " are the top sellers." : "No sales data available.";
}

// Function to get categories summary
function getCategoriesSummary($conn, $user_id)
{
    $query = "SELECT p.category, COUNT(*) as product_count 
              FROM products p 
              WHERE p.user_id = ? 
              GROUP BY p.category 
              ORDER BY product_count DESC 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['category'] . " has the most products with {$row['product_count']} items.";
    }
    return "No category data available.";
}

// Get data for charts
function getSalesChartData($conn, $user_id)
{
    $query = "SELECT DATE(created_at) as date, SUM(total_amount) as daily_sales 
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' 
              GROUP BY DATE(created_at) 
              ORDER BY date DESC 
              LIMIT 7";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return array_reverse($data); // Reverse to show oldest first
}

function getProductsChartData($conn, $user_id)
{
    $query = "SELECT p.name, COALESCE(SUM(ti.quantity), 0) as total_sold 
              FROM products p 
              LEFT JOIN transaction_items ti ON p.id = ti.product_id 
              LEFT JOIN transactions t ON ti.transaction_id = t.id 
              WHERE p.user_id = ? AND (t.status = 'paid' OR t.status IS NULL)
              GROUP BY p.id, p.name 
              ORDER BY total_sold DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getCategoriesChartData($conn, $user_id)
{
    $query = "SELECT p.category, COUNT(*) as product_count 
              FROM products p 
              WHERE p.user_id = ? 
              GROUP BY p.category";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Inventory Report Functions
function getInventorySummary($conn, $user_id)
{
    $query = "SELECT COUNT(*) as total_products, 
                     SUM(stock) as total_stock,
                     SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as low_stock_count
              FROM products 
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return "You have {$row['total_products']} products with {$row['total_stock']} total items in stock. {$row['low_stock_count']} products are running low.";
    }
    return "No inventory data available.";
}

function getLowStockProducts($conn, $user_id)
{
    $query = "SELECT name, stock, price, category 
              FROM products 
              WHERE user_id = ? AND stock <= 5 
              ORDER BY stock ASC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getInventoryValue($conn, $user_id)
{
    $query = "SELECT SUM(stock * price) as total_value 
              FROM products 
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return number_format($row['total_value'], 2);
    }
    return "0.00";
}

// Debts Report Functions
function getDebtsSummary($conn, $user_id)
{
    $query = "SELECT COUNT(*) as total_debts,
                     SUM(amount - amount_paid) as total_outstanding,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                     SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                     SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count
              FROM debts 
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return "You have {$row['total_debts']} debts with ₱" . number_format($row['total_outstanding'], 2) . " outstanding. {$row['pending_count']} pending, {$row['partial_count']} partial, {$row['paid_count']} paid.";
    }
    return "No debts data available.";
}

function getTopDebtors($conn, $user_id)
{
    $query = "SELECT customer_name, SUM(amount - amount_paid) as total_owed
              FROM debts 
              WHERE user_id = ? AND status != 'paid'
              GROUP BY customer_name 
              ORDER BY total_owed DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getDebtsByStatus($conn, $user_id)
{
    $query = "SELECT status, COUNT(*) as count, SUM(amount - amount_paid) as total
              FROM debts 
              WHERE user_id = ?
              GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Customer Report Functions
function getCustomersSummary($conn, $user_id)
{
    $query = "SELECT COUNT(DISTINCT customer_name) as total_customers,
                     AVG(total_amount) as avg_purchase,
                     MAX(total_amount) as max_purchase
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' AND customer_name IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return "You have {$row['total_customers']} customers. Average purchase: ₱" . number_format($row['avg_purchase'], 2) . ". Highest purchase: ₱" . number_format($row['max_purchase'], 2) . ".";
    }
    return "No customer data available.";
}

function getTopCustomers($conn, $user_id)
{
    $query = "SELECT customer_name, COUNT(*) as transaction_count, SUM(total_amount) as total_spent
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' AND customer_name IS NOT NULL
              GROUP BY customer_name 
              ORDER BY total_spent DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getCustomerActivity($conn, $user_id)
{
    $query = "SELECT DATE(created_at) as date, COUNT(DISTINCT customer_name) as active_customers
              FROM transactions 
              WHERE user_id = ? AND status = 'paid' AND customer_name IS NOT NULL
              GROUP BY DATE(created_at) 
              ORDER BY date DESC 
              LIMIT 7";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return array_reverse($data);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Sales Reports (Ulat ng Benta)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Page Styles */
        .page {
            padding: 30px 0;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
        }

        /* Reports Styles */
        .reports-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .tab {
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            background-color: var(--card-bg);
            cursor: pointer;
            font-weight: 500;
        }

        .tab.active {
            background-color: var(--primary);
            color: white;
        }

        .date-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .date-input {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .report-card h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }

        .chart-container {
            height: 200px;
            position: relative;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .sales-icon {
            color: var(--accent);
        }

        .profit-icon {
            color: var(--primary);
        }

        .customers-icon {
            color: var(--secondary);
        }

        .items-icon {
            color: var(--warning);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray);
        }

        .report-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        /* Data Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-paid {
            background-color: #D1ECF1;
            color: #0C5460;
        }

        .status-partial {
            background-color: #FFE6CC;
            color: #663300;
        }

        .status-low {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .status-ok {
            background-color: #D4EDDA;
            color: #155724;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: var(--primary);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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

            .reports-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
            }

            .date-filter {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-actions {
                flex-direction: column;
            }

            .data-table {
                display: block;
                overflow-x: auto;
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
                        <li><a href="reports.php" class="active"><i class="fas fa-chart-line"></i> Reports</a></li>
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

    <!-- Reports Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Sales Reports (Ulat ng Benta)</h1>
                <button class="btn btn-primary" id="exportReportBtn"><i class="fas fa-download"></i> Export
                    Report</button>
            </div>

            <div class="reports-tabs">
                <div class="tab active" data-report="sales">Sales Report</div>
                <div class="tab" data-report="inventory">Inventory Report</div>
                <div class="tab" data-report="debts">Debts Report</div>

            </div>

            <div class="date-filter">
                <span>Filter by date:</span>
                <input type="date" class="date-input" id="dateFrom" value="<?php echo date('Y-m-01'); ?>">
                <span>to</span>
                <input type="date" class="date-input" id="dateTo" value="<?php echo date('Y-m-d'); ?>">
                <button class="btn btn-primary" id="applyDateFilter">Apply Filter</button>
            </div>

            <!-- Sales Report (Default) -->
            <div id="salesReport" class="report-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon sales-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value" id="totalSales">₱<?php echo $totalSales; ?></div>
                        <div class="stat-label">Total Sales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon profit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value" id="totalProfit">₱<?php echo $totalProfit; ?></div>
                        <div class="stat-label">Total Profit</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon customers-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value" id="totalCustomers"><?php echo $totalCustomers; ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon items-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-value" id="totalItems"><?php echo $totalItems; ?></div>
                        <div class="stat-label">Items Sold</div>
                    </div>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Sales Overview</h3>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                        <p id="salesSummary"><?php echo $salesSummary; ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Top Selling Products</h3>
                        <div class="chart-container">
                            <canvas id="productsChart"></canvas>
                        </div>
                        <p id="productsSummary"><?php echo $productsSummary; ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Sales by Category</h3>
                        <div class="chart-container">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                        <p id="categoriesSummary"><?php echo $categoriesSummary; ?></p>
                    </div>
                </div>
            </div>

            <!-- Inventory Report -->
            <div id="inventoryReport" class="report-content" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon sales-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-value"><?php echo count($lowStockProducts); ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon profit-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="stat-value">₱<?php echo $inventoryValue; ?></div>
                        <div class="stat-label">Inventory Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon customers-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-value"><?php echo count($categoriesData); ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon items-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-value"><?php echo count($productsData); ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Stock Levels</h3>
                        <div class="chart-container">
                            <canvas id="stockChart"></canvas>
                        </div>
                        <p><?php echo $inventorySummary; ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Category Distribution</h3>
                        <div class="chart-container">
                            <canvas id="inventoryCategoriesChart"></canvas>
                        </div>
                        <p>Distribution of products across different categories.</p>
                    </div>
                    <div class="report-card">
                        <h3>Low Stock Alert</h3>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php if (count($lowStockProducts) > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stock</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                            <tr>
                                                <td><?php echo $product['name']; ?></td>
                                                <td><?php echo $product['stock']; ?></td>
                                                <td><?php echo $product['category']; ?></td>
                                                <td>
                                                    <span
                                                        class="status-badge <?php echo $product['stock'] <= 3 ? 'status-low' : 'status-ok'; ?>">
                                                        <?php echo $product['stock'] <= 3 ? 'Very Low' : 'Low'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No low stock items. All products are well-stocked.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debts Report -->
            <div id="debtsReport" class="report-content" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon sales-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="stat-value"><?php echo count($topDebtors); ?></div>
                        <div class="stat-label">Active Debtors</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon profit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">
                            ₱<?php
                            $totalOutstanding = 0;
                            foreach ($debtsByStatus as $debt) {
                                if ($debt['status'] != 'paid') {
                                    $totalOutstanding += $debt['total'];
                                }
                            }
                            echo number_format($totalOutstanding, 2);
                            ?>
                        </div>
                        <div class="stat-label">Outstanding Balance</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon customers-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-value">
                            <?php
                            $pendingCount = 0;
                            foreach ($debtsByStatus as $debt) {
                                if ($debt['status'] == 'pending') {
                                    $pendingCount = $debt['count'];
                                    break;
                                }
                            }
                            echo $pendingCount;
                            ?>
                        </div>
                        <div class="stat-label">Pending Debts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon items-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php
                            $paidCount = 0;
                            foreach ($debtsByStatus as $debt) {
                                if ($debt['status'] == 'paid') {
                                    $paidCount = $debt['count'];
                                    break;
                                }
                            }
                            echo $paidCount;
                            ?>
                        </div>
                        <div class="stat-label">Paid Debts</div>
                    </div>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Debts by Status</h3>
                        <div class="chart-container">
                            <canvas id="debtsStatusChart"></canvas>
                        </div>
                        <p><?php echo $debtsSummary; ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Top Debtors</h3>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php if (count($topDebtors) > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Amount Owed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topDebtors as $debtor): ?>
                                            <tr>
                                                <td><?php echo $debtor['customer_name']; ?></td>
                                                <td>₱<?php echo number_format($debtor['total_owed'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No outstanding debts.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Debt Recovery</h3>
                        <p>Track your debt collection efforts and follow up with customers who have outstanding
                            balances.</p>
                        <div class="report-actions">
                            <button class="btn btn-primary"><i class="fas fa-envelope"></i> Send Reminders</button>
                            <button class="btn btn-outline"><i class="fas fa-print"></i> Print Statements</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Report -->
            <div id="customerReport" class="report-content" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon sales-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo count($topCustomers); ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon profit-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo count($customerActivity); ?></div>
                        <div class="stat-label">Daily Active</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon customers-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value"><?php echo $topCustomers[0]['customer_name'] ?? 'N/A'; ?></div>
                        <div class="stat-label">Top Customer</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon items-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="stat-value">
                            ₱<?php echo isset($topCustomers[0]) ? number_format($topCustomers[0]['total_spent'], 2) : '0.00'; ?>
                        </div>
                        <div class="stat-label">Top Spent</div>
                    </div>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Customer Activity</h3>
                        <div class="chart-container">
                            <canvas id="customerActivityChart"></canvas>
                        </div>
                        <p><?php echo $customersSummary; ?></p>
                    </div>
                    <div class="report-card">
                        <h3>Top Customers</h3>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php if (count($topCustomers) > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Transactions</th>
                                            <th>Total Spent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topCustomers as $customer): ?>
                                            <tr>
                                                <td><?php echo $customer['customer_name']; ?></td>
                                                <td><?php echo $customer['transaction_count']; ?></td>
                                                <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No customer data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Customer Insights</h3>
                        <p>Analyze customer behavior and purchasing patterns to improve your marketing strategies and
                            customer retention.</p>
                        <div class="report-actions">
                            <button class="btn btn-primary"><i class="fas fa-bullhorn"></i> Promotions</button>
                            <button class="btn btn-outline"><i class="fas fa-user-plus"></i> Loyalty Program</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Export Report Modal -->
    <div class="modal" id="exportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Export Report</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form id="exportForm">
                <div class="form-group">
                    <label for="exportType">Report Type</label>
                    <select id="exportType" class="form-control">
                        <option value="sales">Sales Report</option>
                        <option value="inventory">Inventory Report</option>
                        <option value="debts">Debts Report</option>
                        <option value="customers">Customer Report</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exportFormat">Format</label>
                    <select id="exportFormat" class="form-control">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exportDateRange">Date Range</label>
                    <select id="exportDateRange" class="form-control">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Export</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>WAISTORE</h3>
                    <p>Your complete inventory and sales management solution designed for small businesses.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="dashboard.php" style="color: #ccc;">Dashboard</a></p>
                    <p><a href="inventory.php" style="color: #ccc;">Inventory</a></p>
                    <p><a href="pos.php" style="color: #ccc;">Point of Sale</a></p>
                    <p><a href="debts.php" style="color: #ccc;">Debt Management</a></p>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-envelope"></i> support@waistore.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Business Ave, Suite 100</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024-2026 WAISTORE &mdash; Kasangga ng Tindahan Mo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tab Switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function () {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.add('active');

                // Hide all report contents
                document.querySelectorAll('.report-content').forEach(content => {
                    content.style.display = 'none';
                });

                // Show the selected report content
                const reportType = this.getAttribute('data-report');
                document.getElementById(reportType + 'Report').style.display = 'block';
            });
        });

        // Export Report Modal
        document.getElementById('exportReportBtn').addEventListener('click', function () {
            document.getElementById('exportModal').style.display = 'flex';
        });

        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('exportModal').style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            const modal = document.getElementById('exportModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Charts
        document.addEventListener('DOMContentLoaded', function () {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($salesData, 'date')); ?>,
                    datasets: [{
                        label: 'Daily Sales',
                        data: <?php echo json_encode(array_column($salesData, 'daily_sales')); ?>,
                        borderColor: '#2D5BFF',
                        backgroundColor: 'rgba(45, 91, 255, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Products Chart
            const productsCtx = document.getElementById('productsChart').getContext('2d');
            const productsChart = new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($productsData, 'name')); ?>,
                    datasets: [{
                        label: 'Units Sold',
                        data: <?php echo json_encode(array_column($productsData, 'total_sold')); ?>,
                        backgroundColor: '#FF9E1A'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($categoriesData, 'category')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($categoriesData, 'product_count')); ?>,
                        backgroundColor: [
                            '#2D5BFF', '#FF9E1A', '#34C759', '#FF3B30', '#8E8E93'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Stock Chart
            const stockCtx = document.getElementById('stockChart').getContext('2d');
            const stockChart = new Chart(stockCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_slice(array_column($productsData, 'name'), 0, 5)); ?>,
                    datasets: [{
                        label: 'Stock Level',
                        data: <?php echo json_encode(array_slice(array_column($productsData, 'total_sold'), 0, 5)); ?>,
                        backgroundColor: '#34C759'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Inventory Categories Chart
            const inventoryCategoriesCtx = document.getElementById('inventoryCategoriesChart').getContext('2d');
            const inventoryCategoriesChart = new Chart(inventoryCategoriesCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($categoriesData, 'category')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($categoriesData, 'product_count')); ?>,
                        backgroundColor: [
                            '#2D5BFF', '#FF9E1A', '#34C759', '#FF3B30', '#8E8E93'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Debts Status Chart
            const debtsStatusCtx = document.getElementById('debtsStatusChart').getContext('2d');
            const debtsStatusChart = new Chart(debtsStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($debtsByStatus, 'status')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($debtsByStatus, 'count')); ?>,
                        backgroundColor: [
                            '#FF9500', '#FF3B30', '#34C759'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Customer Activity Chart
            const customerActivityCtx = document.getElementById('customerActivityChart').getContext('2d');
            const customerActivityChart = new Chart(customerActivityCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($customerActivity, 'date')); ?>,
                    datasets: [{
                        label: 'Active Customers',
                        data: <?php echo json_encode(array_column($customerActivity, 'active_customers')); ?>,
                        borderColor: '#FF9E1A',
                        backgroundColor: 'rgba(255, 158, 26, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

        // Date Filter
        document.getElementById('applyDateFilter').addEventListener('click', function () {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            if (dateFrom && dateTo) {
                alert(`Filtering from ${dateFrom} to ${dateTo}. This would refresh the data in a real application.`);
                // In a real application, you would reload the data with the date filter
            } else {
                alert('Please select both start and end dates.');
            }
        });

        // Export Form
        document.getElementById('exportForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const exportType = document.getElementById('exportType').value;
            const exportFormat = document.getElementById('exportFormat').value;

            window.location.href = `export_reports.php?type=${exportType}&format=${exportFormat}`;
            document.getElementById('exportModal').style.display = 'none';
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>