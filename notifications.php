<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['clear_all'])) {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_single'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit();
    }

    if (isset($_POST['mark_as_read'])) {
        echo json_encode(['success' => true]);
        exit();
    }
}


// Get notifications based on filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

switch ($filter) {
    case 'unread':
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        break;
    case 'alerts':
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'alert' ORDER BY created_at DESC");
        break;
    default:
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        break;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count for badge
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['count'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Notifications</title>
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

        /* Notifications Styles */
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .notification-tabs {
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
            text-decoration: none;
            color: var(--dark);
        }

        .tab.active {
            background-color: var(--primary);
            color: white;
        }

        .notification-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .notification-list {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background-color: rgba(45, 91, 255, 0.05);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon.info {
            background-color: rgba(45, 91, 255, 0.1);
            color: var(--primary);
        }

        .notification-icon.warning {
            background-color: rgba(255, 149, 0, 0.1);
            color: var(--warning);
        }

        .notification-icon.alert {
            background-color: rgba(255, 59, 48, 0.1);
            color: var(--danger);
        }

        .notification-icon.success {
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--accent);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notification-message {
            color: var(--gray);
            margin-bottom: 5px;
        }

        .notification-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .notification-actions-item {
            display: flex;
            gap: 10px;
        }

        .mark-as-read {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .mark-as-read:hover {
            text-decoration: underline;
            transform: translateY(-1px);
        }

        /* Timeline Grouping */
        .notification-group-header {
            padding: 15px 20px 5px;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #fff;
        }

        .notification-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .notification-item.unread {
            background-color: rgba(45, 91, 255, 0.04);
            border-left: 4px solid var(--primary);
        }

        .notification-item.removing {
            transform: translateX(100%);
            opacity: 0;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background-color: var(--primary);
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            box-shadow: 0 0 8px var(--primary);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }


        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
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

            .notification-item {
                flex-direction: column;
            }

            .footer-content {
                flex-direction: column;
            }

            .notification-actions {
                flex-direction: column;
            }
        }

        .notification-badge {
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .notification-link {
            position: relative;
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
                    <a href="notifications.php" class="btn btn-outline notification-link">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="settings.php" class="btn btn-outline"><i class="fas fa-cog"></i></a>
                    <a href="account.php" class="btn btn-primary"><i class="fas fa-user"></i> My Account</a>
                    <a href="logout.php" class="btn btn-outline" style="margin-left:10px;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Notifications Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>Notifications</h1>
                <div class="notification-actions">
                    <button type="button" onclick="markAllRead()" class="btn btn-outline"
                        style="color: var(--dark); border-color: #ddd;">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                    <button type="button" onclick="clearAllNotifs()" class="btn btn-outline"
                        style="color: var(--dark); border-color: #ddd;">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </div>

            <div class="notifications-container">
                <div class="notification-tabs">
                    <a href="?filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?filter=unread" class="tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
                    <a href="?filter=alerts" class="tab <?php echo $filter === 'alerts' ? 'active' : ''; ?>">Alerts</a>
                </div>

                <div class="notification-list" id="notificationList">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="far fa-bell-slash" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No notifications yet</h3>
                            <p>We'll notify you when something important happens in your store.</p>
                        </div>
                    <?php else:
                        $current_date_group = '';
                        foreach ($notifications as $notification):
                            $notif_date = date('Y-m-d', strtotime($notification['created_at']));
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));

                            $group_label = 'Earlier';
                            if ($notif_date === $today)
                                $group_label = 'Today';
                            elseif ($notif_date === $yesterday)
                                $group_label = 'Yesterday';

                            if ($current_date_group !== $group_label):
                                $current_date_group = $group_label;
                                echo "<div class='notification-group-header'>$group_label</div>";
                            endif;
                            ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                                id="notif-<?php echo $notification['id']; ?>">
                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                    <?php
                                    $icons = [
                                        'info' => 'fas fa-info-circle',
                                        'warning' => 'fas fa-clock',
                                        'alert' => 'fas fa-exclamation-circle',
                                        'success' => 'fas fa-check-circle'
                                    ];
                                    echo '<i class="' . ($icons[$notification['type']] ?? 'fas fa-bell') . '"></i>';
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="unread-dot"></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-time"><i class="far fa-clock"></i>
                                        <?php echo time_elapsed_string($notification['created_at']); ?></div>
                                </div>
                                <div class="notification-actions-item">
                                    <?php if (!$notification['is_read']): ?>
                                        <button onclick="markRead(<?php echo $notification['id']; ?>)" class="mark-as-read">Mark as
                                            read</button>
                                    <?php endif; ?>
                                    <button onclick="deleteNotif(<?php echo $notification['id']; ?>)" class="mark-as-read"
                                        style="color: var(--gray);"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
        function markRead(id) {
            const formData = new FormData();
            formData.append('mark_as_read', '1');
            formData.append('notification_id', id);

            fetch('notifications.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                const item = document.getElementById(`notif-${id}`);
                item.classList.remove('unread');
                item.querySelector('.unread-dot')?.remove();
                item.querySelector('.mark-as-read')?.remove();

                // Update badge
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    let count = parseInt(badge.textContent) - 1;
                    if (count <= 0) badge.remove();
                    else badge.textContent = count;
                }
            });
        }

        function deleteNotif(id) {
            if (!confirm('Delete this notification?')) return;

            const formData = new FormData();
            formData.append('delete_single', '1'); // We will add this to PHP
            formData.append('notification_id', id);

            const item = document.getElementById(`notif-${id}`);
            item.classList.add('removing');

            fetch('notifications.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                setTimeout(() => item.remove(), 300);
            });
        }

        function markAllRead() {
            const formData = new FormData();
            formData.append('mark_all_read', '1');
            fetch('notifications.php', { method: 'POST', body: formData })
                .then(() => {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        item.querySelector('.unread-dot')?.remove();
                        item.querySelector('.mark-as-read')?.remove();
                    });
                    document.querySelector('.notification-badge')?.remove();
                });
        }

        function clearAllNotifs() {
            if (!confirm('Clear all notifications?')) return;
            const formData = new FormData();
            formData.append('clear_all', '1');
            fetch('notifications.php', { method: 'POST', body: formData })
                .then(() => {
                    const list = document.getElementById('notificationList');
                    list.style.opacity = '0';
                    setTimeout(() => {
                        list.innerHTML = `
                            <div class="empty-state">
                                <i class="far fa-bell-slash" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                                <h3>No notifications yet</h3>
                                <p>We'll notify you when something important happens in your store.</p>
                            </div>
                        `;
                        list.style.opacity = '1';
                    }, 300);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // No auto-refresh needed with AJAX, but keeping it longer or removing it
            // setInterval(() => window.location.reload(), 60000);
        });
    </script>

    <script src="waistore-global.js"></script>
</body>

</html>

<?php
// Helper function to format time
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $days = $diff->d;
    $weeks = floor($days / 7);
    $remaining_days = $days % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
    );

    $out = [];
    if ($diff->y)
        $out['y'] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    if ($diff->m)
        $out['m'] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    if ($weeks)
        $out['w'] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    if ($remaining_days)
        $out['d'] = $remaining_days . ' day' . ($remaining_days > 1 ? 's' : '');
    if ($diff->h)
        $out['h'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
    if ($diff->i)
        $out['i'] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    if ($diff->s && empty($out))
        $out['s'] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');

    if (!$full)
        $out = array_slice($out, 0, 1);
    return $out ? implode(', ', $out) . ' ago' : 'just now';
}
?>