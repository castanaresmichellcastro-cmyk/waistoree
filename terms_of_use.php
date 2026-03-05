<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - WAISTORE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="waistore-global.css">
    <style>
        /* Reuse your existing CSS variables and styles */
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

        nav a:hover, nav a.active {
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
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Content Styles */
        .content-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
        }

        .section h3 {
            color: var(--dark);
            margin: 20px 0 10px 0;
            font-size: 1.2rem;
        }

        .section p {
            margin-bottom: 15px;
            text-align: justify;
        }

        .section ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .section li {
            margin-bottom: 8px;
        }

        .highlight {
            background-color: rgba(255, 158, 26, 0.1);
            border-left: 4px solid var(--secondary);
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .warning {
            background-color: rgba(255, 59, 48, 0.1);
            border-left: 4px solid var(--danger);
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .contact-info {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-top: 30px;
        }

        .contact-info h3 {
            color: white;
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
            
            .content-card {
                padding: 25px;
            }
            
            .footer-content {
                flex-direction: column;
            }
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--primary-dark);
        }

        .acceptance-box {
            background-color: rgba(52, 199, 89, 0.1);
            border: 2px solid var(--accent);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
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
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a></li>
                        <li><a href="privacy_policy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                        <li><a href="terms_of_use.php" class="active"><i class="fas fa-file-contract"></i> Terms of Use</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Terms of Use Page -->
    <section class="page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <div class="page-header">
                <h1>Terms of Use</h1>
                <p>Last Updated: January 1, 2025</p>
            </div>

            <div class="content-card">
                <div class="acceptance-box">
                    <h3><i class="fas fa-exclamation-circle"></i> Important Notice</h3>
                    <p>By accessing or using WAISTORE, you agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use our services.</p>
                </div>

                <div class="section">
                    <h2>1. Agreement to Terms</h2>
                    <p>These Terms of Use constitute a legally binding agreement made between you, whether personally or on behalf of an entity ("you") and WAISTORE ("we," "us," or "our"), concerning your access to and use of the WAISTORE application and related services.</p>
                    
                    <div class="highlight">
                        <p><strong>Note:</strong> You must be at least 18 years old to use WAISTORE. By using our services, you represent that you are at least 18 years old and have the legal capacity to enter into this agreement.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>2. User Accounts</h2>
                    
                    <h3>2.1 Account Registration</h3>
                    <p>To access WAISTORE, you must register for an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, current, and complete information</li>
                        <li>Maintain and promptly update your account information</li>
                        <li>Maintain the security of your password and accept all risks of unauthorized access</li>
                        <li>Notify us immediately of any unauthorized use of your account</li>
                        <li>Take responsibility for all activities that occur under your account</li>
                    </ul>

                    <h3>2.2 Account Types</h3>
                    <p>WAISTORE offers the following account types:</p>
                    <ul>
                        <li><strong>Free Tier:</strong> Basic features for small businesses</li>
                        <li><strong>Premium Tier:</strong> Advanced features with subscription</li>
                        <li><strong>Enterprise Tier:</strong> Custom solutions for larger businesses</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>3. Services and Features</h2>
                    <p>WAISTORE provides the following core services:</p>
                    <ul>
                        <li><strong>Inventory Management:</strong> Track and manage product stock</li>
                        <li><strong>Point of Sale (POS):</strong> Process customer transactions</li>
                        <li><strong>Debt Tracking:</strong> Monitor customer credits and payments</li>
                        <li><strong>Reporting & Analytics:</strong> Generate business insights</li>
                        <li><strong>Digital Payments:</strong> QR code payment processing</li>
                        <li><strong>Notifications:</strong> Real-time business alerts</li>
                    </ul>
                    
                    <div class="highlight">
                        <p><strong>Service Availability:</strong> We strive to maintain 99.9% uptime but do not guarantee uninterrupted service. We may perform maintenance that could temporarily disrupt access.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>4. User Responsibilities</h2>
                    
                    <h3>4.1 Acceptable Use</h3>
                    <p>You agree to use WAISTORE only for lawful purposes and in accordance with these Terms. You agree not to:</p>
                    <ul>
                        <li>Use the service in any way that violates applicable laws</li>
                        <li>Engage in any fraudulent, deceptive, or illegal activities</li>
                        <li>Attempt to gain unauthorized access to other users' accounts</li>
                        <li>Interfere with or disrupt the service or servers</li>
                        <li>Use the service to store or transmit malicious code</li>
                        <li>Harass, abuse, or harm another person</li>
                    </ul>

                    <h3>4.2 Business Data Accuracy</h3>
                    <p>You are solely responsible for:</p>
                    <ul>
                        <li>The accuracy and completeness of your business data</li>
                        <li>Maintaining proper records and backups</li>
                        <li>Complying with tax and regulatory requirements</li>
                        <li>Verifying transaction details and customer information</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>5. Intellectual Property</h2>
                    <p>The WAISTORE service, including its original content, features, functionality, and design elements are owned by WAISTORE and are protected by international copyright, trademark, and other intellectual property laws.</p>
                    
                    <div class="highlight">
                        <p><strong>Your Data:</strong> You retain all rights to your business data. We only use your data to provide our services as outlined in our Privacy Policy.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>6. Payments and Billing</h2>
                    
                    <h3>6.1 Subscription Fees</h3>
                    <p>Certain features of WAISTORE require payment of subscription fees. By subscribing, you agree to:</p>
                    <ul>
                        <li>Pay all applicable fees and taxes</li>
                        <li>Authorize us to charge your payment method</li>
                        <li>Maintain valid payment information</li>
                        <li>Understand that fees are non-refundable except as required by law</li>
                    </ul>

                    <h3>6.2 Price Changes</h3>
                    <p>We reserve the right to change subscription fees with 30 days' notice. Continued use after price changes constitutes your acceptance of the new pricing.</p>

                    <h3>6.3 Free Trial</h3>
                    <p>We may offer free trials for premium features. At the end of the trial period, you will be automatically charged unless you cancel before the trial ends.</p>
                </div>

                <div class="section">
                    <h2>7. Termination</h2>
                    <p>We may terminate or suspend your account and access to WAISTORE immediately, without prior notice or liability, for any reason, including if you breach these Terms.</p>
                    
                    <p>Upon termination:</p>
                    <ul>
                        <li>Your right to use WAISTORE will immediately cease</li>
                        <li>You must cease all use of the service</li>
                        <li>We may delete your data after a reasonable period</li>
                        <li>Outstanding payments will become immediately due</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>8. Disclaimer of Warranties</h2>
                    <p>WAISTORE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED. TO THE FULLEST EXTENT PERMISSIBLE PURSUANT TO APPLICABLE LAW, WE DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED.</p>
                    
                    <div class="warning">
                        <p><strong>Important:</strong> We do not warrant that the service will be uninterrupted, secure, or error-free. You use WAISTORE at your own risk and discretion.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>9. Limitation of Liability</h2>
                    <p>TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL WAISTORE, ITS DIRECTORS, EMPLOYEES, OR AGENTS BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES ARISING FROM YOUR USE OF THE SERVICE.</p>
                    
                    <p>Our total liability to you for all claims shall not exceed the amount you have paid to us in the last 12 months.</p>
                </div>

                <div class="section">
                    <h2>10. Indemnification</h2>
                    <p>You agree to defend, indemnify, and hold harmless WAISTORE and its affiliates from and against any claims, damages, costs, and expenses, including attorneys' fees, arising from or related to your use of WAISTORE or violation of these Terms.</p>
                </div>

                <div class="section">
                    <h2>11. Governing Law</h2>
                    <p>These Terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions.</p>
                </div>

                <div class="section">
                    <h2>12. Changes to Terms</h2>
                    <p>We reserve the right to modify these Terms at any time. We will notify users of material changes through:</p>
                    <ul>
                        <li>Email notifications to registered users</li>
                        <li>In-app notifications and alerts</li>
                        <li>Updates to the "Last Updated" date</li>
                    </ul>
                    <p>Continued use of WAISTORE after changes constitutes acceptance of the modified Terms.</p>
                </div>

                <div class="section">
                    <h2>13. Contact Information</h2>
                    <p>For questions about these Terms of Use, please contact us:</p>
                </div>

                <div class="contact-info">
                    <h3>WAISTORE Support</h3>
                    <p><i class="fas fa-envelope"></i> Email: waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> Phone: +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Address: Manila, Philippines</p>
                    <p><i class="fas fa-clock"></i> Business Hours: Monday-Friday, 9:00 AM - 6:00 PM PST</p>
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
                    <p>A smart store management and digital wallet app for sari-sari stores</p>
                    <p>Making small business management easier and more efficient</p>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-envelope"></i> waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Legal</h3>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                    <p><a href="terms_of_use.php" style="color: #ccc;">Terms of Use</a></p>
                    <p><a href="#" style="color: #ccc;">Cookie Policy</a></p>
                    <p><a href="#" style="color: #ccc;">Service Level Agreement</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WAISTORE. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="waistore-global.js"></script>
</body>
</html>