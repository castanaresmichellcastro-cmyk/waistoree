<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - WAISTORE</title>
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

        /* About Us Styles */
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 60px 0;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 50px;
        }

        .hero-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .hero-section p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
            display: block;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .content-section {
            margin-bottom: 50px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .section-header p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: var(--dark);
        }

        .team-section {
            background: var(--card-bg);
            padding: 50px;
            border-radius: 16px;
            margin: 50px 0;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .team-member {
            text-align: center;
        }

        .member-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .member-info h4 {
            margin-bottom: 5px;
            color: var(--dark);
        }

        .member-role {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .member-bio {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            margin: 50px 0;
        }

        .mission-card, .vision-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
        }

        .mission-card {
            border-left: 5px solid var(--secondary);
        }

        .vision-card {
            border-left: 5px solid var(--accent);
        }

        .mission-card h3, .vision-card h3 {
            margin-bottom: 20px;
            color: var(--dark);
        }

        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 60px 40px;
            border-radius: 16px;
            text-align: center;
            margin-top: 50px;
        }

        .cta-section h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            background: #f8f9fa;
        }

        .btn-transparent {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-transparent:hover {
            background: rgba(255, 255, 255, 0.1);
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
            
            .hero-section {
                padding: 40px 20px;
            }
            
            .hero-section h2 {
                font-size: 2rem;
            }
            
            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .mission-vision {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
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

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 40px auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary);
            transform: translateX(-50%);
        }

        .timeline-item {
            margin-bottom: 40px;
            position: relative;
        }

        .timeline-content {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            width: 45%;
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            left: 0;
        }

        .timeline-item:nth-child(even) .timeline-content {
            left: 55%;
        }

        .timeline-year {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-weight: 600;
        }

        .timeline-item:nth-child(odd) .timeline-year {
            right: -80px;
        }

        .timeline-item:nth-child(even) .timeline-year {
            left: -80px;
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
                        <li><a href="about_us.php" class="active"><i class="fas fa-info-circle"></i> About Us</a></li>
                        <li><a href="faqs.php"><i class="fas fa-question-circle"></i> FAQs</a></li>
                        <li><a href="privacy_policy.php"><i class="fas fa-shield-alt"></i> Privacy</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="index.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </header>

    <!-- About Us Page -->
    <section class="page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <!-- Hero Section -->
            <div class="hero-section">
                <h2>Empowering Small Businesses in the Philippines</h2>
                <p>WAISTORE is a comprehensive store management solution designed specifically for sari-sari stores and small retailers, making business management simpler, smarter, and more efficient.</p>
                
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Stores</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50K+</span>
                        <span class="stat-label">Transactions Monthly</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Customer Satisfaction</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support Available</span>
                    </div>
                </div>
            </div>

            <!-- Our Story Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Our Story</h2>
                    <p>From a simple idea to a powerful tool transforming small businesses across the Philippines</p>
                </div>

                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h3>The Beginning</h3>
                            <p>Founded in 2023, WAISTORE started as a college project aimed at helping local sari-sari store owners manage their businesses more effectively. We saw the challenges they faced with manual record-keeping and knew technology could help.</p>
                        </div>
                        <div class="timeline-year">2023</div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h3>First Launch</h3>
                            <p>After months of development and testing with local store owners, we launched the first version of WAISTORE. The initial response was overwhelming, with 50 stores signing up in the first month.</p>
                        </div>
                        <div class="timeline-year">2024</div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h3>Growth & Expansion</h3>
                            <p>We expanded our features to include digital payments, debt tracking, and advanced analytics. Our user base grew to over 500 stores across Luzon, Visayas, and Mindanao.</p>
                        </div>
                        <div class="timeline-year">2025</div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <h3>Future Vision</h3>
                            <p>We're working on AI-powered insights, multi-store management, and integration with major Philippine financial institutions to serve even more small businesses.</p>
                        </div>
                        <div class="timeline-year">2026+</div>
                    </div>
                </div>
            </div>

            <!-- Mission & Vision Section -->
            <div class="mission-vision">
                <div class="mission-card">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To democratize business management tools for small retailers in the Philippines, providing affordable, accessible, and easy-to-use solutions that help them grow and compete in the digital economy.</p>
                </div>
                
                <div class="vision-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To become the leading business management platform for micro, small, and medium enterprises in the Philippines, empowering every local store to thrive in the modern marketplace.</p>
                </div>
            </div>

            <!-- Features Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Why Choose WAISTORE?</h2>
                    <p>Designed specifically for the unique needs of Philippine sari-sari stores</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h3>Smart Inventory</h3>
                        <p>Track stock levels, set low-stock alerts, and manage your products efficiently with our intuitive inventory system.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h3>Easy POS</h3>
                        <p>Process sales quickly with our point-of-sale system that works even on basic smartphones and tablets.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>Debt Management</h3>
                        <p>Keep track of customer credits, send payment reminders, and maintain healthy cash flow.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Business Insights</h3>
                        <p>Get valuable insights into your sales patterns, best-selling products, and customer behavior.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3>Digital Payments</h3>
                        <p>Accept GCash, Maya, and other digital payments with integrated QR code support.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile-First</h3>
                        <p>Access your store management tools anywhere, anytime from your smartphone or tablet.</p>
                    </div>
                </div>
            </div>

            <!-- Team Section -->
            <div class="team-section">
                <div class="section-header">
                    <h2>Meet Our Team</h2>
                    <p>The passionate individuals behind WAISTORE's success</p>
                </div>

                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="member-info">
                            <h4>Lorence Montero</h4>
                            <div class="member-role">Founder & CEO</div>
                            <p class="member-bio">With a background in business and technology, Lorince leads WAISTORE's vision to empower small Filipino businesses.</p>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="member-info">
                            <h4>Michell Castanares</h4>
                            <div class="member-role">Head of Product</div>
                            <p class="member-bio">Michell ensures WAISTORE's features meet the real-world needs of sari-sari store owners across the Philippines.</p>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="member-info">
                            <h4>Lorence Montero</h4>
                            <div class="member-role">Lead Developer</div>
                            <p class="member-bio">Jayrad architects WAISTORE's robust and scalable technology platform.</p>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-photo">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="member-info">
                            <h4>Timkang, Aira Mee</h4>
                            <div class="member-role">Customer Success</div>
                            <p class="member-bio">Sarah ensures every WAISTORE user gets the support they need to succeed with our platform.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <h2>Ready to Transform Your Business?</h2>
                <p>Join hundreds of sari-sari store owners who are already using WAISTORE to save time, reduce errors, and grow their businesses.</p>
                
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-white">
                        <i class="fas fa-rocket"></i> Start Free Trial
                    </a>
                    <a href="contact.php" class="btn btn-transparent">
                        <i class="fas fa-comments"></i> Contact Sales
                    </a>
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
                    <h3>Quick Links</h3>
                    <p><a href="about_us.php" style="color: #ccc;">About Us</a></p>
                    <p><a href="faqs.php" style="color: #ccc;">FAQs</a></p>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                    <p><a href="terms_of_use.php" style="color: #ccc;">Terms of Use</a></p>
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