<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<tr><td colspan='7' style='text-align: center;'>Please log in first.</td></tr>";
    exit;
}

// Database connection
$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$category = $_POST['category'] ?? '';
$stock_status = $_POST['stock_status'] ?? '';
$sort_by = $_POST['sort_by'] ?? 'name';

// Build query with parameterized user_id
$query = "SELECT * FROM products WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($stock_status)) {
    if ($stock_status === 'high') {
        $query .= " AND stock > 20";
    } elseif ($stock_status === 'medium') {
        $query .= " AND stock BETWEEN 6 AND 20";
    } elseif ($stock_status === 'low') {
        $query .= " AND stock <= 5";
    }
}

// Add sorting
switch ($sort_by) {
    case 'price':
        $query .= " ORDER BY price";
        break;
    case 'stock':
        $query .= " ORDER BY stock";
        break;
    case 'updated':
        $query .= " ORDER BY updated_at DESC";
        break;
    default:
        $query .= " ORDER BY name";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        // Determine stock status
        $stock_label = "";
        $status_class = "";

        if ($product['stock'] > 20) {
            $stock_label = "In Stock";
            $status_class = "stock-high";
        } elseif ($product['stock'] > 5) {
            $stock_label = "Low Stock";
            $status_class = "stock-medium";
        } else {
            $stock_label = "Critical";
            $status_class = "stock-low";
        }

        echo "<tr data-id='" . $product['id'] . "'>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['category'] ?? 'General') . "</td>";
        echo "<td>₱" . number_format($product['price'], 2) . "</td>";
        echo "<td>" . $product['stock'] . "</td>";
        echo "<td><span class='stock-status " . $status_class . "'>" . $stock_label . "</span></td>";
        echo "<td>" . date('M j, Y', strtotime($product['updated_at'])) . "</td>";
        echo "<td class='action-cell'>";
        echo "<button class='action-btn edit-btn' onclick='openEditModal(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", " . $product['price'] . ", " . $product['stock'] . ", \"" . htmlspecialchars($product['category'] ?? '') . "\", \"" . htmlspecialchars($product['description'] ?? '') . "\")'><i class='fas fa-edit'></i> Edit</button>";
        echo "<button class='action-btn stock-btn' onclick='openStockModal(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", " . $product['stock'] . ")'><i class='fas fa-boxes'></i> Stock</button>";
        echo "<button class='action-btn delete-btn' onclick='deleteProduct(" . $product['id'] . ")'><i class='fas fa-trash'></i> Delete</button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7' style='text-align: center;'>No products found matching your criteria.</td></tr>";
}

$stmt->close();
$conn->close();
?>