<?php
// admin_logout.php
session_start();

// Check if logout is confirmed
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: admin_login.php");
    exit();
}

// If not confirmed, show confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout - WAISTORE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #8E44AD, #6C3483);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .logout-icon {
            font-size: 4rem;
            color: #8E44AD;
            margin-bottom: 20px;
        }

        .logout-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .logout-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .buttons-container {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
        }

        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #3498db;
            color: white;
        }

        .btn-back:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .security-notice {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #8E44AD;
        }

        .security-notice h4 {
            color: #8E44AD;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .security-notice p {
            color: #666;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        @media (max-width: 480px) {
            .logout-container {
                padding: 30px 20px;
            }
            
            .buttons-container {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h1 class="logout-title">Confirm Logout</h1>
        
        <p class="logout-message">
            Are you sure you want to logout from the WAISTORE Admin Panel? 
            You will need to login again to access the administration features.
        </p>

        <form method="POST" action="admin_logout.php">
            <input type="hidden" name="confirm_logout" value="yes">
            <div class="buttons-container">
                <button type="button" class="btn btn-cancel" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Cancel
                </button>
                <button type="submit" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Yes, Logout
                </button>
            </div>
        </form>

        <div class="security-notice">
            <h4><i class="fas fa-shield-alt"></i> Security Notice</h4>
            <p>For security reasons, please ensure you logout when you're done with your admin session, especially on shared computers.</p>
        </div>
    </div>

    <script>
        function goBack() {
            // Go back to previous page or admin dashboard if no history
            if (document.referrer && document.referrer.includes('admin')) {
                window.history.back();
            } else {
                window.location.href = 'admin.php';
            }
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // ESC key to cancel
            if (event.key === 'Escape') {
                goBack();
            }
            // Enter key to confirm (but only if form is focused)
            if (event.key === 'Enter' && event.target.type !== 'button') {
                event.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Focus management for accessibility
        window.onload = function() {
            document.querySelector('.btn-cancel').focus();
        };
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>