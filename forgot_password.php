<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Check if Composer autoload exists
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composer_autoload)) {
    die("Composer autoload not found. Please run 'composer install'.");
}
require $composer_autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
        $error = 'Please enter your email address.';
    } else {
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'Email not found in our system.';
            } else {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $username = $user['username'];
                
                // Generate a random 6-digit code
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Generate a unique token
                $token = bin2hex(random_bytes(32));
                
                // Delete any existing reset requests for this email
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Store the reset request in database
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token, code, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $email, $token, $code);
                
                if ($stmt->execute()) {
                    // Send email with the code
                    $mail = new PHPMailer(true);

                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'monterolorencemanuel@gmail.com'; // Your Gmail
                        $mail->Password = 'flvpeamjxzvswndz'; // Your App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        
                        // Fix for SSL certificate issue
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );
                        
                        // Recipients
                        $mail->setFrom('monterolorencemanuel@gmail.com', 'WAISTORE');
                        $mail->addAddress($email);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Code - WAISTORE';
                        $mail->Body = "
                            <h2>Password Reset Request</h2>
                            <p>Hello $username,</p>
                            <p>You have requested to reset your password. Please use the following verification code:</p>
                            <h3 style='background-color: #f2f2f2; padding: 10px; display: inline-block;'>$code</h3>
                            <p>This code will expire in 1 hour.</p>
                            <p>If you didn't request this reset, please ignore this email.</p>
                            <br>
                            <p>Thank you,<br>WAISTORE Team</p>
                        ";
                        
                        $mail->AltBody = "Password Reset Code: $code\n\nThis code will expire in 1 hour.";
                        
                        if ($mail->send()) {
                            // Store token in session for verification
                            $_SESSION['reset_token'] = $token;
                            $_SESSION['reset_email'] = $email;
                            
                            // Redirect to reset password page
                            header("Location: reset_password.php?token=" . $token);
                            exit();
                        } else {
                            $error = 'Failed to send email. Please try again later.';
                        }
                    } catch (Exception $e) {
                        $error = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                    }
                } else {
                    $error = 'Error processing your request. Please try again.';
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
    <title>Forgot Password - WAISTORE</title>
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

        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
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
        
        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
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
            <p>Enter your email to receive a verification code</p>
        </div>

        <div class="content">
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your registered email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn">Send Verification Code</button>
            </form>
            
            <a href="index.php" class="back-link">Back to Login</a>
        </div>

        <div class="footer">
            <p>&copy; 2023 WAISTORE. All rights reserved</p>
        </div>
    </div>
    <script src="waistore-global.js"></script>
</body>
</html>