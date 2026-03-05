<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - WAISTORE</title>
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
            background-color: rgba(45, 91, 255, 0.1);
            border-left: 4px solid var(--primary);
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
                        <li><a href="privacy_policy.php" class="active"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                        <li><a href="terms_of_use.php"><i class="fas fa-file-contract"></i> Terms of Use</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Privacy Policy Page -->
    <section class="page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <div class="page-header">
                <h1>Privacy Policy</h1>
                <p>Last Updated: January 1, 2025</p>
            </div>

            <div class="content-card">
                <div class="section">
                    <h2>Introduction</h2>
                    <p>Welcome to WAISTORE. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our store management application and related services.</p>
                    
                    <div class="highlight">
                        <p><strong>Note:</strong> By using WAISTORE, you consent to the data practices described in this policy. If you do not agree with these practices, please do not use our services.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>Information We Collect</h2>
                    
                    <h3>Personal Information</h3>
                    <p>When you register for WAISTORE, we collect:</p>
                    <ul>
                        <li>Full name and contact information</li>
                        <li>Email address and username</li>
                        <li>Store name and business details</li>
                        <li>Phone number and address</li>
                        <li>Payment and billing information</li>
                    </ul>

                    <h3>Business Data</h3>
                    <p>To provide our services, we collect:</p>
                    <ul>
                        <li>Inventory information and product details</li>
                        <li>Sales transactions and customer data</li>
                        <li>Debt records and payment information</li>
                        <li>Financial reports and analytics</li>
                        <li>QR code payment information</li>
                    </ul>

                    <h3>Technical Information</h3>
                    <p>We automatically collect:</p>
                    <ul>
                        <li>Device information and IP address</li>
                        <li>Browser type and version</li>
                        <li>Usage patterns and feature interactions</li>
                        <li>Error logs and performance data</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>How We Use Your Information</h2>
                    <p>We use the collected information for the following purposes:</p>
                    <ul>
                        <li><strong>Service Provision:</strong> To provide and maintain WAISTORE services</li>
                        <li><strong>Business Management:</strong> To help you manage inventory, sales, and debts</li>
                        <li><strong>Communication:</strong> To send important updates and notifications</li>
                        <li><strong>Improvement:</strong> To enhance and optimize our application</li>
                        <li><strong>Security:</strong> To protect against fraud and unauthorized access</li>
                        <li><strong>Compliance:</strong> To meet legal and regulatory requirements</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>Data Sharing and Disclosure</h2>
                    <p>We do not sell your personal information. We may share data with:</p>
                    <ul>
                        <li><strong>Service Providers:</strong> Trusted partners who help us operate our services</li>
                        <li><strong>Payment Processors:</strong> For secure payment transactions</li>
                        <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
                        <li><strong>Business Transfers:</strong> In case of merger, acquisition, or sale</li>
                    </ul>
                    
                    <div class="highlight">
                        <p><strong>Important:</strong> We only share the minimum necessary information and ensure all third parties maintain appropriate security measures.</p>
                    </div>
                </div>

                <div class="section">
                    <h2>Data Security</h2>
                    <p>We implement comprehensive security measures:</p>
                    <ul>
                        <li>Encryption of sensitive data in transit and at rest</li>
                        <li>Regular security assessments and updates</li>
                        <li>Access controls and authentication protocols</li>
                        <li>Secure server infrastructure and backups</li>
                        <li>Employee training on data protection</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>Your Rights and Choices</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access and review your personal information</li>
                        <li>Correct inaccurate or incomplete data</li>
                        <li>Request deletion of your account and data</li>
                        <li>Opt-out of marketing communications</li>
                        <li>Export your business data</li>
                        <li>Withdraw consent where applicable</li>
                    </ul>
                    
                    <p>To exercise these rights, contact us at <strong>waistore1@gmail.com</strong></p>
                </div>

                <div class="section">
                    <h2>Data Retention</h2>
                    <p>We retain your information only as long as necessary:</p>
                    <ul>
                        <li><strong>Active Accounts:</strong> Data is retained while your account is active</li>
                        <li><strong>Inactive Accounts:</strong> Data may be archived after 2 years of inactivity</li>
                        <li><strong>Legal Requirements:</strong> Some data may be retained to comply with laws</li>
                        <li><strong>Business Records:</strong> Financial data is retained for 7 years as required</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>Third-Party Services</h2>
                    <p>WAISTORE integrates with third-party services:</p>
                    <ul>
                        <li><strong>Google Sign-In:</strong> For authentication services</li>
                        <li><strong>Payment Gateways:</strong> For transaction processing</li>
                        <li><strong>Analytics Tools:</strong> For service improvement</li>
                        <li><strong>Cloud Services:</strong> For data storage and processing</li>
                    </ul>
                    <p>These services have their own privacy policies, and we encourage you to review them.</p>
                </div>

                <div class="section">
                    <h2>International Data Transfers</h2>
                    <p>Your data may be processed outside the Philippines. We ensure appropriate safeguards are in place through:</p>
                    <ul>
                        <li>Standard contractual clauses</li>
                        <li>Adequacy decisions where applicable</li>
                        <li>Binding corporate rules</li>
                        <li>Other legally approved mechanisms</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>Changes to This Policy</h2>
                    <p>We may update this Privacy Policy periodically. We will notify you of significant changes by:</p>
                    <ul>
                        <li>Posting the updated policy on our website</li>
                        <li>Sending email notifications to registered users</li>
                        <li>Displaying in-app notifications</li>
                    </ul>
                    <p>Continued use of WAISTORE after changes constitutes acceptance of the updated policy.</p>
                </div>

                <div class="contact-info">
                    <h3>Contact Us</h3>
                    <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
                    <p><i class="fas fa-envelope"></i> Email: waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> Phone: +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Address: Manila, Philippines</p>
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
                    <p><a href="#" style="color: #ccc;">Data Processing Agreement</a></p>
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