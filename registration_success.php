<?php
// registration_success.php
session_start();
if (!isset($_SESSION['registration_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['registration_email'];
unset($_SESSION['registration_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - WAISTORE</title>
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

        .success-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .success-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            text-align: center;
        }

        .success-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
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

        .success-icon {
            font-size: 4rem;
            color: var(--accent);
            margin-bottom: 20px;
        }

        h1 {
            color: var(--accent);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .success-content {
            margin-bottom: 30px;
        }

        .success-content p {
            margin-bottom: 15px;
            color: var(--text);
            line-height: 1.6;
        }

        .email-address {
            color: var(--primary);
            font-weight: 600;
            background: rgba(45, 91, 255, 0.1);
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
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

        @media (max-width: 480px) {
            .success-card {
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
            
            h1 {
                font-size: 1.5rem;
            }
            
            .success-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="logo-section">
                <div class="logo">
                    <img src="WAIS_LOGO1.png" alt="WAISTORE Logo" style="height: 60px; width: 150;">
                    <span class="logo-text">WAISTORE</span>
                </div>
                <p class="tagline">A SMART TOOL FOR SMALL ENTREPRENEURS</p>
            </div>

            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1>Registration Successful!</h1>
            
            <div class="success-content">
                <p>We've sent a verification email to:</p>
                <div class="email-address"><?php echo htmlspecialchars($email); ?></div>
                <p>Please check your inbox and click the verification link to activate your account.</p>
                <p>If you don't see the email, please check your spam folder.</p>
            </div>

            <a href="index.php" class="btn btn-primary">Go to Login</a>
        </div>

        <div class="footer">
            <p>&copy; 2025 WAISTORE. All rights reserved | <a href="privacy_policy.php">Privacy Policy</a> | <a href="terms_of_use.php">Terms of Service</a></p>
        </div>
    </div>
    <script src="waistore-global.js"></script>
</body>
</html>