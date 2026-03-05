<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current user settings
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get store settings
$stmt = $conn->prepare("SELECT * FROM store_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$store_settings = $result->fetch_assoc();
$stmt->close();

// Get notification settings
$stmt = $conn->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notification_settings = $result->fetch_assoc();
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_store_settings'])) {
        // Update store settings
        $store_name = $_POST['store_name'];
        $store_address = $_POST['store_address'];
        $store_phone = $_POST['store_phone'];
        $opening_time = $_POST['opening_time'];
        $closing_time = $_POST['closing_time'];
        $open_24_hours = isset($_POST['open_24_hours']) ? 1 : 0;
        $currency = $_POST['currency'];
        $weight_unit = $_POST['weight_unit'];

        if ($store_settings) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE store_settings SET store_name = ?, store_address = ?, store_phone = ?, opening_time = ?, closing_time = ?, open_24_hours = ?, currency = ?, weight_unit = ? WHERE user_id = ?");
            $stmt->bind_param("sssssissi", $store_name, $store_address, $store_phone, $opening_time, $closing_time, $open_24_hours, $currency, $weight_unit, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO store_settings (user_id, store_name, store_address, store_phone, opening_time, closing_time, open_24_hours, currency, weight_unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssiss", $user_id, $store_name, $store_address, $store_phone, $opening_time, $closing_time, $open_24_hours, $currency, $weight_unit);
            $stmt->execute();
            $stmt->close();
        }

        // Update user table
        $stmt = $conn->prepare("UPDATE users SET store_name = ? WHERE id = ?");
        $stmt->bind_param("si", $store_name, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Store settings updated successfully!";
    } elseif (isset($_POST['save_notification_settings'])) {
        // Update notification settings
        $low_stock_alert = isset($_POST['low_stock_alert']) ? 1 : 0;
        $low_stock_threshold = $_POST['low_stock_threshold'];
        $sales_notifications = isset($_POST['sales_notifications']) ? 1 : 0;
        $debt_reminders = isset($_POST['debt_reminders']) ? 1 : 0;
        $report_ready = isset($_POST['report_ready']) ? 1 : 0;
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

        if ($notification_settings) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE notification_settings SET low_stock_alert = ?, low_stock_threshold = ?, sales_notifications = ?, debt_reminders = ?, report_ready = ?, email_notifications = ?, push_notifications = ? WHERE user_id = ?");
            $stmt->bind_param("iiiiiiii", $low_stock_alert, $low_stock_threshold, $sales_notifications, $debt_reminders, $report_ready, $email_notifications, $push_notifications, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO notification_settings (user_id, low_stock_alert, low_stock_threshold, sales_notifications, debt_reminders, report_ready, email_notifications, push_notifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiiiii", $user_id, $low_stock_alert, $low_stock_threshold, $sales_notifications, $debt_reminders, $report_ready, $email_notifications, $push_notifications);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['success_message'] = "Notification settings updated successfully!";
    } elseif (isset($_POST['save_appearance_settings'])) {
        // Update appearance settings
        $theme = $_POST['theme'];
        $language = $_POST['language'];
        $date_format = $_POST['date_format'];
        $time_format = $_POST['time_format'];

        $stmt = $conn->prepare("REPLACE INTO appearance_settings (user_id, theme, language, date_format, time_format) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $theme, $language, $date_format, $time_format);
        $stmt->execute();
        $stmt->close();

        // Update session with new appearance settings
        $_SESSION['theme'] = $theme;
        $_SESSION['language'] = $language;
        $_SESSION['date_format'] = $date_format;
        $_SESSION['time_format'] = $time_format;

        $_SESSION['success_message'] = "Appearance settings updated successfully!";
    } elseif (isset($_POST['save_security_settings'])) {
        // Update security settings
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success_message'] = "Password updated successfully!";
            } else {
                $_SESSION['error_message'] = "New passwords do not match!";
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect!";
        }
    }

    // Redirect to prevent form resubmission
    header("Location: settings.php");
    exit();
}

// Get appearance settings
$stmt = $conn->prepare("SELECT * FROM appearance_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appearance_settings = $result->fetch_assoc();
$stmt->close();

// Set default values if settings don't exist
if (!$store_settings) {
    $store_settings = [
        'store_name' => $user['store_name'] ?: 'My Store',
        'store_address' => '',
        'store_phone' => '',
        'opening_time' => '07:00',
        'closing_time' => '20:00',
        'open_24_hours' => 0,
        'currency' => 'PHP',
        'weight_unit' => 'kg'
    ];
}

if (!$notification_settings) {
    $notification_settings = [
        'low_stock_alert' => 1,
        'low_stock_threshold' => 5,
        'sales_notifications' => 1,
        'debt_reminders' => 1,
        'report_ready' => 1,
        'email_notifications' => 0,
        'push_notifications' => 1
    ];
}

if (!$appearance_settings) {
    $appearance_settings = [
        'theme' => 'light',
        'language' => 'en',
        'date_format' => 'Y-m-d',
        'time_format' => '12'
    ];
}

// Get active tab from URL
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'store';

// Get current theme for body class
$current_theme = $appearance_settings['theme'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Grocery Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <link rel="stylesheet" href="themes.css">
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
            --text-color: #1C1C1E;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            color: var(--text-color);
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
            color: var(--text-color);
        }

        /* Settings Styles */
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }

        .settings-sidebar {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .settings-menu {
            list-style: none;
        }

        .settings-menu-item {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-color);
        }

        .settings-menu-item.active {
            background-color: var(--primary);
            color: white;
        }

        .settings-menu-item:hover:not(.active) {
            background-color: rgba(45, 91, 255, 0.1);
        }

        .settings-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .settings-section {
            margin-bottom: 30px;
        }

        .settings-section h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
            background-color: var(--background);
            color: var(--text-color);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
        }

        .form-check label {
            color: var(--text-color);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-secondary {
            background-color: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7a7a7a;
        }

        .theme-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .theme-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            background-color: var(--background);
        }

        .theme-option.active {
            border-color: var(--primary);
        }

        .theme-preview {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 auto 10px;
        }

        .theme-light {
            background: linear-gradient(135deg, #2D5BFF, #FFFFFF);
        }

        .theme-dark {
            background: linear-gradient(135deg, #2D5BFF, #1C1C1E);
        }

        .theme-blue {
            background: linear-gradient(135deg, #2D5BFF, #1A46E0);
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

            .form-actions {
                flex-direction: column;
            }

            .footer-content {
                flex-direction: column;
            }
        }

        .alert {
            padding: 12px 15px;
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
    </style>
</head>

<body class="theme-<?php echo $current_theme; ?>">
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="WAIS_LOGO.png" alt="WAISTORE Logo" style="height: 60px; width: 150px;">
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

    <!-- Settings Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Grocery Store Settings</h1>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="settings-container">
                <div class="settings-sidebar">
                    <ul class="settings-menu">
                        <li>
                            <a href="?tab=store"
                                class="settings-menu-item <?php echo $active_tab === 'store' ? 'active' : ''; ?>">
                                <i class="fas fa-store"></i> Tindahan Settings
                            </a>
                        </li>
                        <li>
                            <a href="?tab=notifications"
                                class="settings-menu-item <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li>
                            <a href="?tab=appearance"
                                class="settings-menu-item <?php echo $active_tab === 'appearance' ? 'active' : ''; ?>">
                                <i class="fas fa-palette"></i> Appearance
                            </a>
                        </li>
                        <li>
                            <a href="?tab=security"
                                class="settings-menu-item <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                                <i class="fas fa-shield-alt"></i> Security
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="settings-content">
                    <?php if ($active_tab === 'store'): ?>
                        <!-- Store Settings -->
                        <form method="POST">
                            <div class="settings-section">
                                <h2>Grocery Store Information</h2>
                                <div class="form-group">
                                    <label for="store_name">Grocery Store Name (Pangalan ng Tindahan)</label>
                                    <input type="text" id="store_name" name="store_name" class="form-control"
                                        value="<?php echo htmlspecialchars($store_settings['store_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="store_address">Store Address</label>
                                    <textarea id="store_address" name="store_address" class="form-control"
                                        rows="3"><?php echo htmlspecialchars($store_settings['store_address']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="store_phone">Store Phone</label>
                                    <input type="tel" id="store_phone" name="store_phone" class="form-control"
                                        value="<?php echo htmlspecialchars($store_settings['store_phone']); ?>">
                                </div>
                            </div>

                            <div class="settings-section">
                                <h2>Business Hours</h2>
                                <div class="form-check">
                                    <input type="checkbox" id="open_24_hours" name="open_24_hours" class="form-check-input"
                                        <?php echo $store_settings['open_24_hours'] ? 'checked' : ''; ?>>
                                    <label for="open_24_hours">Open 24 Hours</label>
                                </div>
                                <div class="form-group" id="hours-container"
                                    style="<?php echo $store_settings['open_24_hours'] ? 'display: none;' : ''; ?>">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div>
                                            <label for="opening_time">Opening Time</label>
                                            <input type="time" id="opening_time" name="opening_time" class="form-control"
                                                value="<?php echo $store_settings['opening_time']; ?>">
                                        </div>
                                        <div>
                                            <label for="closing_time">Closing Time</label>
                                            <input type="time" id="closing_time" name="closing_time" class="form-control"
                                                value="<?php echo $store_settings['closing_time']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h2>Units & Currency</h2>
                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <select id="currency" name="currency" class="form-control">
                                        <option value="PHP" <?php echo $store_settings['currency'] === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (₱)</option>
                                        <option value="USD" <?php echo $store_settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                        <option value="EUR" <?php echo $store_settings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="weight_unit">Weight Unit</label>
                                    <select id="weight_unit" name="weight_unit" class="form-control">
                                        <option value="kg" <?php echo $store_settings['weight_unit'] === 'kg' ? 'selected' : ''; ?>>Kilograms (kg)</option>
                                        <option value="g" <?php echo $store_settings['weight_unit'] === 'g' ? 'selected' : ''; ?>>Grams (g)</option>
                                        <option value="lbs" <?php echo $store_settings['weight_unit'] === 'lbs' ? 'selected' : ''; ?>>Pounds (lbs)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_store_settings" class="btn btn-primary">Save Store
                                    Settings</button>
                            </div>
                        </form>

                    <?php elseif ($active_tab === 'notifications'): ?>
                        <!-- Notification Settings -->
                        <form method="POST">
                            <div class="settings-section">
                                <h2>Notification Preferences</h2>
                                <div class="form-check">
                                    <input type="checkbox" id="low_stock_alert" name="low_stock_alert"
                                        class="form-check-input" <?php echo $notification_settings['low_stock_alert'] ? 'checked' : ''; ?>>
                                    <label for="low_stock_alert">Low Stock Alerts</label>
                                </div>
                                <div class="form-group" id="stock-threshold-container"
                                    style="margin-left: 30px; <?php echo $notification_settings['low_stock_alert'] ? '' : 'display: none;'; ?>">
                                    <label for="low_stock_threshold">Low Stock Threshold</label>
                                    <input type="number" id="low_stock_threshold" name="low_stock_threshold"
                                        class="form-control"
                                        value="<?php echo $notification_settings['low_stock_threshold']; ?>" min="1">
                                    <small>Send alert when stock falls below this quantity</small>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="sales_notifications" name="sales_notifications"
                                        class="form-check-input" <?php echo $notification_settings['sales_notifications'] ? 'checked' : ''; ?>>
                                    <label for="sales_notifications">Sales Notifications</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="debt_reminders" name="debt_reminders"
                                        class="form-check-input" <?php echo $notification_settings['debt_reminders'] ? 'checked' : ''; ?>>
                                    <label for="debt_reminders">Utang Reminders</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="report_ready" name="report_ready" class="form-check-input"
                                        <?php echo $notification_settings['report_ready'] ? 'checked' : ''; ?>>
                                    <label for="report_ready">Report Ready Notifications</label>
                                </div>
                            </div>

                            <div class="settings-section">
                                <h2>Notification Channels</h2>
                                <div class="form-check">
                                    <input type="checkbox" id="email_notifications" name="email_notifications"
                                        class="form-check-input" <?php echo $notification_settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label for="email_notifications">Email Notifications</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" id="push_notifications" name="push_notifications"
                                        class="form-check-input" <?php echo $notification_settings['push_notifications'] ? 'checked' : ''; ?>>
                                    <label for="push_notifications">Push Notifications</label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_notification_settings" class="btn btn-primary">Save
                                    Notification Settings</button>
                            </div>
                        </form>

                    <?php elseif ($active_tab === 'appearance'): ?>
                        <!-- Appearance Settings -->
                        <form method="POST">
                            <div class="settings-section">
                                <h2>Theme</h2>
                                <div class="theme-options">
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'light' ? 'active' : ''; ?>"
                                        data-theme="light">
                                        <div class="theme-preview theme-light"></div>
                                        <span>Light</span>
                                    </div>
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'dark' ? 'active' : ''; ?>"
                                        data-theme="dark">
                                        <div class="theme-preview theme-dark"></div>
                                        <span>Dark</span>
                                    </div>
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'blue' ? 'active' : ''; ?>"
                                        data-theme="blue">
                                        <div class="theme-preview theme-blue"></div>
                                        <span>Blue</span>
                                    </div>
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'midnight' ? 'active' : ''; ?>"
                                        data-theme="midnight">
                                        <div class="theme-preview theme-midnight"
                                            style="background: #111; border: 1px solid #3752FF;"></div>
                                        <span>Midnight</span>
                                    </div>
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'forest' ? 'active' : ''; ?>"
                                        data-theme="forest">
                                        <div class="theme-preview theme-forest" style="background: #2D6A4F;"></div>
                                        <span>Forest</span>
                                    </div>
                                    <div class="theme-option <?php echo $appearance_settings['theme'] === 'glass' ? 'active' : ''; ?>"
                                        data-theme="glass">
                                        <div class="theme-preview theme-glass"
                                            style="background: linear-gradient(135deg, #6366F1, #EC4899);"></div>
                                        <span>Glass</span>
                                    </div>
                                </div>

                                <input type="hidden" id="theme" name="theme"
                                    value="<?php echo $appearance_settings['theme']; ?>">
                            </div>

                            <div class="settings-section">
                                <h2>Language & Region</h2>
                                <div class="form-group">
                                    <label for="language">Language</label>
                                    <select id="language" name="language" class="form-control">
                                        <option value="en" <?php echo $appearance_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="tl" <?php echo $appearance_settings['language'] === 'tl' ? 'selected' : ''; ?>>Tagalog</option>
                                        <option value="es" <?php echo $appearance_settings['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="date_format">Date Format</label>
                                    <select id="date_format" name="date_format" class="form-control">
                                        <option value="Y-m-d" <?php echo $appearance_settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="m/d/Y" <?php echo $appearance_settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                        <option value="d/m/Y" <?php echo $appearance_settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="time_format">Time Format</label>
                                    <select id="time_format" name="time_format" class="form-control">
                                        <option value="12" <?php echo $appearance_settings['time_format'] === '12' ? 'selected' : ''; ?>>12-hour</option>
                                        <option value="24" <?php echo $appearance_settings['time_format'] === '24' ? 'selected' : ''; ?>>24-hour</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_appearance_settings" class="btn btn-primary">Save
                                    Appearance Settings</button>
                            </div>
                        </form>

                    <?php elseif ($active_tab === 'security'): ?>
                        <!-- Security Settings -->
                        <form method="POST">
                            <div class="settings-section">
                                <h2>Change Password</h2>
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="form-control" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_security_settings" class="btn btn-primary">Change
                                    Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

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
                <p>&copy; 2024-2026 WAISTORE &mdash; Kasangga ng Tindahan Mo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle business hours based on 24-hour checkbox
            const open24Hours = document.getElementById('open_24_hours');
            const hoursContainer = document.getElementById('hours-container');

            if (open24Hours && hoursContainer) {
                open24Hours.addEventListener('change', function () {
                    hoursContainer.style.display = this.checked ? 'none' : 'block';
                });
            }

            // Toggle low stock threshold based on checkbox
            const lowStockAlert = document.getElementById('low_stock_alert');
            const stockThresholdContainer = document.getElementById('stock-threshold-container');

            if (lowStockAlert && stockThresholdContainer) {
                lowStockAlert.addEventListener('change', function () {
                    stockThresholdContainer.style.display = this.checked ? 'block' : 'none';
                });
            }

            // Theme selection
            const themeOptions = document.querySelectorAll('.theme-option');
            const themeInput = document.getElementById('theme');

            themeOptions.forEach(option => {
                option.addEventListener('click', function () {
                    const newTheme = this.getAttribute('data-theme');
                    themeOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    themeInput.value = newTheme;

                    // Live Preview
                    document.body.className = document.body.className.replace(/theme-\S+/g, '');
                    document.body.classList.add('theme-' + newTheme);

                    // Add animation class
                    document.body.style.transition = 'all 0.5s ease';
                });
            });

        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>