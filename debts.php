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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Utang Records</title>
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

        /* Debts Styles */
        .debts-tabs {
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

        .debts-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-bar {
            flex: 1;
            position: relative;
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

        .debts-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .debts-table th,
        .debts-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .debts-table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .debts-table tr:last-child td {
            border-bottom: none;
        }

        .debt-status {
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

        .status-overdue {
            background-color: rgba(255, 59, 48, 0.2);
            color: var(--danger);
        }

        .status-partial {
            background-color: rgba(45, 91, 255, 0.2);
            color: var(--primary);
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

        .pay-btn {
            background-color: var(--accent);
            color: white;
        }

        .remind-btn {
            background-color: var(--primary);
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

        /* Filter Panel */
        .filter-panel {
            display: none;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-panel h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }

        .filter-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        /* Payment History */
        .payment-history {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-item:last-child {
            border-bottom: none;
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

            .debts-table {
                font-size: 0.9rem;
            }

            .footer-content {
                flex-direction: column;
            }

            .action-cell {
                flex-direction: column;
            }

            .debts-actions {
                flex-direction: column;
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
                        <li><a href="pos.php"><i class="fas fa-cash-register"></i> POS</a></li>
                        <li><a href="debts.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
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

    <!-- Debts Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Utang Records</h1>
                <button class="btn btn-primary" id="newCreditBtn"><i class="fas fa-plus"></i> Bagong Utang</button>
            </div>

            <div class="debts-tabs">
                <div class="tab active" data-tab="all">All Debts</div>
                <div class="tab" data-tab="pending">Pending</div>
                <div class="tab" data-tab="overdue">Overdue</div>
                <div class="tab" data-tab="partial">Partial</div>
                <div class="tab" data-tab="paid">Paid</div>
            </div>

            <div class="filter-panel" id="filterPanel">
                <h3>Filter Debts</h3>
                <div class="filter-options">
                    <div class="form-group">
                        <label for="statusFilter">Status</label>
                        <select id="statusFilter">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dateFrom">From Date</label>
                        <input type="date" id="dateFrom">
                    </div>
                    <div class="form-group">
                        <label for="dateTo">To Date</label>
                        <input type="date" id="dateTo">
                    </div>
                    <div class="form-group">
                        <label for="amountMin">Min Amount (₱)</label>
                        <input type="number" id="amountMin" min="0">
                    </div>
                    <div class="form-group">
                        <label for="amountMax">Max Amount (₱)</label>
                        <input type="number" id="amountMax" min="0">
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-outline" id="resetFilters"
                        style="color: var(--dark); border-color: #ddd;">Reset</button>
                    <button class="btn btn-primary" id="applyFilters">Apply Filters</button>
                </div>
            </div>

            <div class="debts-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search suki or utang...">
                </div>
                <button class="btn btn-outline" id="filterBtn" style="color: var(--dark); border-color: #ddd;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button class="btn btn-outline" id="exportBtn" style="color: var(--dark); border-color: #ddd;">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>

            <table class="debts-table" id="debtsTable">
                <thead>
                    <tr>
                        <th>Suki (Customer)</th>
                        <th>Amount Due</th>
                        <th>Amount Paid</th>
                        <th>Date Issued</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="debtsTableBody">
                    <?php
                    // Get user info from session (already set above)
                    
                    try {
                        // Check if debt_payments table exists
                        $table_check = $conn->query("SHOW TABLES LIKE 'debt_payments'");

                        if ($table_check->num_rows > 0) {
                            // Get debts from database with payment history using prepared statement
                            $debts_query = "SELECT d.*, 
                                           (SELECT SUM(amount_paid) FROM debt_payments dp WHERE dp.debt_id = d.id) as total_paid
                                           FROM debts d 
                                           WHERE d.user_id = ? 
                                           ORDER BY d.created_at DESC";
                            $stmt = $conn->prepare($debts_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $debts_result = $stmt->get_result();
                        } else {
                            // Fallback query if debt_payments table doesn't exist
                            $debts_query = "SELECT d.*, 
                                           COALESCE(d.amount_paid, 0) as total_paid
                                           FROM debts d 
                                           WHERE d.user_id = ? 
                                           ORDER BY d.created_at DESC";
                            $stmt = $conn->prepare($debts_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $debts_result = $stmt->get_result();
                        }

                        if ($debts_result->num_rows > 0) {
                            while ($debt = $debts_result->fetch_assoc()) {
                                $total_paid = $debt['total_paid'] ?: 0;
                                $amount_due = $debt['amount'] - $total_paid;

                                // Determine status and styling
                                $status_class = "";
                                $status_text = "";

                                if ($debt['status'] == 'paid') {
                                    $status_class = "status-paid";
                                    $status_text = "Paid";
                                } elseif ($total_paid > 0 && $total_paid < $debt['amount']) {
                                    $status_class = "status-partial";
                                    $status_text = "Partial";
                                } else {
                                    // Check if overdue
                                    $due_date = strtotime($debt['due_date'] ?: $debt['created_at'] . ' +7 days');
                                    $today = time();

                                    if ($due_date < $today) {
                                        $status_class = "status-overdue";
                                        $status_text = "Overdue";
                                    } else {
                                        $status_class = "status-pending";
                                        $status_text = "Pending";
                                    }
                                }

                                // Get payment history if table exists
                                $payment_history = "";
                                if ($table_check->num_rows > 0) {
                                    $payment_query = "SELECT * FROM debt_payments WHERE debt_id = ? ORDER BY payment_date DESC";
                                    $payment_stmt = $conn->prepare($payment_query);
                                    $payment_stmt->bind_param("i", $debt['id']);
                                    $payment_stmt->execute();
                                    $payment_result = $payment_stmt->get_result();

                                    if ($payment_result && $payment_result->num_rows > 0) {
                                        $payment_history .= '<div class="payment-history"><strong>Payment History:</strong>';
                                        while ($payment = $payment_result->fetch_assoc()) {
                                            $payment_history .= '<div class="payment-item">';
                                            $payment_history .= '<span>' . date('M j, Y', strtotime($payment['payment_date'])) . '</span>';
                                            $payment_history .= '<span>₱' . number_format($payment['amount_paid'], 2) . ' (' . ucfirst($payment['payment_method']) . ')</span>';
                                            $payment_history .= '</div>';
                                        }
                                        $payment_history .= '</div>';
                                    }
                                    $payment_stmt->close();
                                }

                                echo "<tr data-status='" . strtolower($status_text) . "'>";
                                echo "<td>" . htmlspecialchars($debt['customer_name']) .
                                    ($debt['phone'] ? "<br><small>" . htmlspecialchars($debt['phone']) . "</small>" : "") .
                                    $payment_history . "</td>";
                                echo "<td>₱" . number_format($debt['amount'], 2) . "</td>";
                                echo "<td>₱" . number_format($total_paid, 2) . "</td>";
                                echo "<td>" . date('M j, Y', strtotime($debt['created_at'])) . "</td>";
                                echo "<td>" . date('M j, Y', strtotime($debt['due_date'] ?: $debt['created_at'] . ' +7 days')) . "</td>";
                                echo "<td><span class='debt-status " . $status_class . "'>" . $status_text . "</span></td>";
                                echo "<td class='action-cell'>";

                                if ($debt['status'] !== 'paid' && $amount_due > 0) {
                                    echo "<button class='action-btn pay-btn' data-id='" . $debt['id'] . "' data-customer='" . htmlspecialchars($debt['customer_name']) . "' data-amount='" . $amount_due . "' data-total='" . $debt['amount'] . "' data-paid='" . $total_paid . "'><i class='fas fa-money-bill'></i> Pay</button>";
                                    echo "<button class='action-btn remind-btn' data-id='" . $debt['id'] . "' data-customer='" . htmlspecialchars($debt['customer_name']) . "' data-amount='" . $amount_due . "'><i class='fas fa-bell'></i> Remind</button>";
                                } else {
                                    echo "<button class='action-btn pay-btn' disabled><i class='fas fa-money-bill'></i> Paid</button>";
                                    echo "<button class='action-btn remind-btn' disabled><i class='fas fa-bell'></i> Remind</button>";
                                }

                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center;'>No debts found</td></tr>";
                        }

                        if (isset($stmt)) {
                            $stmt->close();
                        }
                    } catch (Exception $e) {
                        echo "<tr><td colspan='7' style='text-align: center; color: var(--danger);'>Error loading debts: " . $e->getMessage() . "</td></tr>";
                    }

                    // Close database connection
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- New Credit Modal -->
    <div class="modal" id="newCreditModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Bagong Utang (New Credit)</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form id="newCreditForm">
                <div class="form-group">
                    <label for="customerName">Pangalan ng Suki (Customer Name) *</label>
                    <input type="text" id="customerName" required>
                </div>
                <div class="form-group">
                    <label for="customerPhone">Phone Number</label>
                    <input type="tel" id="customerPhone">
                </div>
                <div class="form-group">
                    <label for="creditAmount">Halaga ng Utang (Amount) (₱) *</label>
                    <input type="number" id="creditAmount" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="dueDate">Due Date *</label>
                    <input type="date" id="dueDate" required>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline close-modal"
                        style="color: var(--dark); border-color: #ddd;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Credit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Record Payment</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form id="paymentForm">
                <input type="hidden" id="debtId">
                <div class="form-group">
                    <label for="payingCustomer">Customer</label>
                    <input type="text" id="payingCustomer" readonly>
                </div>
                <div class="form-group">
                    <label for="totalAmount">Total Amount</label>
                    <input type="text" id="totalAmount" readonly>
                </div>
                <div class="form-group">
                    <label for="paidSoFar">Paid So Far</label>
                    <input type="text" id="paidSoFar" readonly>
                </div>
                <div class="form-group">
                    <label for="debtAmount">Amount Due</label>
                    <input type="text" id="debtAmount" readonly>
                </div>
                <div class="form-group">
                    <label for="paymentAmount">Payment Amount (₱) *</label>
                    <input type="number" id="paymentAmount" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="remainingAmount">Remaining Amount After Payment</label>
                    <input type="text" id="remainingAmount" readonly
                        style="background-color: #f8f9fa; font-weight: bold;">
                </div>
                <div class="form-group">
                    <label for="paymentDate">Payment Date *</label>
                    <input type="date" id="paymentDate" required>
                </div>
                <div class="form-group">
                    <label for="paymentMethod">Payment Method *</label>
                    <select id="paymentMethod" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline close-modal"
                        style="color: var(--dark); border-color: #ddd;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reminder Modal -->
    <div class="modal" id="reminderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Send Reminder</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form id="reminderForm">
                <input type="hidden" id="remindDebtId">
                <div class="form-group">
                    <label for="remindCustomer">Customer</label>
                    <input type="text" id="remindCustomer" readonly>
                </div>
                <div class="form-group">
                    <label for="remindAmount">Amount Due</label>
                    <input type="text" id="remindAmount" readonly>
                </div>
                <div class="form-group">
                    <label for="reminderMethod">Reminder Method *</label>
                    <select id="reminderMethod" required>
                        <option value="sms">SMS</option>
                        <option value="in_person">In Person</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reminderMessage">Message *</label>
                    <textarea id="reminderMessage" rows="4"
                        required>Hello! This is a friendly reminder about your outstanding balance of [AMOUNT]. Please settle your payment at your earliest convenience. Thank you!</textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline close-modal"
                        style="color: var(--dark); border-color: #ddd;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reminder</button>
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
                <p>&copy; 2024-2026 WAISTORE &mdash; Kasangga ng Tindahan Mo. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Modal functionality
        const newCreditModal = document.getElementById('newCreditModal');
        const paymentModal = document.getElementById('paymentModal');
        const reminderModal = document.getElementById('reminderModal');
        const newCreditBtn = document.getElementById('newCreditBtn');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const filterPanel = document.getElementById('filterPanel');
        const filterBtn = document.getElementById('filterBtn');
        const applyFiltersBtn = document.getElementById('applyFilters');
        const resetFiltersBtn = document.getElementById('resetFilters');

        // Open modals
        newCreditBtn.addEventListener('click', () => {
            // Set default due date to 7 days from now
            const defaultDueDate = new Date();
            defaultDueDate.setDate(defaultDueDate.getDate() + 7);
            document.getElementById('dueDate').value = defaultDueDate.toISOString().split('T')[0];

            newCreditModal.style.display = 'flex';
        });

        // Close modals
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                newCreditModal.style.display = 'none';
                paymentModal.style.display = 'none';
                reminderModal.style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === newCreditModal) {
                newCreditModal.style.display = 'none';
            }
            if (e.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
            if (e.target === reminderModal) {
                reminderModal.style.display = 'none';
            }
        });

        // Filter panel toggle
        filterBtn.addEventListener('click', () => {
            filterPanel.style.display = filterPanel.style.display === 'block' ? 'none' : 'block';
        });

        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                tab.classList.add('active');

                // Filter table rows based on tab
                const tabType = tab.getAttribute('data-tab');
                filterTableByTab(tabType);
            });
        });

        // Filter table by tab
        function filterTableByTab(tabType) {
            const rows = document.querySelectorAll('#debtsTableBody tr');

            rows.forEach(row => {
                if (tabType === 'all') {
                    row.style.display = '';
                } else {
                    const status = row.getAttribute('data-status');
                    if (status === tabType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#debtsTableBody tr');

            rows.forEach(row => {
                const customerName = row.cells[0].textContent.toLowerCase();
                if (customerName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Payment amount calculation
        const paymentAmountInput = document.getElementById('paymentAmount');
        paymentAmountInput.addEventListener('input', () => {
            const debtAmount = parseFloat(document.getElementById('debtAmount').value.replace('₱', '').replace(/,/g, ''));
            const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
            const remaining = debtAmount - paymentAmount;

            document.getElementById('remainingAmount').value = '₱' + remaining.toFixed(2);
        });

        // Pay buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('pay-btn') && !e.target.disabled) {
                const debtId = e.target.getAttribute('data-id');
                const customer = e.target.getAttribute('data-customer');
                const amount = e.target.getAttribute('data-amount');
                const total = e.target.getAttribute('data-total');
                const paid = e.target.getAttribute('data-paid');

                document.getElementById('debtId').value = debtId;
                document.getElementById('payingCustomer').value = customer;
                document.getElementById('totalAmount').value = '₱' + parseFloat(total).toFixed(2);
                document.getElementById('paidSoFar').value = '₱' + parseFloat(paid).toFixed(2);
                document.getElementById('debtAmount').value = '₱' + parseFloat(amount).toFixed(2);
                document.getElementById('paymentAmount').value = '';
                document.getElementById('remainingAmount').value = '₱' + parseFloat(amount).toFixed(2);
                document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];

                paymentModal.style.display = 'flex';
            }
        });

        // Remind buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remind-btn') && !e.target.disabled) {
                const debtId = e.target.getAttribute('data-id');
                const customer = e.target.getAttribute('data-customer');
                const amount = e.target.getAttribute('data-amount');

                document.getElementById('remindDebtId').value = debtId;
                document.getElementById('remindCustomer').value = customer;
                document.getElementById('remindAmount').value = '₱' + parseFloat(amount).toFixed(2);

                // Update message with actual amount
                const messageTextarea = document.getElementById('reminderMessage');
                messageTextarea.value = messageTextarea.value.replace('[AMOUNT]', '₱' + parseFloat(amount).toFixed(2));

                reminderModal.style.display = 'flex';
            }
        });

        // New Credit Form Submission
        document.getElementById('newCreditForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                customer_name: document.getElementById('customerName').value,
                amount: parseFloat(document.getElementById('creditAmount').value),
                due_date: document.getElementById('dueDate').value,
                phone: document.getElementById('customerPhone').value,
                notes: document.getElementById('notes').value,
                user_id: <?php echo $user_id; ?>
            };

            try {
                const response = await fetch('save_debt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Credit created successfully!');
                    newCreditModal.style.display = 'none';
                    document.getElementById('newCreditForm').reset();
                    location.reload(); // Reload to show new debt
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error creating credit: ' + error.message);
            }
        });

        // Payment Form Submission
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                debt_id: document.getElementById('debtId').value,
                amount_paid: parseFloat(document.getElementById('paymentAmount').value),
                payment_date: document.getElementById('paymentDate').value,
                payment_method: document.getElementById('paymentMethod').value
            };

            try {
                const response = await fetch('save_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Payment recorded successfully!');
                    paymentModal.style.display = 'none';
                    document.getElementById('paymentForm').reset();
                    location.reload(); // Reload to update debt status
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error recording payment: ' + error.message);
            }
        });

        // Reminder Form Submission
        document.getElementById('reminderForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                debt_id: document.getElementById('remindDebtId').value,
                reminder_method: document.getElementById('reminderMethod').value,
                message: document.getElementById('reminderMessage').value
            };

            try {
                const response = await fetch('send_reminder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Reminder sent successfully!');
                    reminderModal.style.display = 'none';
                    document.getElementById('reminderForm').reset();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error sending reminder: ' + error.message);
            }
        });

        // Apply Filters
        applyFiltersBtn.addEventListener('click', () => {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const amountMin = document.getElementById('amountMin').value;
            const amountMax = document.getElementById('amountMax').value;

            // Implement filter logic here
            filterTable(statusFilter, dateFrom, dateTo, amountMin, amountMax);
            filterPanel.style.display = 'none';
        });

        // Reset Filters
        resetFiltersBtn.addEventListener('click', () => {
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('amountMin').value = '';
            document.getElementById('amountMax').value = '';

            // Show all rows
            const rows = document.querySelectorAll('#debtsTableBody tr');
            rows.forEach(row => row.style.display = '');
        });

        // Filter table function
        function filterTable(status, dateFrom, dateTo, amountMin, amountMax) {
            const rows = document.querySelectorAll('#debtsTableBody tr');

            rows.forEach(row => {
                let showRow = true;

                // Status filter
                if (status !== 'all') {
                    const rowStatus = row.getAttribute('data-status');
                    if (rowStatus !== status) {
                        showRow = false;
                    }
                }

                // Date filter
                if (dateFrom) {
                    const rowDate = new Date(row.cells[3].textContent);
                    const fromDate = new Date(dateFrom);
                    if (rowDate < fromDate) {
                        showRow = false;
                    }
                }

                if (dateTo) {
                    const rowDate = new Date(row.cells[3].textContent);
                    const toDate = new Date(dateTo);
                    if (rowDate > toDate) {
                        showRow = false;
                    }
                }

                // Amount filter
                if (amountMin) {
                    const rowAmount = parseFloat(row.cells[1].textContent.replace('₱', '').replace(/,/g, ''));
                    if (rowAmount < parseFloat(amountMin)) {
                        showRow = false;
                    }
                }

                if (amountMax) {
                    const rowAmount = parseFloat(row.cells[1].textContent.replace('₱', '').replace(/,/g, ''));
                    if (rowAmount > parseFloat(amountMax)) {
                        showRow = false;
                    }
                }

                row.style.display = showRow ? '' : 'none';
            });
        }

        // Export functionality
        document.getElementById('exportBtn').addEventListener('click', () => {
            window.location.href = 'export_debts.php';
        });

        // Set default payment date to today
        document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>