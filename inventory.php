<?php
session_start();
require_once 'appearance.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check for success/error messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Product Inventory</title>
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

        /* Inventory Styles */
        .inventory-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-bar {
            flex: 1;
            position: relative;
            min-width: 250px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .filter-section {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-section.active {
            display: block;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .inventory-table th,
        .inventory-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .inventory-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .inventory-table tr:last-child td {
            border-bottom: none;
        }

        .stock-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .stock-high {
            background-color: rgba(52, 199, 89, 0.2);
            color: var(--accent);
        }

        .stock-medium {
            background-color: rgba(255, 149, 0, 0.2);
            color: var(--warning);
        }

        .stock-low {
            background-color: rgba(255, 59, 48, 0.2);
            color: var(--danger);
        }

        .action-cell {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .edit-btn {
            background-color: var(--primary);
            color: white;
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
        }

        .stock-btn {
            background-color: var(--accent);
            color: white;
        }

        .request-btn {
            background-color: var(--warning);
            color: white;
        }

        /* Direct Stock Controls in Table */
        .stock-control-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 20px;
            border: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .stock-control-wrapper:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stock-adjust-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 800;
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stock-adjust-btn.minus {
            color: #FF3B30;
        }

        .stock-adjust-btn.minus:hover {
            background: #FF3B30;
            color: white;
            transform: scale(1.1);
        }

        .stock-adjust-btn.plus {
            color: #34C759;
        }

        .stock-adjust-btn.plus:hover {
            background: #34C759;
            color: white;
            transform: scale(1.1);
        }

        .stock-adjust-btn:active {
            transform: scale(0.9);
        }

        .stock-display-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            min-width: 30px;
            text-align: center;
            font-size: 1.05rem;
            color: var(--dark);
        }

        .stock-adjusting {
            opacity: 0.5;
            pointer-events: none;
        }


        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: var(--dark);
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
            color: var(--dark);
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

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        /* Purchase Requests Section */
        .purchase-requests {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .purchase-requests h3 {
            margin-bottom: 15px;
            color: var(--dark);
        }

        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }

        .requests-table th,
        .requests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .requests-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .priority-high {
            color: var(--danger);
            font-weight: bold;
        }

        .priority-medium {
            color: var(--warning);
            font-weight: bold;
        }

        .priority-low {
            color: var(--accent);
            font-weight: bold;
        }

        .status-pending {
            color: var(--warning);
            font-weight: bold;
        }

        .status-approved {
            color: var(--accent);
            font-weight: bold;
        }

        .status-rejected {
            color: var(--danger);
            font-weight: bold;
        }

        .status-completed {
            color: var(--primary);
            font-weight: bold;
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

            .inventory-table {
                font-size: 0.9rem;
            }

            .footer-content {
                flex-direction: column;
            }

            .action-cell {
                flex-direction: column;
            }
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            transform: translateX(150%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background-color: var(--accent);
        }

        .toast.error {
            background-color: var(--danger);
        }

        .toast.warning {
            background-color: var(--warning);
        }
    </style>
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
                        <li><a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory</a></li>
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

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Inventory Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Product Inventory (Stock ng Tindahan)</h1>
                <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New
                    Item</button>
            </div>

            <!-- Purchase Requests Section -->
            <div class="purchase-requests" id="purchaseRequestsSection" style="display: none;">
                <h3><i class="fas fa-shopping-cart"></i> Low Stock Purchase Requests</h3>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Requested Qty</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseRequestsBody">
                        <!-- Purchase requests will be loaded here -->
                    </tbody>
                </table>
            </div>

            <div class="inventory-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search inventory..." onkeyup="searchProducts()">
                </div>
                <button class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                    onclick="toggleFilter()">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                    onclick="exportInventory()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                    onclick="showLowStock()">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock
                </button>
                <button class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                    onclick="togglePurchaseRequests()">
                    <i class="fas fa-shopping-cart"></i> Purchase Requests
                </button>
            </div>

            <!-- Filter Section -->
            <div id="filterSection" class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="categoryFilter">Category</label>
                        <select id="categoryFilter" onchange="applyFilters()">
                            <option value="">All Categories</option>
                            <?php
                            $conn = new mysqli($servername, $username, $password, $dbname);
                            $categories_query = "SELECT DISTINCT category FROM products WHERE user_id = $user_id AND category IS NOT NULL";
                            $categories_result = $conn->query($categories_query);

                            if ($categories_result->num_rows > 0) {
                                while ($category = $categories_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($category['category']) . "'>" . htmlspecialchars($category['category']) . "</option>";
                                }
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="stockFilter">Stock Status</label>
                        <select id="stockFilter" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="high">In Stock</option>
                            <option value="medium">Low Stock</option>
                            <option value="low">Critical</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sortBy">Sort By</label>
                        <select id="sortBy" onchange="applyFilters()">
                            <option value="name">Product Name</option>
                            <option value="price">Price</option>
                            <option value="stock">Stock Quantity</option>
                            <option value="updated">Last Updated</option>
                        </select>
                    </div>
                </div>
            </div>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <?php
                    // Database connection
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

                    // Get products from database for logged-in user only
                    $products_query = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY name");
                    $products_query->bind_param("i", $user_id);
                    $products_query->execute();
                    $products_result = $products_query->get_result();

                    if ($products_result->num_rows > 0) {
                        while ($product = $products_result->fetch_assoc()) {
                            // Determine stock status
                            $stock_status = "";
                            $status_class = "";
                            $priority = "";

                            if ($product['stock'] > 20) {
                                $stock_status = "In Stock";
                                $status_class = "stock-high";
                                $priority = "low";
                            } elseif ($product['stock'] > 5) {
                                $stock_status = "Low Stock";
                                $status_class = "stock-medium";
                                $priority = "medium";
                            } else {
                                $stock_status = "Critical";
                                $status_class = "stock-low";
                                $priority = "high";
                            }

                            echo "<tr data-id='" . $product['id'] . "' data-priority='" . $priority . "'>";
                            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['category'] ?? 'General') . "</td>";
                            echo "<td>₱" . number_format($product['purchase_price'], 2) . "</td>";
                            echo "<td>₱" . number_format($product['selling_price'], 2) . "</td>";
                            echo "<td style='white-space: nowrap;'>";
                            echo "<div class='stock-control-wrapper' id='stock-ctrl-{$product['id']}'>";
                            echo "  <button class='stock-adjust-btn minus' onclick='quickAdjust({$product['id']}, \"remove\", 1)' title='Subtract 1'>-</button>";
                            echo "  <span class='stock-display-value' id='stock-val-{$product['id']}'>{$product['stock']}</span>";
                            echo "  <button class='stock-adjust-btn plus' onclick='quickAdjust({$product['id']}, \"add\", 1)' title='Add 1'>+</button>";
                            echo "</div>";
                            echo "</td>";
                            echo "<td><span class='stock-status " . $status_class . "'>" . $stock_status . "</span></td>";

                            echo "<td>" . ($product['purchase_date'] ? date('M j, Y', strtotime($product['purchase_date'])) : '-') . "</td>";
                            echo "<td>" . ($product['expiry_date'] ? date('M j, Y', strtotime($product['expiry_date'])) : '-') . "</td>";
                            echo "<td class='action-cell'>";
                            echo "<button class='action-btn edit-btn' onclick='openEditModal(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", " . ($product['selling_price'] ?: $product['price']) . ", " . $product['purchase_price'] . ", " . $product['selling_price'] . ", " . $product['stock'] . ", \"" . htmlspecialchars($product['category'] ?? '') . "\", \"" . htmlspecialchars($product['description'] ?? '') . "\", \"" . $product['purchase_date'] . "\", \"" . $product['expiry_date'] . "\")'><i class='fas fa-edit'></i> Edit</button>";

                            echo "<button class='action-btn stock-btn' onclick='openStockModal(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", " . $product['stock'] . ")'><i class='fas fa-boxes'></i> Stock</button>";
                            if ($product['stock'] <= 10) {
                                echo "<button class='action-btn request-btn' onclick='openRequestModal(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", " . $product['stock'] . ", \"" . $priority . "\")'><i class='fas fa-cart-plus'></i> Request</button>";
                            }
                            echo "<button class='action-btn delete-btn' onclick='deleteProduct(" . $product['id'] . ")'><i class='fas fa-trash'></i> Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' style='text-align: center;'>No products found. Add your first product!</td></tr>";
                    }

                    // Close database connection
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm" method="POST" action="save_product.php">
                <input type="hidden" id="productId" name="product_id">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="product_name" required>
                </div>
                <div class="form-group">
                    <label for="productCategory">Category (Kategorya)</label>
                    <input type="text" id="productCategory" name="product_category"
                        placeholder="e.g., Canned Goods, Beverages" list="categories">
                    <datalist id="categories">
                        <option value="Rice & Grains">
                        <option value="Canned Goods">
                        <option value="Beverages">
                        <option value="Snacks & Chips">
                        <option value="Condiments & Seasonings">
                        <option value="Instant Noodles">
                        <option value="Coffee & Powdered Drinks">
                        <option value="Dairy & Eggs">
                        <option value="Bread & Pastries">
                        <option value="Frozen Foods">
                        <option value="Fresh Goods / Palengke">
                        <option value="Personal Care">
                        <option value="Household & Cleaning">
                        <option value="Cooking Oil & Vinegar">
                        <option value="Sugar, Salt & Flour">
                        <option value="Baby Products">
                        <option value="Tobacco & Cigarettes">
                        <option value="School & Office Supplies">
                        <option value="Others">
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="purchasePrice">Purchase Price (₱) *</label>
                    <input type="number" id="purchasePrice" name="purchase_price" step="0.01" min="0" required
                        onchange="calculateSellingPrice()">
                </div>
                <div class="form-group">
                    <label for="sellingPrice">Selling Price (₱) *</label>
                    <input type="number" id="sellingPrice" name="selling_price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="productStock">Stock Quantity *</label>
                    <input type="number" id="productStock" name="product_stock" min="0" required>
                </div>
                <div class="form-group">
                    <label for="purchaseDate">Purchase Date</label>
                    <input type="date" id="purchaseDate" name="purchase_date">
                </div>
                <div class="form-group">
                    <label for="expiryDate">Expiry Date</label>
                    <input type="date" id="expiryDate" name="expiry_date">
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" name="product_description"
                        placeholder="Optional product description"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                        onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adjust Stock</h2>
                <button class="close-btn" onclick="closeStockModal()">&times;</button>
            </div>
            <form id="stockForm">
                <input type="hidden" id="stockProductId" name="product_id">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="stockProductName" readonly style="background-color: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="text" id="currentStock" readonly style="background-color: #f5f5f5;">
                </div>
                <div id="projectedStockContainer"
                    style="margin-top: -15px; margin-bottom: 15px; font-size: 0.95rem; color: #666; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calculator" style="color: var(--primary);"></i>
                    <span>Resulting Stock: <span id="projectedStock"
                            style="font-weight: 800; color: var(--primary); font-size: 1.1rem;">-</span></span>
                </div>
                <div class="form-group">
                    <label for="stockAction">Action</label>
                    <select id="stockAction" name="stock_action" onchange="toggleStockFields()">
                        <option value="add">Add Stock</option>
                        <option value="remove">Remove Stock</option>
                        <option value="set">Set Stock</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stockQuantity">Quantity *</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span id="stockActionIndicator"
                            style="position: absolute; left: 12px; font-weight: 800; font-size: 1.2rem; pointer-events: none; transition: all 0.3s ease;">+</span>
                        <input type="number" id="stockQuantity" name="stock_quantity" min="1" required
                            style="padding-left: 35px; border-width: 2px;">
                    </div>
                </div>
                <div class="form-group" id="reasonGroup" style="display: none;">
                    <label for="stockReason">Reason (for removal)</label>
                    <input type="text" id="stockReason" name="stock_reason" placeholder="e.g., Damaged, Expired, Sold">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                        onclick="closeStockModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="adjustStock()">Update Stock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Purchase Request</h2>
                <button class="close-btn" onclick="closeRequestModal()">&times;</button>
            </div>
            <form id="requestForm">
                <input type="hidden" id="requestProductId" name="product_id">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="requestProductName" readonly style="background-color: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="text" id="requestCurrentStock" readonly style="background-color: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label for="requestedQuantity">Requested Quantity *</label>
                    <input type="number" id="requestedQuantity" name="requested_quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="requestNotes">Notes</label>
                    <textarea id="requestNotes" name="notes"
                        placeholder="Optional notes about this purchase request"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" style="color: var(--dark); border-color: #ddd;"
                        onclick="closeRequestModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitPurchaseRequest()">Submit
                        Request</button>
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
                    <p>Smart Grocery Store Management System</p>
                    <p>Empowering Filipino grocery store owners with digital tools for sales, inventory, and utang
                        management.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact & Support</h3>
                    <p><i class="fas fa-envelope"></i> waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="about_us.php" style="color: #ccc;">About Us</a></p>
                    <p><a href="dashboard.php" style="color: #ccc;">Features</a></p>
                    <p><a href="faqs.php" style="color: #ccc;">FAQs</a></p>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WAISTORE. All rights reserved</p>
            </div>
        </div>
    </footer>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').style.display = 'flex';
        }

        function openEditModal(id, name, price, purchasePrice, sellingPrice, stock, category, description, purchaseDate, expiryDate) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('purchasePrice').value = purchasePrice;
            document.getElementById('sellingPrice').value = sellingPrice;
            document.getElementById('productStock').value = stock;
            document.getElementById('productCategory').value = category || '';
            document.getElementById('productDescription').value = description || '';
            document.getElementById('purchaseDate').value = purchaseDate || '';
            document.getElementById('expiryDate').value = expiryDate || '';
            document.getElementById('productModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Stock Modal functions
        function openStockModal(id, name, currentStock) {
            document.getElementById('stockProductId').value = id;
            document.getElementById('stockProductName').value = name;
            document.getElementById('currentStock').value = currentStock;
            document.getElementById('stockQuantity').value = '';
            document.getElementById('stockReason').value = '';
            document.getElementById('stockAction').value = 'add';
            document.getElementById('projectedStock').textContent = currentStock;
            toggleStockFields();
            document.getElementById('stockModal').style.display = 'flex';
        }

        function closeStockModal() {
            document.getElementById('stockModal').style.display = 'none';
        }

        function toggleStockFields() {
            const action = document.getElementById('stockAction').value;
            const reasonGroup = document.getElementById('reasonGroup');
            const indicator = document.getElementById('stockActionIndicator');
            const qtyInput = document.getElementById('stockQuantity');

            if (action === 'remove') {
                reasonGroup.style.display = 'block';
                indicator.textContent = '-';
                indicator.style.color = '#FF3B30'; // red
                indicator.style.opacity = '1';
                qtyInput.style.borderColor = '#FF3B30';
                qtyInput.style.paddingLeft = '35px';
            } else if (action === 'add') {
                reasonGroup.style.display = 'none';
                indicator.textContent = '+';
                indicator.style.color = '#34C759'; // green
                indicator.style.opacity = '1';
                qtyInput.style.borderColor = '#34C759';
                qtyInput.style.paddingLeft = '35px';
            } else {
                reasonGroup.style.display = 'none';
                indicator.style.opacity = '0';
                qtyInput.style.borderColor = '#e0e0e0';
                qtyInput.style.paddingLeft = '12px';
            }
            updateProjectedStock();
        }

        function updateProjectedStock() {
            const current = parseInt(document.getElementById('currentStock').value) || 0;
            const qty = parseInt(document.getElementById('stockQuantity').value) || 0;
            const action = document.getElementById('stockAction').value;
            const projectedDisplay = document.getElementById('projectedStock');

            let projected = current;
            if (action === 'add') projected = current + qty;
            else if (action === 'remove') projected = Math.max(0, current - qty);
            else if (action === 'set') projected = qty;

            projectedDisplay.textContent = projected;

            if (projected < current) projectedDisplay.style.color = '#FF3B30';
            else if (projected > current) projectedDisplay.style.color = '#34C759';
            else projectedDisplay.style.color = 'var(--primary)';
        }

        document.getElementById('stockQuantity').addEventListener('input', updateProjectedStock);


        // Request Modal functions
        function openRequestModal(id, name, currentStock, priority) {
            document.getElementById('requestProductId').value = id;
            document.getElementById('requestProductName').value = name;
            document.getElementById('requestCurrentStock').value = currentStock;
            document.getElementById('requestedQuantity').value = Math.max(10, Math.ceil(currentStock * 2));
            document.getElementById('priority').value = priority;
            document.getElementById('requestNotes').value = '';
            document.getElementById('requestModal').style.display = 'flex';
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        // Calculate selling price based on purchase price
        function calculateSellingPrice() {
            const purchasePrice = parseFloat(document.getElementById('purchasePrice').value) || 0;
            const sellingPrice = purchasePrice * 1.3; // 30% markup by default
            document.getElementById('sellingPrice').value = sellingPrice.toFixed(2);
        }

        // Filter and search functions
        function toggleFilter() {
            const filterSection = document.getElementById('filterSection');
            filterSection.classList.toggle('active');
        }

        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.getElementById('inventoryTableBody').getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const productName = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                const category = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();

                if (productName.includes(searchTerm) || category.includes(searchTerm)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        function applyFilters() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;
            const rows = document.getElementById('inventoryTableBody').getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const category = rows[i].getElementsByTagName('td')[1].textContent;
                const priority = rows[i].getAttribute('data-priority');

                let showRow = true;

                if (categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }

                if (stockFilter && priority !== stockFilter) {
                    showRow = false;
                }

                rows[i].style.display = showRow ? '' : 'none';
            }
        }

        function showLowStock() {
            const rows = document.getElementById('inventoryTableBody').getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const priority = rows[i].getAttribute('data-priority');
                rows[i].style.display = (priority === 'medium' || priority === 'high') ? '' : 'none';
            }
        }

        // Purchase Request functions
        function togglePurchaseRequests() {
            const section = document.getElementById('purchaseRequestsSection');
            if (section.style.display === 'none') {
                loadPurchaseRequests();
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }

        function loadPurchaseRequests() {
            // This would typically make an AJAX call to fetch purchase requests
            // For now, we'll simulate with the data we have
            const tbody = document.getElementById('purchaseRequestsBody');
            tbody.innerHTML = '';

            // Get all low stock products and create requests for them
            const rows = document.getElementById('inventoryTableBody').getElementsByTagName('tr');
            let hasRequests = false;

            for (let i = 0; i < rows.length; i++) {
                const priority = rows[i].getAttribute('data-priority');
                if (priority === 'medium' || priority === 'high') {
                    const cells = rows[i].getElementsByTagName('td');
                    const productName = cells[0].textContent;
                    const currentStock = parseInt(cells[4].textContent);
                    const productId = rows[i].getAttribute('data-id');

                    const requestedQty = priority === 'high' ? 50 : 25;

                    const row = document.createElement('tr');
                    row.innerHTML = `
                            <td>${productName}</td>
                            <td>${currentStock}</td>
                            <td>${requestedQty}</td>
                            <td class="priority-${priority}">${priority.charAt(0).toUpperCase() + priority.slice(1)}</td>
                            <td class="status-pending">Pending</td>
                            <td>${new Date().toLocaleDateString()}</td>
                            <td>
                                <button class="action-btn edit-btn" onclick="approveRequest(${productId})">Approve</button>
                                <button class="action-btn delete-btn" onclick="rejectRequest(${productId})">Reject</button>
                            </td>
                        `;
                    tbody.appendChild(row);
                    hasRequests = true;
                }
            }

            if (!hasRequests) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No pending purchase requests</td></tr>';
            }
        }

        function submitPurchaseRequest() {
            const productId = document.getElementById('requestProductId').value;
            const requestedQuantity = document.getElementById('requestedQuantity').value;
            const priority = document.getElementById('priority').value;
            const notes = document.getElementById('requestNotes').value;

            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('user_id', <?php echo $user_id; ?>);
            formData.append('requested_quantity', requestedQuantity);
            formData.append('priority', priority);
            formData.append('notes', notes);

            // Send AJAX request
            fetch('save_purchase_request.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Purchase request submitted successfully!', 'success');
                        closeRequestModal();
                        loadPurchaseRequests(); // Refresh the requests list
                    } else {
                        showToast('Error submitting request: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error submitting request', 'error');
                });
        }

        function approveRequest(productId) {
            // This would typically make an AJAX call to update the request status
            showToast('Purchase request approved!', 'success');
            loadPurchaseRequests(); // Refresh the list
        }

        function rejectRequest(productId) {
            // This would typically make an AJAX call to update the request status
            showToast('Purchase request rejected!', 'warning');
            loadPurchaseRequests(); // Refresh the list
        }

        // Quick stick adjustment for the table
        function quickAdjust(productId, action, qty) {
            const wrapper = document.getElementById(`stock-ctrl-${productId}`);
            const valSpan = document.getElementById(`stock-val-${productId}`);
            const currentStock = parseInt(valSpan.textContent);

            // Basic UI lock
            wrapper.classList.add('stock-adjusting');

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('user_id', <?php echo $user_id; ?>);
            formData.append('action', action);
            formData.append('quantity', qty);
            formData.append('reason', 'Quick table adjustment');

            fetch('adjust_stock.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    wrapper.classList.remove('stock-adjusting');
                    if (data.success) {
                        let newVal = currentStock;
                        if (action === 'add') newVal += qty;
                        else if (action === 'remove') newVal = Math.max(0, currentStock - qty);

                        valSpan.textContent = newVal;

                        // Add a little pop animation
                        valSpan.style.transform = 'scale(1.3)';
                        valSpan.style.color = action === 'add' ? '#34C759' : '#FF3B30';
                        setTimeout(() => {
                            valSpan.style.transform = 'scale(1)';
                            valSpan.style.color = '';
                        }, 200);

                        // We don't reload the page here for "automation" feel, but 
                        // real-time status update would be nice. 
                        // Optional: update status badge color/text if needed.
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    wrapper.classList.remove('stock-adjusting');
                    showToast('Network error', 'error');
                });
        }

        // Stock adjustment function
        function adjustStock() {
            const productId = document.getElementById('stockProductId').value;
            const action = document.getElementById('stockAction').value;
            const quantity = parseInt(document.getElementById('stockQuantity').value);
            const reason = document.getElementById('stockReason').value;

            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('user_id', <?php echo $user_id; ?>);
            formData.append('action', action);
            formData.append('quantity', quantity);
            formData.append('reason', reason);

            // Send AJAX request
            fetch('adjust_stock.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Stock updated successfully!', 'success');
                        closeStockModal();
                        // Refresh the page to show updated stock
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error updating stock: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error updating stock', 'error');
                });
        }

        // Delete product function
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                // Create form data
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('user_id', <?php echo $user_id; ?>);

                // Send AJAX request
                fetch('delete_product.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Product deleted successfully!', 'success');
                            // Remove the row from the table
                            const row = document.querySelector(`tr[data-id="${productId}"]`);
                            if (row) row.remove();
                        } else {
                            showToast('Error deleting product: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error deleting product', 'error');
                    });
            }
        }

        // Export function
        function exportInventory() {
            window.location.href = 'export_inventory.php';
        }

        // Toast notification function
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        }
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>