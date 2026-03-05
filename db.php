<?php
$servername = "localhost:3307";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables if they don't exist
$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50),
    full_name VARCHAR(100),
    store_name VARCHAR(100),
    google_id VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$sqlProducts = "CREATE TABLE IF NOT EXISTS products (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT(6) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$sqlTransactions = "CREATE TABLE IF NOT EXISTS transactions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    customer_name VARCHAR(100),
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'gcash', 'credit') DEFAULT 'cash',
    status ENUM('paid', 'pending', 'debt') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$sqlTransactionItems = "CREATE TABLE IF NOT EXISTS transaction_items (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT(6) UNSIGNED,
    product_id INT(6) UNSIGNED,
    quantity INT(6) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

$sqlDebts = "CREATE TABLE IF NOT EXISTS debts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    customer_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

// Execute table creation
if ($conn->query($sqlUsers) === TRUE) {
    // echo "Table users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

if ($conn->query($sqlProducts) === TRUE) {
    // echo "Table products created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

if ($conn->query($sqlTransactions) === TRUE) {
    // echo "Table transactions created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

if ($conn->query($sqlTransactionItems) === TRUE) {
    // echo "Table transaction_items created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

if ($conn->query($sqlDebts) === TRUE) {
    // echo "Table debts created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}
?>