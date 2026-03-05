<?php
session_start();
include 'config.php'; // Include your database configuration

// Only log out if user confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Update logout time in database if user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $logout_time = date('Y-m-d H:i:s');
        
        // Update logout time in database
        $update_sql = "UPDATE users SET last_logout = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $logout_time, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update session activity if session tracking is enabled
    if (isset($_SESSION['session_id'])) {
        $session_id = $_SESSION['session_id'];
        $logout_time = date('Y-m-d H:i:s');
        
        $update_session_sql = "UPDATE user_sessions SET logout_time = ? WHERE session_id = ?";
        $stmt = $conn->prepare($update_session_sql);
        $stmt->bind_param("ss", $logout_time, $session_id);
        $stmt->execute();
        $stmt->close();
    }

    // Destroy all session data
    session_unset();
    session_destroy();

    // Optionally, clear cookies if used for authentication
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    $loggedOut = true;
} else {
    $loggedOut = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .logout-message {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            text-align: center;
        }
        .logout-message i {
            color: #2D5BFF;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .logout-message h2 {
            margin-bottom: 10px;
            color: #2D5BFF;
        }
        .logout-message p {
            margin-bottom: 20px;
            color: #333;
        }
        .logout-message a {
            display: inline-block;
            padding: 10px 24px;
            background: #2D5BFF;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
            margin: 5px;
        }
        .logout-message a:hover {
            background: #1A46E0;
        }
    </style>
</head>
<body>
    <div class="logout-message">
        <?php if ($loggedOut): ?>
            <i class="fas fa-sign-out-alt"></i>
            <h2>You have been logged out</h2>
            <p>Thank you for using WAISTORE.<br>
            Click below to return to the login page.</p>
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        <?php else: ?>
            <i class="fas fa-question-circle"></i>
            <h2>Are you sure you want to log out?</h2>
            <p>Your session will be ended and you'll need to log in again.</p>
            <a href="logout.php?confirm=yes"><i class="fas fa-check"></i> Yes, Log me out</a>
            <a href="dashboard.php"><i class="fas fa-times"></i> Cancel</a>
        <?php endif; ?>
    </div>
    <script src="waistore-global.js"></script>
</body>
</html>