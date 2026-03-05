<?php
session_start();
require_once 'appearance.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Grocery POS</title>
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

        /* POS Styles */
        .pos-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
            background-color: var(--card-bg);
            border-radius: 12px;
        }

        .product-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-3px);
        }

        .product-card.out-of-stock {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .product-image {
            width: 60px;
            height: 60px;
            background-color: var(--light);
            border-radius: 8px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .product-price {
            color: var(--primary);
            font-weight: 700;
        }

        .product-stock {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .cart-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .cart-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header h2 {
            font-size: 1.5rem;
        }

        .cart-items {
            flex: 1;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
        }

        .item-price {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px;
            font-weight: 700;
            font-family: inherit;
        }

        .quantity-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .cart-summary {
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .payment-options {
            margin: 20px 0;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: white;
            cursor: pointer;
        }

        .payment-option.selected {
            border: 2px solid var(--primary);
        }

        .btn-void {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 1.1rem;
            margin-left: 8px;
            transition: transform 0.2s;
        }

        .btn-void:hover {
            transform: scale(1.2);
        }

        .checkout-btn {

            background-color: var(--accent);
            color: white;
            padding: 15px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .checkout-btn:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
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
            max-width: 500px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        /* Receipt Styles */
        .receipt {
            font-family: 'Courier New', monospace;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            padding: 15px;
            background-color: white;
            border: 1px dashed #ccc;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .receipt-items {
            width: 100%;
            margin-bottom: 15px;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .receipt-total {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
        }

        /* GCash QR Code */
        .gcash-qr {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code {
            width: 300px;
            height: 300px;
            background-color: #ffffff;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .qr-placeholder {
            text-align: center;
            color: var(--gray);
        }

        .qr-placeholder i {
            font-size: 4rem;
            margin-bottom: 15px;
            display: block;
        }

        /* QR Instructions */
        .qr-instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
        }

        .qr-instructions h4 {
            margin-bottom: 10px;
            color: var(--primary);
        }

        .qr-instructions ol {
            padding-left: 20px;
        }

        .qr-instructions li {
            margin-bottom: 8px;
            color: var(--dark);
        }

        /* Cash Modal */
        .cash-input-field {
            width: 100%;
            padding: 15px;
            font-size: 2rem;
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            border: 2px solid var(--primary);
            border-radius: 8px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .change-display {
            text-align: center;
            padding: 15px;
            background: #f0fff4;
            border: 2px solid #34c759;
            border-radius: 8px;
            margin-top: 10px;
        }

        .change-label {
            font-size: 0.9rem;
            color: #666;
        }

        .change-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #34c759;
        }

        .price-input {
            width: 70px;
            border: 1px dashed #ccc;
            font-size: 0.85rem;
            padding: 2px 4px;
            border-radius: 4px;
            color: var(--primary);
            font-weight: 600;
        }

        .price-overridden {
            background-color: #fff9db !important;
            border-color: #fab005 !important;
        }

        /* Debt Form */

        .debt-form {
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        /* QR Management Styles */
        .qr-management-section {
            margin-top: 30px;
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
        }

        .qr-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .qr-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .qr-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .qr-card.default {
            border-color: var(--accent);
            background: linear-gradient(135deg, #ffffff 0%, #f0fff0 100%);
        }

        .qr-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .qr-card-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-default {
            background-color: var(--accent);
            color: white;
        }

        .badge-gcash {
            background-color: #0070BA;
            color: white;
        }

        .badge-maya {
            background-color: #00A3FF;
            color: white;
        }

        .badge-bank {
            background-color: #6C757D;
            color: white;
        }

        .qr-card-image {
            text-align: center;
            margin: 15px 0;
        }

        .qr-card-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .qr-card-details {
            margin: 15px 0;
        }

        .qr-card-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .qr-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        .btn-edit {
            background-color: var(--primary);
            color: white;
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        .btn-set-default {
            background-color: var(--accent);
            color: white;
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

            .pos-container {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
            }

            .qr-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Improved GCash Modal Styles */
        .gcash-modal-content {
            max-width: 500px !important;
            width: 95% !important;
            margin: 20px auto;
            padding: 20px !important;
        }

        .gcash-action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .gcash-action-btn {
            padding: 15px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            min-width: 180px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .gcash-cancel-btn {
            background-color: #f8f9fa;
            color: var(--dark);
            border: 2px solid #ddd;
        }

        .gcash-cancel-btn:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .gcash-confirm-btn {
            background-color: var(--accent);
            color: white;
            border: 2px solid var(--accent);
            flex: 2;
        }

        .gcash-confirm-btn:hover {
            background-color: #2da44e;
            border-color: #2da44e;
        }

        .gcash-amount-display {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
            text-align: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .qr-code {
            height: 250px !important;
        }

        @media (max-width: 480px) {
            .gcash-action-buttons {
                flex-direction: column;
            }

            .gcash-action-btn {
                min-width: 100%;
                width: 100%;
            }

            .qr-code {
                width: 200px;
                height: 200px !important;
            }
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
                        <li><a href="inventory.php"><i class="fas fa-box"></i> Inventory</a></li>
                        <li><a href="pos.php" class="active"><i class="fas fa-cash-register"></i> POS</a></li>
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

    <!-- POS Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Grocery POS (Punto de Benta)</h1>
                <button class="btn btn-primary" id="manageQrBtn"><i class="fas fa-qrcode"></i> Manage QR Codes</button>
            </div>
            <div class="pos-container">
                <div class="products-section">
                    <div class="search-bar-container" style="margin-bottom: 20px; display: flex; gap: 10px;">
                        <div class="search-bar" style="flex: 1; position: relative;">
                            <i class="fas fa-barcode"
                                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                            <input type="text" id="barcodeInput" placeholder="Scan Barcode or Type Product ID... (F2)"
                                autofocus
                                style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #ddd; font-size: 1rem;">
                        </div>
                        <div class="search-bar" style="flex: 1; position: relative;">
                            <i class="fas fa-search"
                                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                            <input type="text" id="searchInput" placeholder="Search by name..."
                                style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #ddd; font-size: 1rem;">
                        </div>
                    </div>
                    <div class="products-grid" id="productsGrid">
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

                        $user_id = $_SESSION['user_id'];

                        // Get products from database for logged-in user only
                        $products_query = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY name");
                        $products_query->bind_param("i", $user_id);
                        $products_query->execute();
                        $products_result = $products_query->get_result();

                        if ($products_result->num_rows > 0) {
                            while ($product = $products_result->fetch_assoc()) {
                                $out_of_stock = $product['stock'] <= 0;
                                $stock_class = $out_of_stock ? 'out-of-stock' : '';

                                echo "<div class='product-card $stock_class' data-id='" . $product['id'] . "' data-name='" . htmlspecialchars($product['name']) . "' data-price='" . $product['price'] . "' data-barcode='" . htmlspecialchars($product['barcode'] ?? '') . "' data-stock='" . $product['stock'] . "'>";

                                echo "<div class='product-image'>";
                                echo "<i class='fas fa-cube'></i>";
                                echo "</div>";
                                echo "<div class='product-name'>" . htmlspecialchars($product['name']) . "</div>";
                                echo "<div class='product-price'>₱" . number_format($product['price'], 2) . "</div>";
                                echo "<div class='product-stock'>Stock: " . $product['stock'] . "</div>";
                                echo "</div>";
                            }
                        } else {
                            echo "<div style='grid-column: 1 / -1; text-align: center; padding: 20px;'>No products found. Add products in the inventory first.</div>";
                        }

                        // Close database connection
                        $conn->close();
                        ?>
                    </div>
                </div>
                <div class="cart-container">
                    <div class="cart-header">
                        <h2>Current Sale</h2><button id="clear-cart"
                            style="background: none; border: none; color: var(--danger); cursor: pointer;"><i
                                class="fas fa-trash"></i>Clear </button>
                    </div>
                    <div class="cart-items" id="cart-items"><!-- Cart items will be populated by JavaScript --></div>
                    <div class="cart-summary">
                        <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
                        <div class="summary-row"><span>Tax</span><span id="tax">₱0.00</span></div>
                        <div class="summary-row summary-total"><span>Total</span><span id="total">₱0.00</span></div>
                    </div>
                    <div class="payment-options">
                        <h3 style="margin-bottom: 15px;">Payment Method</h3>
                        <div class="payment-option selected" data-method="cash"><input type="radio" name="payment"
                                id="cash" checked><label for="cash">Cash</label></div>
                        <div class="payment-option" data-method="gcash"><input type="radio" name="payment"
                                id="gcash"><label for="gcash">GCash</label></div>
                        <div class="payment-option" data-method="credit"><input type="radio" name="payment"
                                id="credit"><label for="credit">Credit (Utang)</label></div>
                    </div><button class="checkout-btn" id="checkout-btn" disabled>Complete Sale</button>
                </div>
            </div><!-- QR Management Section -->
            <div class="qr-management-section" id="qrManagementSection" style="display: none;">
                <div class="qr-management-header">
                    <h2>QR Code Management</h2><button class="btn btn-primary" id="addQrBtn"><i
                            class="fas fa-plus"></i>Add New QR Code </button>
                </div>
                <div class="qr-cards" id="qrCardsContainer"><!-- QR cards will be populated by JavaScript --></div>
            </div>
        </div>
    </section><!-- Receipt Modal -->
    <div class="modal" id="receipt-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Receipt</h2><button class="close-btn">&times;
                </button>
            </div>
            <div class="receipt" id="receipt-content">
                <div class="receipt-header">
                    <h3>WAISTORE</h3>
                    <p>Sari-Sari Store</p>
                    <p>Date: <span id="receipt-date"></span></p>
                </div>
                <div class="receipt-items" id="receipt-items"><!-- Receipt items will be inserted here --></div>
                <div class="receipt-total">
                    <p>Total: <span id="receipt-total"></span></p>
                    <p>Payment Method: <span id="receipt-method"></span></p>
                </div>
            </div><button class="checkout-btn" id="print-receipt">Print Receipt</button>
        </div>
    </div><!-- GCash Modal -->
    <div class="modal" id="gcash-modal">
        <div class="modal-content gcash-modal-content">
            <div class="modal-header">
                <h2>GCash Payment</h2><button class="close-btn">&times;
                </button>
            </div>
            <div class="gcash-qr">
                <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 15px;">Scan the QR code to complete your
                    payment </p>
                <div class="gcash-amount-display">Amount: <span id="gcash-amount">₱0.00</span></div>
                <div class="qr-code" id="dynamic-qr-code" style="height: 250px;">
                    <!-- QR code will be dynamically loaded here -->
                    <div id="no-qr-message" class="qr-placeholder"><i class="fas fa-qrcode"></i>
                        <p>No QR code configured</p><button class="btn btn-primary" id="setup-qr-btn"
                            style="margin-top: 10px;"><i class="fas fa-cog"></i>Setup QR Code </button>
                    </div>
                </div>
                <div id="qr-account-info"
                    style="text-align: center; margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 8px; font-size: 0.9rem;">
                </div>
            </div>
            <div class="gcash-action-buttons"><button type="button" class="gcash-action-btn gcash-cancel-btn"><i
                        class="fas fa-times"></i>Cancel </button><button class="gcash-action-btn gcash-confirm-btn"
                    id="confirm-gcash"><i class="fas fa-check-circle"></i>Payment Completed </button></div>
        </div>
    </div><!-- QR Setup/Edit Modal -->
    <div class="modal" id="qr-setup-modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="qr-modal-title">Setup QR Code</h2><button class="close-btn">&times;
                </button>
            </div>
            <form id="qr-setup-form" enctype="multipart/form-data"><input type="hidden" id="qr-id" name="qr_id"
                    value=""><input type="hidden" id="action-type" name="action" value="upload">
                <div class="form-group"><label for="payment-method">Payment Method</label><select id="payment-method"
                        name="payment_method" required class="form-control">
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                    </select></div>
                <div class="form-group"><label for="account-name">Account Name</label><input type="text"
                        id="account-name" name="account_name" required
                        placeholder="Enter gcash/maya account holder name" class="form-control"></div>
                <div class="form-group"><label for="account-number">Account Number</label><input type="text"
                        id="account-number" name="account_number" required placeholder="Enter account number"
                        class="form-control"></div>
                <div class="form-group"><label for="qr-code-image">QR Code Image</label><input type="file"
                        id="qr-code-image" name="qr_code_image" accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                        class="form-control"><small style="color: var(--gray); display: block; margin-top: 5px;">•
                        Recommended: High-quality PNG or JPEG image<br>• Minimum size: 500x500 pixels<br>• Ensure QR
                        code is clear and not blurry </small>
                    <div id="current-qr-preview" style="margin-top: 10px; display: none;">
                        <p><strong>Current QR Code:</strong></p><img id="current-qr-image" src=""
                            style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                </div>
                <div class="form-group"><label style="display: flex; align-items: center; gap: 8px;"><input
                            type="checkbox" name="is_default" value="1" id="is-default">Set as default QR code </label>
                </div>
                <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;"><button type="button"
                        class="btn btn-outline" style="flex: 1; color: var(--dark); border-color: #ddd;"
                        onclick="document.getElementById('qr-setup-modal').style.display='none'">Cancel </button><button
                        type="submit" class="btn btn-primary" style="flex: 2;" id="qr-save-btn"><i
                            class="fas fa-save"></i>Save QR Code </button></div>
            </form>
        </div>
    </div><!-- Cash Payment Modal -->
    <div class="modal" id="cash-modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Cash Payment</h2><button class="close-btn">&times;
                </button>
            </div>
            <div style="margin-bottom: 20px;">
                <p style="margin-bottom: 5px;">Total Order Amount</p>
                <div id="cash-total-display" style="font-size: 1.5rem; font-weight: 700; color: var(--dark);">₱0.00
                </div>
            </div>
            <div class="form-group"><label for="cash-tendered">Amount Tendered</label><input type="number"
                    id="cash-tendered" class="cash-input-field" placeholder="0.00" step="1" min="0"></div>
            <div class="change-display">
                <div class="change-label">Change due</div>
                <div class="change-value" id="cash-change">₱0.00</div>
            </div><button type="button" class="checkout-btn" id="confirm-cash-btn"
                style="width: 100%; margin-top: 20px;">Confirm Payment & Print </button>
        </div>
    </div><!-- Debt Modal -->
    <div class="modal" id="debt-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Credit Sale</h2><button class="close-btn">&times;
                </button>
            </div>
            <form id="debt-form">
                <div class="debt-form">
                    <div class="form-group"><label for="customer-name">Customer Name *</label><input type="text"
                            id="customer-name" placeholder="Enter customer name" required></div>
                    <div class="form-group"><label for="customer-phone">Phone Number (Optional)</label><input
                            type="text" id="customer-phone" placeholder="Enter phone number"></div>
                    <p><strong>Total Amount: <span id="debt-amount"></span></strong></p>
                </div><button type="submit" class="checkout-btn" id="confirm-debt">Add to Debt List</button>
            </form>
        </div>
    </div><!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>WAISTORE</h3>
                    <p>Smart Grocery Store Management System</p>
                    <p>Empowering Filipino grocery store owners with digital tools for sales,
                        inventory,
                        and utang management.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact & Support</h3>
                    <p><i class="fas fa-envelope"></i>waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i>+63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i>Manila, Philippines</p>
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
                <p>&copy;

                    2025 WAISTORE. All rights reserved</p>
            </div>
        </div>
    </footer>
    <script>document.addEventListener('DOMContentLoaded', function () {
            // Cart state
            let cart = [];
            let currentPaymentMethod = 'cash';

            // DOM Elements
            const productsGrid = document.getElementById('productsGrid');
            const cartItems = document.getElementById('cart-items');
            const subtotalElement = document.getElementById('subtotal');
            const taxElement = document.getElementById('tax');
            const totalElement = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkout-btn');
            const clearCartBtn = document.getElementById('clear-cart');
            const searchInput = document.getElementById('searchInput');
            const paymentOptions = document.querySelectorAll('.payment-option');
            const receiptModal = document.getElementById('receipt-modal');
            const gcashModal = document.getElementById('gcash-modal');
            const cashModal = document.getElementById('cash-modal');
            const debtModal = document.getElementById('debt-modal');

            const qrSetupModal = document.getElementById('qr-setup-modal');
            const closeButtons = document.querySelectorAll('.close-btn');
            const confirmGcashBtn = document.getElementById('confirm-gcash');
            const printReceiptBtn = document.getElementById('print-receipt');
            const setupQrBtn = document.getElementById('setup-qr-btn');
            const qrSetupForm = document.getElementById('qr-setup-form');
            const debtForm = document.getElementById('debt-form');
            const manageQrBtn = document.getElementById('manageQrBtn');
            const qrManagementSection = document.getElementById('qrManagementSection');
            const addQrBtn = document.getElementById('addQrBtn');
            const qrCardsContainer = document.getElementById('qrCardsContainer');
            const qrModalTitle = document.getElementById('qr-modal-title');
            const qrSaveBtn = document.getElementById('qr-save-btn');
            const currentQrPreview = document.getElementById('current-qr-preview');
            const currentQrImage = document.getElementById('current-qr-image');

            // Toggle QR Management Section
            manageQrBtn.addEventListener('click', function () {
                const isVisible = qrManagementSection.style.display === 'block';
                qrManagementSection.style.display = isVisible ? 'none' : 'block';

                if (!isVisible) {
                    loadQRCodes();
                }
            });

            // Add New QR Code
            addQrBtn.addEventListener('click', function () {
                openQrSetupModal();
            });

            // Load QR codes from database
            function loadQRCodes() {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'qr_management.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);

                            if (response.success && response.qr_codes) {
                                displayQRCodes(response.qr_codes);
                            }

                            else {
                                qrCardsContainer.innerHTML = '<p>No QR codes found. Add your first QR code to start accepting digital payments.</p>';
                            }
                        }

                        catch (e) {
                            console.error('Error parsing QR codes response:', e);
                            qrCardsContainer.innerHTML = '<p>Error loading QR codes. Please try again.</p>';
                        }
                    }
                }

                    ;

                xhr.onerror = function () {
                    console.error('Network error loading QR codes');
                    qrCardsContainer.innerHTML = '<p>Network error. Please check your connection.</p>';
                }

                    ;
                xhr.send('action=get_all');
            }

            // Display QR codes in cards
            function displayQRCodes(qrCodes) {
                qrCardsContainer.innerHTML = '';

                if (qrCodes.length === 0) {
                    qrCardsContainer.innerHTML = '<p>No QR codes found. Add your first QR code to start accepting digital payments.</p>';
                    return;
                }

                qrCodes.forEach(qr => {
                    const badgeClass = `badge-${qr.payment_method}

                                `;
                    const isDefault = qr.is_default == 1;

                    const qrCard = document.createElement('div');

                    qrCard.className = `qr-card ${isDefault ? 'default' : ''}

                                `;

                    qrCard.innerHTML = ` <div class="qr-card-header" > <span class="qr-card-badge ${badgeClass}" > ${qr.payment_method.toUpperCase()}

                                </span> ${isDefault ? '<span class="qr-card-badge badge-default">DEFAULT</span>' : ''}

                                </div> <div class="qr-card-image" > <img src="uploads/qr_codes/${qr.qr_code_image}" alt="${qr.payment_method} QR Code" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5RUiBDb2RlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4='" > </div> <div class="qr-card-details" > <div class="qr-card-detail" > <span>Account Name:</span> <span><strong>${qr.account_name}

                                </strong></span> </div> <div class="qr-card-detail" > <span>Account Number:</span> <span><strong>${qr.account_number}

                                </strong></span> </div> <div class="qr-card-detail" > <span>Created:</span> <span>${new Date(qr.created_at).toLocaleDateString()}

                                </span> </div> </div> <div class="qr-card-actions" > <button class="btn btn-edit btn-sm" onclick="editQRCode(${qr.id})" > <i class="fas fa-edit" ></i> Edit </button> <button class="btn btn-delete btn-sm" onclick="deleteQRCode(${qr.id})" > <i class="fas fa-trash" ></i> Delete </button> ${!isDefault ? ` <button class="btn btn-set-default btn-sm" onclick="setDefaultQRCode(${qr.id})" > <i class="fas fa-star" ></i> Set Default </button> ` : ''
                        }

                                </div> `;

                    qrCardsContainer.appendChild(qrCard);
                });
            }

            // Open QR setup modal for new QR code
            function openQrSetupModal() {
                qrModalTitle.textContent = 'Setup QR Code';
                qrSaveBtn.innerHTML = '<i class="fas fa-save"></i> Save QR Code';
                document.getElementById('qr-id').value = '';
                document.getElementById('action-type').value = 'upload';
                document.getElementById('qr-setup-form').reset();
                currentQrPreview.style.display = 'none';
                qrSetupModal.style.display = 'flex';
            }

            // Edit QR code
            window.editQRCode = function (qrId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'qr_management.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);

                            if (response.success && response.qr_code) {
                                const qr = response.qr_code;

                                qrModalTitle.textContent = 'Edit QR Code';
                                qrSaveBtn.innerHTML = '<i class="fas fa-save"></i> Update QR Code';
                                document.getElementById('qr-id').value = qr.id;
                                document.getElementById('action-type').value = 'update';
                                document.getElementById('payment-method').value = qr.payment_method;
                                document.getElementById('account-name').value = qr.account_name;
                                document.getElementById('account-number').value = qr.account_number;
                                document.getElementById('is-default').checked = qr.is_default == 1;

                                // Show current QR code preview
                                currentQrPreview.style.display = 'block';

                                currentQrImage.src = `uploads/qr_codes/${qr.qr_code_image}

                                        `;

                                currentQrImage.onerror = function () {
                                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+';
                                }

                                    ;

                                qrSetupModal.style.display = 'flex';
                            }
                        }

                        catch (e) {
                            console.error('Error parsing QR code data:', e);
                            alert('Error loading QR code data');
                        }
                    }
                }

                    ;

                xhr.onerror = function () {
                    alert('Network error loading QR code data');
                }

                    ;

                xhr.send(`action=get_single&qr_id=${qrId}

                            `);
            }

            // Delete QR code
            window.deleteQRCode = function (qrId) {
                if (confirm('Are you sure you want to delete this QR code? This action cannot be undone.')) {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'qr_management.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);

                                if (response.success) {
                                    alert('QR code deleted successfully!');
                                    loadQRCodes();
                                    // Also reload the GCash modal if it's open
                                    loadQRCodeForPayment();
                                }

                                else {
                                    alert('Error deleting QR code: ' + response.message);
                                }
                            }

                            catch (e) {
                                console.error('Error parsing delete response:', e);
                                alert('Error deleting QR code');
                            }
                        }
                    }

                        ;

                    xhr.onerror = function () {
                        alert('Network error deleting QR code');
                    }

                        ;

                    xhr.send(`action=delete&qr_id=${qrId}

                                `);
                }
            }

            // Set default QR code
            window.setDefaultQRCode = function (qrId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'qr_management.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);

                            if (response.success) {
                                alert('Default QR code updated successfully!');
                                loadQRCodes();
                                // Also reload the GCash modal if it's open
                                loadQRCodeForPayment();
                            }

                            else {
                                alert('Error setting default QR code: ' + response.message);
                            }
                        }

                        catch (e) {
                            console.error('Error parsing set default response:', e);
                            alert('Error setting default QR code');
                        }
                    }
                }

                    ;

                xhr.onerror = function () {
                    alert('Network error setting default QR code');
                }

                    ;

                xhr.send(`action=set_default&qr_id=${qrId}

                            `);
            }

            // QR setup form submission
            qrSetupForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const action = document.getElementById('action-type').value;
                formData.append('action', action);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'qr_management.php', true);

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);

                            if (response.success) {
                                alert(action === 'upload' ? 'QR code setup successfully!' : 'QR code updated successfully!');
                                qrSetupModal.style.display = 'none';
                                loadQRCodes();
                                // Also reload the GCash modal if it's open
                                loadQRCodeForPayment();
                            }

                            else {
                                alert('Error: ' + response.message);
                            }
                        }

                        catch (e) {
                            console.error('Error parsing QR save response:', e);
                            alert('Error saving QR code');
                        }
                    }
                }

                    ;

                xhr.onerror = function () {
                    alert('Network error saving QR code');
                }

                    ;
                xhr.send(formData);
            });

            // Product search
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const productCards = productsGrid.querySelectorAll('.product-card');

                productCards.forEach(card => {
                    const productName = card.querySelector('.product-name').textContent.toLowerCase();
                    const barcode = card.dataset.barcode ? card.dataset.barcode.toLowerCase() : '';

                    if (productName.includes(searchTerm) || barcode.includes(searchTerm)) {
                        card.style.display = 'block';
                    }

                    else {
                        card.style.display = 'none';
                    }
                });
            });

            // Barcode search
            document.getElementById('barcodeInput').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    const code = this.value.trim();

                    if (code) {
                        const card = Array.from(productsGrid.querySelectorAll('.product-card')).find(c => c.dataset.id == code || c.dataset.barcode == code);

                        if (card) {
                            card.click();
                            this.value = '';
                            showToast('Item added!', 'success');
                        }

                        else {
                            showToast('Product not found!', 'error');
                        }
                    }
                }
            });

            // Keyboard Shortcuts
            window.addEventListener('keydown', function (e) {
                if (e.key === 'F2') {
                    e.preventDefault();
                    document.getElementById('barcodeInput').focus();
                }

                else if (e.key === 'F1') {
                    e.preventDefault();
                    if (cart.length > 0) checkoutBtn.click();
                }

                else if (e.key === 'F4') {
                    e.preventDefault();
                    clearCartBtn.click();
                }
            });


            // Add product to cart
            productsGrid.addEventListener('click', function (e) {
                const productCard = e.target.closest('.product-card');
                if (!productCard || productCard.classList.contains('out-of-stock')) return;

                const productId = parseInt(productCard.dataset.id);
                const productName = productCard.dataset.name;
                const productPrice = parseFloat(productCard.dataset.price);
                const productStock = parseInt(productCard.dataset.stock);

                // Check if product is already in cart
                const existingItem = cart.find(item => item.id === productId);

                if (existingItem) {
                    if (existingItem.quantity < productStock) {
                        existingItem.quantity++;
                    }

                    else {
                        alert(`Only ${productStock}

                                        items available in stock`);
                        return;
                    }
                }

                else {
                    cart.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        originalPrice: productPrice,
                        quantity: 1,
                        stock: productStock
                    });

                }

                updateCartDisplay();
            });

            // Update cart display
            function updateCartDisplay() {
                cartItems.innerHTML = '';

                if (cart.length === 0) {
                    cartItems.innerHTML = '<div style="text-align: center; color: var(--gray); padding: 20px;">Cart is empty</div>';
                    checkoutBtn.disabled = true;
                    updateTotals(0, 0, 0);
                    return;
                }

                let subtotal = 0;

                cart.forEach(item => {
                    const itemPriceDisplay = item.price !== item.originalPrice ? `<span style="text-decoration: line-through; font-size: 0.75rem; color: #999;" >₱${item.originalPrice.toFixed(2)}

                            </span> ` : '';

                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;

                    const cartItem = document.createElement('div');
                    cartItem.className = 'cart-item';

                    cartItem.innerHTML = ` <div class="item-details" > <div class="item-name" >${item.name}

                            </div> <div class="item-price" > ${itemPriceDisplay}

                            <input type="number" class="price-input ${item.price !== item.originalPrice ? 'price-overridden' : ''}"

                            data-id="${item.id}" value="${item.price}" step="0.01" title="Type to override price" > </div> </div> <div class="item-quantity" > <button class="quantity-btn" onclick="updateQuantity(${item.id}, 'decrease')" >-</button> <input type="number" class="quantity-input" data-id="${item.id}" value="${item.quantity}" min="1" max="${item.stock}" > <button class="quantity-btn" onclick="updateQuantity(${item.id}, 'increase')" >+</button> </div> <div class="item-total" > ₱${itemTotal.toFixed(2)}

                            <button class="btn-void" onclick="voidItem(${item.id})" title="Remove item" ><i class="fas fa-times-circle" ></i></button> </div> `;
                    cartItems.appendChild(cartItem);
                });

                // Add event listeners to quantity inputs
                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', function () {
                        const productId = parseInt(this.dataset.id);
                        const newVal = parseInt(this.value) || 1;
                        manualUpdateQuantity(productId, newVal);
                    });
                });

                // Add event listeners for price override
                document.querySelectorAll('.price-input').forEach(input => {
                    input.addEventListener('change', function () {
                        const productId = parseInt(this.dataset.id);
                        const newPrice = parseFloat(this.value) || 0;
                        overridePrice(productId, newPrice);
                    });
                });

                const tax = subtotal * 0.00; // 0% tax for now as requested or per current logic
                const total = subtotal + tax;

                updateTotals(subtotal, tax, total);
                checkoutBtn.disabled = false;
            }


            // Update product quantity in cart
            window.updateQuantity = function (productId, action) {
                const item = cart.find(item => item.id === productId);
                if (!item) return;

                if (action === 'increase') {
                    if (item.quantity < item.stock) {
                        item.quantity++;
                    }

                    else {
                        showToast(`Only ${item.stock}

                                items available set in stock`, 'warning');
                    }
                }

                else if (action === 'decrease') {
                    item.quantity--;

                    if (item.quantity === 0) {
                        cart = cart.filter(i => i.id !== productId);
                    }
                }

                updateCartDisplay();
            }

            function manualUpdateQuantity(productId, newQty) {
                const item = cart.find(item => item.id === productId);
                if (!item) return;

                if (newQty > item.stock) {
                    showToast(`Only ${item.stock}

                            set in stock. Adjusted to limit.`, 'warning');
                    item.quantity = item.stock;
                }

                else if (newQty < 1) {
                    item.quantity = 1;
                }

                else {
                    item.quantity = newQty;
                }

                updateCartDisplay();
            }

            function overridePrice(productId, newPrice) {
                const item = cart.find(item => item.id === productId);

                if (item) {
                    item.price = newPrice;
                    updateCartDisplay();
                }
            }

            window.voidItem = function (productId) {

                cart = cart.filter(item => item.id !== productId);
                updateCartDisplay();
            }


            // Update totals
            function updateTotals(subtotal, tax, total) {
                subtotalElement.textContent = `₱${subtotal.toFixed(2)}

                    `;

                taxElement.textContent = `₱${tax.toFixed(2)}

                    `;

                totalElement.textContent = `₱${total.toFixed(2)}

                    `;
            }

            // Clear cart
            clearCartBtn.addEventListener('click', function () {
                if (cart.length > 0 && confirm('Are you sure you want to clear the cart?')) {
                    cart = [];
                    updateCartDisplay();
                }
            });

            // Payment method selection
            paymentOptions.forEach(option => {
                option.addEventListener('click', function () {
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input').checked = true;
                    currentPaymentMethod = this.dataset.method;
                });
            });

            // Checkout button click
            checkoutBtn.addEventListener('click', function () {
                const totalAmount = parseFloat(totalElement.textContent.replace('₱', ''));

                switch (currentPaymentMethod) {
                    case 'cash': processCashPayment(totalAmount);
                        break;
                    case 'gcash': showGcashModal(totalAmount);
                        break;
                    case 'credit': showDebtModal(totalAmount);
                        break;
                }
            });

            // Process cash payment
            function processCashPayment(totalAmount) {
                document.getElementById('cash-total-display').textContent = `₱${totalAmount.toFixed(2)}

                    `;
                document.getElementById('cash-tendered').value = '';
                document.getElementById('cash-change').textContent = '₱0.00';
                cashModal.style.display = 'flex';
                setTimeout(() => document.getElementById('cash-tendered').focus(), 300);
            }

            // Real-time change calculator
            document.getElementById('cash-tendered').addEventListener('input', function () {
                const total = parseFloat(totalElement.textContent.replace('₱', ''));
                const tendered = parseFloat(this.value) || 0;
                const change = Math.max(0, tendered - total);

                document.getElementById('cash-change').textContent = `₱${change.toFixed(2)}

                        `;
            });

            document.getElementById('confirm-cash-btn').addEventListener('click', function () {
                const total = parseFloat(totalElement.textContent.replace('₱', ''));
                const tendered = parseFloat(document.getElementById('cash-tendered').value) || 0;

                if (tendered < total) {
                    if (!confirm('Amount tendered is less than total. Proceed anyway?')) return;
                }

                // Save transaction to database
                saveTransaction('cash', 'paid', total).then(success => {
                    if (success) {
                        cashModal.style.display = 'none';
                        showReceipt('Cash', total);
                    }

                    else {
                        alert('Error processing payment. Please try again.');
                    }
                });
            });


            // Load QR code for payment modal
            function loadQRCodeForPayment() {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'qr_management.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            const qrContainer = document.getElementById('dynamic-qr-code');
                            const noQrMessage = document.getElementById('no-qr-message');
                            const accountInfo = document.getElementById('qr-account-info');

                            if (response.success && response.qr_codes && response.qr_codes.length > 0) {
                                // Find default QR code or use the first one
                                const defaultQr = response.qr_codes.find(qr => qr.is_default) || response.qr_codes[0];

                                qrContainer.innerHTML = `<img src="uploads/qr_codes/${defaultQr.qr_code_image}" alt="${defaultQr.payment_method} QR Code" style="max-width: 100%;" >`;

                                accountInfo.innerHTML = `<small>${defaultQr.account_name}

                                    - ${defaultQr.account_number}

                                    </small>`;
                                noQrMessage.style.display = 'none';
                            }

                            else {
                                noQrMessage.style.display = 'block';
                                accountInfo.innerHTML = '';
                            }
                        }

                        catch (e) {
                            console.error('Error parsing QR code response:', e);
                            noQrMessage.style.display = 'block';
                        }
                    }
                }

                    ;

                xhr.onerror = function () {
                    console.error('Network error loading QR code');
                }

                    ;
                xhr.send('action=get');
            }

            // Show GCash modal
            function showGcashModal(totalAmount) {
                document.getElementById('gcash-amount').textContent = `₱${totalAmount.toFixed(2)}

                    `;
                loadQRCodeForPayment();
                gcashModal.style.display = 'flex';
            }

            // Show debt modal
            function showDebtModal(totalAmount) {
                document.getElementById('debt-amount').textContent = `₱${totalAmount.toFixed(2)}

                    `;
                debtModal.style.display = 'flex';
            }

            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function () {
                    receiptModal.style.display = 'none';
                    gcashModal.style.display = 'none';
                    cashModal.style.display = 'none';
                    debtModal.style.display = 'none';
                    qrSetupModal.style.display = 'none';

                });
            });

            // QR code setup from GCash modal
            setupQrBtn.addEventListener('click', function () {
                gcashModal.style.display = 'none';
                openQrSetupModal();
            });

            // Confirm GCash payment
            confirmGcashBtn.addEventListener('click', function () {
                const totalAmount = parseFloat(totalElement.textContent.replace('₱', ''));

                // Save transaction to database
                saveTransaction('gcash', 'paid', totalAmount).then(success => {
                    if (success) {
                        gcashModal.style.display = 'none';
                        showReceipt('GCash', totalAmount);
                    }

                    else {
                        alert('Error processing GCash payment. Please try again.');
                    }
                });
            });

            // Debt form submission
            debtForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const customerName = document.getElementById('customer-name').value;

                if (!customerName) {
                    alert('Please enter customer name');
                    return;
                }

                const totalAmount = parseFloat(totalElement.textContent.replace('₱', ''));
                const phone = document.getElementById('customer-phone').value;

                // Save transaction and debt to database
                saveTransaction('credit', 'debt', totalAmount, customerName, phone).then(success => {
                    if (success) {
                        // Show success message
                        alert('Credit sale recorded successfully! The items have been added to debt list and stock has been updated.');

                        // Close modal and clear cart
                        debtModal.style.display = 'none';
                        debtForm.reset();
                        showReceipt('Credit', totalAmount, customerName);
                    }

                    else {
                        alert('Error recording credit sale. Please try again.');
                    }
                });
            });

            // Print receipt
            printReceiptBtn.addEventListener('click', function () {
                window.print();
            });

            // Show receipt function
            function showReceipt(paymentMethod, totalAmount, customerName = '') {
                const now = new Date();
                const dateString = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();

                document.getElementById('receipt-date').textContent = dateString;

                document.getElementById('receipt-total').textContent = `₱${totalAmount.toFixed(2)}

                    `;
                document.getElementById('receipt-method').textContent = paymentMethod;

                // Build receipt items
                const receiptItems = document.getElementById('receipt-items');
                receiptItems.innerHTML = '';

                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    const receiptItem = document.createElement('div');
                    receiptItem.className = 'receipt-item';

                    receiptItem.innerHTML = ` <div>${item.name}

                            x${item.quantity}

                            </div> <div>₱${itemTotal.toFixed(2)}

                            </div> `;
                    receiptItems.appendChild(receiptItem);
                });

                // Add customer name if it's a credit sale
                if (customerName) {
                    const customerElement = document.createElement('p');

                    customerElement.textContent = `Customer: ${customerName}

                        `;
                    document.querySelector('.receipt-total').prepend(customerElement);
                }

                receiptModal.style.display = 'flex';

                // Clear cart after successful transaction
                cart = [];
                updateCartDisplay();
            }

            // Save transaction to database (AJAX) - FIXED VERSION
            function saveTransaction(paymentMethod, status, totalAmount, customerName = null, phone = null) {
                return new Promise((resolve) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'save_transaction.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                resolve(response.success);

                                if (!response.success) {
                                    console.error('Error saving transaction:', response.message);
                                }
                            }

                            catch (e) {
                                console.error('Error parsing response:', e);
                                resolve(false);
                            }
                        }

                        else {
                            console.error('HTTP error:', xhr.status);
                            resolve(false);
                        }
                    }

                        ;

                    xhr.onerror = function () {
                        console.error('Network error');
                        resolve(false);
                    }

                        ;

                    const transactionData = {
                        customer_name: customerName,
                        total_amount: totalAmount,
                        payment_method: paymentMethod,
                        status: status,
                        phone: phone,
                        items: cart
                    }

                        ;

                    xhr.send('transaction_data=' + encodeURIComponent(JSON.stringify(transactionData)));
                });
            }

            // Close modal when clicking outside
            window.onclick = function (event) {
                if (event.target.classList.contains('modal')) {
                    receiptModal.style.display = 'none';
                    gcashModal.style.display = 'none';
                    debtModal.style.display = 'none';
                    qrSetupModal.style.display = 'none';
                }
            }
        }); // GCash cancel button

        document.querySelector('.gcash-cancel-btn').addEventListener('click', function () {
            document.getElementById('gcash-modal').style.display = 'none';
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>