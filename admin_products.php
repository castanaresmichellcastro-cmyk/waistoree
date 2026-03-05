<?php
require_once 'admin_auth.php';
require_once 'db_connection.php';

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$low_stock = isset($_GET['low_stock']) ? true : false;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query for products
$query = "SELECT p.*, u.store_name, u.username 
          FROM products p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category_filter)) {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
}

if ($low_stock) {
    $query .= " AND p.stock <= 10"; // Consider stock <= 10 as low stock
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.barcode LIKE ?)";
}
if (!empty($category_filter)) {
    $count_query .= " AND p.category = ?";
}
if ($low_stock) {
    $count_query .= " AND p.stock <= 10";
}

$count_stmt = $pdo->prepare($count_query);
$count_params = [];
if (!empty($search)) {
    $search_term = "%$search%";
    $count_params = array_merge($count_params, [$search_term, $search_term, $search_term]);
}
if (!empty($category_filter)) {
    $count_params[] = $category_filter;
}
$count_stmt->execute($count_params);
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $limit);

// Add pagination to main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get categories for filter
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
    $categories = $categories_stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (!hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } elseif ($product_id > 0) {
        switch ($action) {
            case 'delete_product':
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $success = "Product deleted successfully.";
                } catch (PDOException $e) {
                    $error = "Failed to delete product: " . $e->getMessage();
                }
                break;
                
            case 'update_stock':
                $new_stock = intval($_POST['stock'] ?? 0);
                try {
                    $stmt = $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_stock, $product_id]);
                    $success = "Product stock updated.";
                } catch (PDOException $e) {
                    $error = "Failed to update stock: " . $e->getMessage();
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: admin_products.php?" . ($_SERVER['QUERY_STRING'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        /* Same CSS as admin_users.php - you would typically put this in a separate CSS file */
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
                        <li><a href="admin_products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
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
                <h1>Product Management</h1>
                <p>Manage all products across all stores</p>
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
                    <input type="text" id="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </div>
                
                <select id="category-filter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="filter-checkbox">
                    <input type="checkbox" id="low-stock" <?php echo $low_stock ? 'checked' : ''; ?>>
                    <label for="low-stock">Show Low Stock Only</label>
                </div>
                
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <button class="btn btn-success" onclick="exportProducts()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>

            <!-- Products Table -->
            <div class="content-section">
                <div class="section-title">
                    <i class="fas fa-box"></i> All Products (<?php echo $total_products; ?>)
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Store</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Barcode</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-box" style="font-size: 3rem; color: var(--gray); margin-bottom: 10px; display: block;"></i>
                                    No products found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($products as $product): 
                                $stock_class = $product['stock'] <= 5 ? 'stock-low' : ($product['stock'] <= 10 ? 'stock-medium' : 'stock-high');
                            ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <?php if ($product['description']): ?>
                                        <br><small style="color: var(--gray);"><?php echo htmlspecialchars($product['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($product['store_name'] ?? 'N/A'); ?>
                                    <br><small style="color: var(--gray);">@<?php echo htmlspecialchars($product['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category'] ?? 'General'); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td class="<?php echo $stock_class; ?>">
                                    <?php echo $product['stock']; ?>
                                    <?php if ($product['stock'] <= 5): ?>
                                        <i class="fas fa-exclamation-triangle" title="Low Stock"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" 
                                               class="stock-input" min="0" style="margin-right: 5px;">
                                        <button type="submit" name="action" value="update_stock" class="action-btn stock-btn" title="Update Stock">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                    
                                    <button class="action-btn edit-btn" onclick="viewProductDetails(<?php echo $product['id']; ?>)"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['admin_csrf_token']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="action" value="delete_product" class="action-btn delete-btn" title="Delete Product">
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
            const category = document.getElementById('category-filter').value;
            const lowStock = document.getElementById('low-stock').checked;
            const params = new URLSearchParams();
            
            if (search) params.set('search', search);
            if (category) params.set('category', category);
            if (lowStock) params.set('low_stock', '1');
            
            window.location.href = 'admin_products.php?' + params.toString();
        }

        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function exportProducts() {
            const search = document.getElementById('search').value;
            const category = document.getElementById('category-filter').value;
            const lowStock = document.getElementById('low-stock').checked;
            let url = 'admin_export.php?type=products';
            
            if (search) url += '&search=' + encodeURIComponent(search);
            if (category) url += '&category=' + encodeURIComponent(category);
            if (lowStock) url += '&low_stock=1';
            
            window.location.href = url;
        }

        function viewProductDetails(productId) {
            // In a real application, this would open a modal with product details
            window.location.href = 'admin_product_details.php?id=' + productId;
        }

        // Enter key support for search
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Auto-submit stock update forms
        document.addEventListener('DOMContentLoaded', function() {
            const stockInputs = document.querySelectorAll('.stock-input');
            stockInputs.forEach(input => {
                input.addEventListener('change', function() {
                    this.closest('form').querySelector('button[type="submit"]').click();
                });
            });
        });
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>