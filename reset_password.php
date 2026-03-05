<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: forgot_password.php");
    exit();
}

$token = $_GET['token'];

// Verify token exists and is not expired (1 hour expiration)
$stmt = $conn->prepare("SELECT email, code FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Invalid or expired reset token.'); window.location='forgot_password.php';</script>";
    exit();
}

$reset_data = $result->fetch_assoc();
$email = $reset_data['email'];
$stored_code = $reset_data['code'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_code'])) {
        // Verify code
        $entered_code = $_POST['code'];
        
        if ($entered_code !== $stored_code) {
            $error = "Invalid verification code.";
        } else {
            // Code is correct, show password reset form
            $_SESSION['code_verified'] = true;
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
        }
    } elseif (isset($_POST['reset_password'])) {
        // Reset password
        if (!isset($_SESSION['code_verified']) || !$_SESSION['code_verified'] || !isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
            $error = "Please verify your code first.";
        } else {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);
                
                if ($stmt->execute()) {
                    // Delete the reset token
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    
                    // Clear session
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['code_verified']);
                    
                    // Redirect to login page with success message
                    header("Location: index.php?reset=success");
                    exit();
                } else {
                    $error = "Error resetting password. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - WAISTORE</title>
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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .logo-text {
            font-size: 2.4rem;
            font-weight: 800;
        }

        h1 {
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(45, 91, 255, 0.2);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px 24px;
            background-color: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            text-align: center;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #e58e0c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: var(--light);
            color: var(--dark);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <span class="logo-text">WAISTORE</span>
            </div>
            <h1>Reset Your Password</h1>
            <p>Enter the verification code and set a new password</p>
        </div>

        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['code_verified']) || !$_SESSION['code_verified']): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="code">Verification Code</label>
                        <input type="text" id="code" name="code" required placeholder="Enter the 6-digit code sent to your email">
                    </div>
                    
                    <button type="submit" name="verify_code" class="btn">Verify Code</button>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password">
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <a href="forgot_password.php" class="back-link">Back to Forgot Password</a>
        </div>

        <div class="footer">
            <p>&copy; 2023 WAISTORE. All rights reserved</p>
        </div>
    </div>
    <script src="waistore-global.js"></script>
</body>
</html>