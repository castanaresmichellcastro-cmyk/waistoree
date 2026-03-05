<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - Smart Grocery Store Management</title>
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

        .login-container {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
        }

        .login-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .login-card:hover {
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
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 12px rgba(45, 91, 255, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(45, 91, 255, 0.4);
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

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--border), transparent);
        }

        .divider span {
            background-color: var(--card-bg);
            padding: 0 20px;
            position: relative;
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--gray-light);
            border: 1.5px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
        }

        .social-btn:hover {
            background-color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .social-btn i {
            font-size: 1.2rem;
            color: var(--dark);
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

        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: var(--text);
            border: 1.5px solid var(--border);
            padding: 14px 16px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .google-btn:hover {
            background-color: #f8f9fa;
            border-color: var(--gray);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .google-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
            color: #DB4437;
        }

        .google-signin-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
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

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 14px;
            border: 1px solid #4caf50;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            display: none;
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.1);
        }

        .success-message.show {
            display: block;
        }

        .floating-label {
            position: relative;
            margin-bottom: 24px;
        }

        .floating-label input {
            padding-top: 20px;
        }

        .floating-label label {
            position: absolute;
            top: 18px;
            left: 48px;
            color: var(--gray);
            transition: all 0.3s;
            pointer-events: none;
            font-weight: 500;
        }

        .floating-label input:focus+label,
        .floating-label input:not(:placeholder-shown)+label {
            top: 8px;
            left: 48px;
            font-size: 0.8rem;
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .login-card {
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
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo">
                    <img src="WAIS_LOGO1.png" alt="WAISTORE Logo" style="height: 60px; width: 150;">
                    <span class="logo-text">WAISTORE</span>
                </div>
                <p class="tagline">YOUR SMART GROCERY STORE PARTNER</p>
                <p style="color: var(--gray); font-size: 0.8rem; margin-top: 4px;">Kasangga ng Tindahan Mo</p>
            </div>

            <?php
            // Display error message if login failed
            if (isset($_GET['error'])) {
                if ($_GET['error'] == 1) {
                    echo '<div class="error-message" id="errorMessage">
                            <i class="fas fa-exclamation-circle"></i> 
                            Incorrect <strong>username</strong> or <strong>password</strong>. Please try again.
                          </div>';
                } elseif ($_GET['error'] == 2) {
                    echo '<div class="error-message" id="errorMessage">
                            <i class="fas fa-exclamation-circle"></i> 
                            Email not found. Please check your email address.
                          </div>';
                } elseif ($_GET['error'] == 3) {
                    echo '<div class="error-message" id="errorMessage">
                            <i class="fas fa-exclamation-circle"></i> 
                            Failed to send email. Please try again later.
                          </div>';
                }
            }

            // Display success message if password reset email was sent
            if (isset($_GET['reset']) && $_GET['reset'] == 'sent') {
                echo '<div class="success-message show" id="successMessage">Password reset instructions have been sent to your email.</div>';
            }

            // Display success message if password was changed
            if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
                echo '<div class="success-message show" id="successMessage">Your password has been successfully reset. You can now login with your new password.</div>';
            }

            // Display verification message
            if (isset($_GET['verify']) && $_GET['verify'] == 'success') {
                echo '<div class="success-message show" id="successMessage">Your email has been verified successfully. You can now login.</div>';
            }
            ?>

            <form id="loginForm" action="auth.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary" name="login">
                    <span class="btn-text">Login to Dashboard</span>
                </button>
            </form>

            <div class="signup-link" style="margin-top: 20px;">
                <i class="fas fa-headset" style="color: var(--primary);"></i>
                Need help? Contact <a href="mailto:support@waistore.com" style="color: var(--primary);">WAISTORE
                    Support</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2024-2026 WAISTORE &mdash; Smart Grocery Store Management | <a href="privacy_policy.php">Privacy
                    Policy</a> | <a href="terms_of_use.php">Terms of Service</a></p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Simple validation
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (username && password) {
                // Add loading state to button
                const btn = this.querySelector('button[type="submit"]');
                btn.classList.add('btn-loading');

                // Submit the form after a short delay to show loading state
                setTimeout(() => {
                    this.submit();
                }, 800);
            }
        });

        // Auto-hide messages after 5 seconds
        setTimeout(function () {
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            if (errorMessage) {
                errorMessage.style.display = 'none';
            }

            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);
    </script>
    <script src="waistore-global.js"></script>
</body>

</html>