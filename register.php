<?php
include 'db.php';
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php'; // Adjust path if needed

// Function to generate verification token
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

// Function to send verification email using PHPMailer
function sendVerificationEmail($email, $token, $username)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'monterolorencemanuel@gmail.com'; // Your Gmail
        $mail->Password = 'flvpeamjxzvswndz'; // Your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        // Recipients
        $mail->setFrom('noreply@waistore.com', 'WAISTORE');
        $mail->addAddress($email, $username);
        $mail->addReplyTo('support@waistore.com', 'WAISTORE Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your WAISTORE Account';

        $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #2D5BFF, #1A46E0); color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
                .button { background: #FF9E1A; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>WAISTORE</h1>
                    <p>A SMART TOOL FOR SMALL ENTREPRENEURS</p>
                </div>
                
                <h2>Welcome to WAISTORE, " . htmlspecialchars($username) . "!</h2>
                <p>Thank you for registering with WAISTORE. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $verification_link . "' class='button'>Verify Email Address</a>
                </div>
                
                <p>Or copy and paste this link in your browser:<br>
                <a href='" . $verification_link . "'>" . $verification_link . "</a></p>
                
                <p>If you didn't create an account with WAISTORE, please ignore this email.</p>
                
                <div class='footer'>
                    <p>&copy; 2025 WAISTORE. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Welcome to WAISTORE, $username!\n\nPlease verify your email by visiting: $verification_link";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Alternative simple mail function (if PHPMailer fails)
function sendSimpleVerificationEmail($email, $token, $username)
{
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;

    $subject = "Verify Your WAISTORE Account";
    $message = "
    Welcome to WAISTORE, $username!
    
    Thank you for registering with WAISTORE. To complete your registration and activate your account, please verify your email address by clicking the link below:
    
    $verification_link
    
    If you didn't create an account with WAISTORE, please ignore this email.
    
    Best regards,
    WAISTORE Team
    ";

    $headers = "From: WAISTORE <noreply@waistore.com>\r\n";
    $headers .= "Reply-To: support@waistore.com\r\n";

    return mail($email, $subject, $message, $headers);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $store_name = trim($_POST['store_name']);

    // Check if username already exists
    $check_username_sql = "SELECT id FROM users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $username_result = $check_username_stmt->get_result();

    // Check if email already exists
    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $email_result = $check_email_stmt->get_result();

    if ($username_result->num_rows > 0) {
        $error = "Username already exists. Please choose a different username.";
    } elseif ($email_result->num_rows > 0) {
        $error = "Email address already exists. Please use a different email.";
    } else {
        // Check if terms are agreed to
        if (!isset($_POST['agree_terms']) || !isset($_POST['agree_privacy'])) {
            $error = "You must agree to the Terms of Service and Privacy Policy to register.";
        } else {
            // Generate verification token
            $verification_token = generateToken();

            // Insert new user with verification token (is_verified = 0)
            $sql = "INSERT INTO users (username, password, email, full_name, store_name, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $username, $password, $email, $full_name, $store_name, $verification_token);

            if ($stmt->execute()) {
                // Try to send verification email with PHPMailer first
                $email_sent = sendVerificationEmail($email, $verification_token, $username);

                // If PHPMailer fails, try simple mail
                if (!$email_sent) {
                    $email_sent = sendSimpleVerificationEmail($email, $verification_token, $username);
                }

                if ($email_sent) {
                    $_SESSION['registration_email'] = $email;
                    $_SESSION['registration_username'] = $username;
                    header("Location: registration_success.php");
                    exit();
                } else {
                    // If email fails, still create account but show warning
                    $success = "Account created successfully! However, verification email failed to send. Please check your email settings or contact support.";
                }
            } else {
                $error = "Error creating account. Please try again.";
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
    <title>WAISTORE - Create Your Grocery Store Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        :root {
            --primary: #2D5BFF;
            --primary-light: #5A7FFF;
            --primary-dark: #1A46E0;
            --secondary: #FF9E1A;
            --accent: #34C759;
            --danger: #FF3B30;
            --warning: #FF9500;
            --light: #F8F9FA;
            --dark: #1C1C1E;
            --gray: #8E8E93;
            --gray-light: #F2F2F7;
            --background: #FFFFFF;
            --card-bg: #FFFFFF;
            --border: #E5E5E7;
            --text: #333333;
            --text-light: #666666;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .register-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .register-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
            padding: 20px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(45, 91, 255, 0.05) 0%, rgba(255, 158, 26, 0.05) 100%);
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }

        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .logo img {
            height: 70px;
            width: auto;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .logo-text {
            font-size: 2.4rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
        }

        .tagline {
            color: var(--text-light);
            font-size: 12px;
            margin-bottom: 5px;
            font-style: italic;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text);
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            transition: color 0.3s;
        }

        .input-with-icon input {
            padding-left: 48px;
            transition: all 0.3s;
        }

        input {
            width: 100%;
            padding: 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light);
            color: var(--text);
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(45, 91, 255, 0.15);
            background-color: white;
        }

        input:focus+i {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--secondary), #FFB74D);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 158, 26, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #e58e0c, var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 158, 26, 0.4);
        }

        .btn-loading .btn-text {
            visibility: hidden;
            opacity: 0;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }

            to {
                transform: rotate(1turn);
            }
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .checkbox-group:hover {
            background-color: rgba(45, 91, 255, 0.05);
        }

        .checkbox-group input {
            width: auto;
            margin-right: 12px;
            margin-top: 4px;
        }

        .checkbox-group label {
            font-size: 0.9rem;
            line-height: 1.5;
            cursor: pointer;
        }

        .checkbox-group a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            color: var(--text-light);
            margin-top: 30px;
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 14px;
            border: 1px solid #4caf50;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            display: block;
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.1);
        }

        .error-message {
            background-color: #fdecea;
            color: #b71c1c;
            padding: 14px;
            border: 1px solid #f44336;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 600;
            display: block;
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.1);
        }

        .error-message i {
            margin-right: 6px;
            color: #d32f2f;
        }

        .password-strength {
            margin-top: 8px;
            height: 5px;
            border-radius: 5px;
            background-color: #e0e0e0;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 30px 25px;
            }

            .logo-text {
                font-size: 2rem;
            }

            .logo img {
                height: 60px;
            }

            .tagline {
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-section">
                <div class="logo">
                    <img src="WAIS_LOGO1.png" alt="WAISTORE Logo" style="height: 60px; width: 150;">
                    <span class="logo-text">WAISTORE</span>
                </div>
                <p class="tagline">YOUR SMART GROCERY STORE PARTNER</p>
                <p style="color: var(--gray); font-size: 0.8rem; margin-top: 4px;">Kasangga ng Tindahan Mo</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                    <?php if (strpos($success, 'successfully') !== false && strpos($success, 'verification email failed') === false): ?>
                        <a href="index.php">Login here</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="store_name">Grocery Store Name (Pangalan ng Tindahan)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-store"></i>
                        <input type="text" id="store_name" name="store_name"
                            placeholder="e.g., Juan's Grocery, Aling Maria's Store" required
                            value="<?php echo isset($_POST['store_name']) ? htmlspecialchars($_POST['store_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" id="username" name="username" placeholder="Choose a username" required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter" id="passwordStrength"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Confirm your password" required>
                    </div>
                </div>

                <!-- Agree to Terms Section -->
                <div class="form-group">
                    <label>Agree to Terms:</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="agree_terms" name="agree_terms" value="1" required>
                        <label for="agree_terms">I agree to the <a href="terms_of_use.php" target="_blank">Terms of
                                Service</a></label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="agree_privacy" name="agree_privacy" value="1" required>
                        <label for="agree_privacy">I agree to the <a href="privacy_policy.php" target="_blank">Privacy
                                Policy</a></label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" name="register">
                    <span class="btn-text">Create Account</span>
                </button>
            </form>

            <div class="signup-link">
                Already have an account? <a href="index.php">Login here</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2025 WAISTORE. All rights reserved | <a href="privacy_policy.php">Privacy Policy</a> | <a
                    href="terms_of_use.php">Terms of Service</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return false;
            }

            // Check password length
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }

            // Check if terms are agreed to
            const agreeTerms = document.getElementById('agree_terms').checked;
            const agreePrivacy = document.getElementById('agree_privacy').checked;

            if (!agreeTerms || !agreePrivacy) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy to register.');
                return false;
            }

            // Add loading state to button
            const btn = this.querySelector('button[type="submit"]');
            btn.classList.add('btn-loading');

            return true;
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function () {
            const password = this.value;
            const strengthMeter = document.getElementById('passwordStrength');
            let strength = 0;

            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;

            // Cap at 100%
            strength = Math.min(strength, 100);
            strengthMeter.style.width = strength + '%';

            if (strength < 50) {
                strengthMeter.style.backgroundColor = '#FF3B30';
            } else if (strength < 75) {
                strengthMeter.style.backgroundColor = '#FF9500';
            } else {
                strengthMeter.style.backgroundColor = '#34C759';
            }
        });
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>