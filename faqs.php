<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - WAISTORE</title>
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

        /* FAQ Styles */
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-categories {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 10px 20px;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .category-btn.active, .category-btn:hover {
            background: var(--primary);
            color: white;
        }

        .faq-item {
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .faq-question:hover {
            background: rgba(45, 91, 255, 0.05);
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .faq-answer.active {
            padding: 20px;
            max-height: 500px;
        }

        .faq-answer p {
            margin-bottom: 15px;
        }

        .faq-answer ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .faq-answer li {
            margin-bottom: 8px;
        }

        .icon {
            transition: transform 0.3s;
        }

        .faq-item.active .icon {
            transform: rotate(180deg);
        }

        .search-box {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .contact-support {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-top: 40px;
        }

        .contact-support h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .support-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .support-option {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            transition: transform 0.3s;
        }

        .support-option:hover {
            transform: translateY(-5px);
        }

        .support-option i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--secondary);
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
            
            .faq-categories {
                gap: 10px;
            }
            
            .category-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .support-options {
                grid-template-columns: 1fr;
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

        .no-results {
            text-align: center;
            padding: 40px;
            color: var(--gray);
            display: none;
        }

        .highlight {
            background-color: rgba(255, 158, 26, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--secondary);
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
                        <li><a href="about_us.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                        <li><a href="faqs.php" class="active"><i class="fas fa-question-circle"></i> FAQs</a></li>
                        <li><a href="privacy_policy.php"><i class="fas fa-shield-alt"></i> Privacy</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </header>

    <!-- FAQs Page -->
    <section class="page">
        <div class="container">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <div class="page-header">
                <h1>Frequently Asked Questions</h1>
                <p>Find answers to common questions about WAISTORE</p>
            </div>

            <div class="faq-container">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search for questions or keywords..." id="faqSearch">
                </div>

                <!-- FAQ Categories -->
                <div class="faq-categories">
                    <button class="category-btn active" data-category="all">All Questions</button>
                    <button class="category-btn" data-category="account">Account</button>
                    <button class="category-btn" data-category="features">Features</button>
                    <button class="category-btn" data-category="billing">Billing</button>
                    <button class="category-btn" data-category="technical">Technical</button>
                </div>

                <!-- FAQ Items -->
                <div class="faq-items">
                    <!-- Account FAQs -->
                    <div class="faq-item" data-category="account">
                        <div class="faq-question">
                            <span>How do I create a WAISTORE account?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Creating a WAISTORE account is simple and free:</p>
                            <ol>
                                <li>Click the "Register" button on our homepage</li>
                                <li>Fill in your personal and store information</li>
                                <li>Verify your email address</li>
                                <li>Set up your store profile</li>
                                <li>Start managing your business!</li>
                            </ol>
                            <div class="highlight">
                                <p><strong>Pro Tip:</strong> Use your business email address for better organization and future business communications.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-category="account">
                        <div class="faq-question">
                            <span>I forgot my password. How can I reset it?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To reset your password:</p>
                            <ol>
                                <li>Go to the login page</li>
                                <li>Click "Forgot Password"</li>
                                <li>Enter your registered email address</li>
                                <li>Check your email for reset instructions</li>
                                <li>Follow the link to create a new password</li>
                            </ol>
                            <p>If you don't receive the email within 5 minutes, check your spam folder or contact our support team.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="account">
                        <div class="faq-question">
                            <span>Can I use WAISTORE on multiple devices?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! WAISTORE is designed to work seamlessly across multiple devices:</p>
                            <ul>
                                <li><strong>Web Browser:</strong> Access from any computer with internet</li>
                                <li><strong>Mobile Devices:</strong> Use on smartphones and tablets</li>
                                <li><strong>Sync:</strong> Your data syncs automatically across all devices</li>
                                <li><strong>Multiple Sessions:</strong> You can be logged in on multiple devices simultaneously</li>
                            </ul>
                            <p>Your data is securely stored in the cloud and accessible wherever you have an internet connection.</p>
                        </div>
                    </div>

                    <!-- Features FAQs -->
                    <div class="faq-item" data-category="features">
                        <div class="faq-question">
                            <span>What inventory management features does WAISTORE offer?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>WAISTORE provides comprehensive inventory management including:</p>
                            <ul>
                                <li><strong>Product Catalog:</strong> Add, edit, and organize your products</li>
                                <li><strong>Stock Tracking:</strong> Real-time inventory levels</li>
                                <li><strong>Low Stock Alerts:</strong> Automatic notifications when items run low</li>
                                <li><strong>Categories:</strong> Organize products by type or category</li>
                                <li><strong>Barcode Support:</strong> Optional barcode integration</li>
                                <li><strong>Batch Management:</strong> Track different product batches</li>
                                <li><strong>Supplier Information:</strong> Manage supplier details</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item" data-category="features">
                        <div class="faq-question">
                            <span>How does the debt tracking system work?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Our debt tracking system helps you manage customer credits effectively:</p>
                            <ul>
                                <li><strong>Record Debts:</strong> Track who owes you money and how much</li>
                                <li><strong>Payment History:</strong> Monitor payment patterns and history</li>
                                <li><strong>Due Date Alerts:</strong> Get reminders for overdue payments</li>
                                <li><strong>Partial Payments:</strong> Record partial payments and track remaining balances</li>
                                <li><strong>Customer Profiles:</strong> Maintain customer contact information</li>
                                <li><strong>Reports:</strong> Generate debt summary reports</li>
                            </ul>
                            <div class="highlight">
                                <p><strong>Best Practice:</strong> Set clear payment terms with customers and use the reminder system to maintain healthy cash flow.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-category="features">
                        <div class="faq-question">
                            <span>Can I generate sales reports with WAISTORE?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! WAISTORE offers comprehensive reporting features:</p>
                            <ul>
                                <li><strong>Daily Sales Reports:</strong> Track daily revenue and transactions</li>
                                <li><strong>Weekly/Monthly Summaries:</strong> View performance over time</li>
                                <li><strong>Product Performance:</strong> Identify best-selling items</li>
                                <li><strong>Customer Analytics:</strong> Understand customer buying patterns</li>
                                <li><strong>Profit Calculations:</strong> Track costs and profits</li>
                                <li><strong>Export Options:</strong> Download reports in various formats</li>
                            </ul>
                            <p>Reports are automatically generated and can be accessed anytime from your dashboard.</p>
                        </div>
                    </div>

                    <!-- Billing FAQs -->
                    <div class="faq-item" data-category="billing">
                        <div class="faq-question">
                            <span>Is WAISTORE really free to use?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>WAISTORE offers both free and premium plans:</p>
                            <ul>
                                <li><strong>Free Plan:</strong> Includes basic inventory management, sales tracking, and debt management - perfect for small sari-sari stores</li>
                                <li><strong>Premium Plan:</strong> Advanced features like detailed analytics, multiple store locations, and priority support</li>
                                <li><strong>Enterprise Plan:</strong> Custom solutions for larger businesses</li>
                            </ul>
                            <p>You can start with our free plan and upgrade anytime as your business grows.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="billing">
                        <div class="faq-question">
                            <span>What payment methods do you accept for premium plans?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We accept various payment methods for your convenience:</p>
                            <ul>
                                <li><strong>GCash:</strong> Popular mobile wallet in the Philippines</li>
                                <li><strong>Maya:</strong> Another trusted mobile payment option</li>
                                <li><strong>Credit/Debit Cards:</strong> Visa, Mastercard, and other major cards</li>
                                <li><strong>Bank Transfer:</strong> Direct bank deposits</li>
                                <li><strong>Online Banking:</strong> Through supported Philippine banks</li>
                            </ul>
                            <p>All payments are processed securely through our payment partners.</p>
                        </div>
                    </div>

                    <!-- Technical FAQs -->
                    <div class="faq-item" data-category="technical">
                        <div class="faq-question">
                            <span>Is my data safe with WAISTORE?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, we take data security very seriously:</p>
                            <ul>
                                <li><strong>Encryption:</strong> All data is encrypted in transit and at rest</li>
                                <li><strong>Secure Servers:</strong> Hosted on reliable, secure cloud infrastructure</li>
                                <li><strong>Regular Backups:</strong> Automatic daily backups of your data</li>
                                <li><strong>Access Controls:</strong> Multi-level security and authentication</li>
                                <li><strong>Privacy Protection:</strong> We never sell your data to third parties</li>
                                <li><strong>Compliance:</strong> Adherence to data protection regulations</li>
                            </ul>
                            <p>You can read more about our security measures in our <a href="privacy_policy.php">Privacy Policy</a>.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="technical">
                        <div class="faq-question">
                            <span>What happens to my data if I cancel my account?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We understand your business data is important:</p>
                            <ul>
                                <li><strong>Data Export:</strong> You can export your data before cancellation</li>
                                <li><strong>Grace Period:</strong> 30-day grace period after cancellation</li>
                                <li><strong>Secure Deletion:</strong> Data is permanently deleted after the grace period</li>
                                <li><strong>Backup Retention:</strong> Backups are retained for 90 days for security purposes</li>
                            </ul>
                            <p>We recommend exporting your important business data before proceeding with account cancellation.</p>
                        </div>
                    </div>

                    <div class="faq-item" data-category="technical">
                        <div class="faq-question">
                            <span>Do you offer mobile apps for WAISTORE?</span>
                            <i class="fas fa-chevron-down icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>WAISTORE is a web-based application that works perfectly on mobile devices:</p>
                            <ul>
                                <li><strong>Mobile-Optimized:</strong> Fully responsive design for all screen sizes</li>
                                <li><strong>PWA Support:</strong> Can be installed as an app on your phone</li>
                                <li><strong>Offline Capability:</strong> Basic functions work without internet</li>
                                <li><strong>Cross-Platform:</strong> Works on iOS, Android, and any modern browser</li>
                            </ul>
                            <p>No separate app download is required - just visit our website from your mobile browser!</p>
                        </div>
                    </div>
                </div>

                <!-- No Results Message -->
                <div class="no-results" id="noResults">
                    <i class="fas fa-search fa-3x" style="margin-bottom: 15px;"></i>
                    <h3>No questions found</h3>
                    <p>Try searching with different keywords or browse the categories above.</p>
                </div>

                <!-- Contact Support Section -->
                <div class="contact-support">
                    <h3>Still have questions?</h3>
                    <p>Our support team is here to help you get the most out of WAISTORE</p>
                    
                    <div class="support-options">
                        <div class="support-option">
                            <i class="fas fa-envelope"></i>
                            <h4>Email Support</h4>
                            <p>waistore1@gmail.com</p>
                            <small>Response within 24 hours</small>
                        </div>
                        <div class="support-option">
                            <i class="fas fa-phone"></i>
                            <h4>Phone Support</h4>
                            <p>+63 912 345 6789</p>
                            <small>Mon-Fri, 9AM-6PM</small>
                        </div>
                        <div class="support-option">
                            <i class="fas fa-comments"></i>
                            <h4>Live Chat</h4>
                            <p>Available in-app</p>
                            <small>Real-time assistance</small>
                        </div>
                        <div class="support-option">
                            <i class="fas fa-file-alt"></i>
                            <h4>Documentation</h4>
                            <p>User Guides & Tutorials</p>
                            <small>Step-by-step help</small>
                        </div>
                    </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Accordion Functionality
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const faqItem = this.parentElement;
                    const answer = this.nextElementSibling;
                    
                    // Close all other FAQ items
                    document.querySelectorAll('.faq-item').forEach(item => {
                        if (item !== faqItem) {
                            item.classList.remove('active');
                            item.querySelector('.faq-answer').classList.remove('active');
                        }
                    });
                    
                    // Toggle current FAQ item
                    faqItem.classList.toggle('active');
                    answer.classList.toggle('active');
                });
            });

            // Category Filtering
            const categoryBtns = document.querySelectorAll('.category-btn');
            const faqItems = document.querySelectorAll('.faq-item');
            
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    
                    // Update active category button
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter FAQ items
                    faqItems.forEach(item => {
                        if (category === 'all' || item.getAttribute('data-category') === category) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Update search results
                    performSearch();
                });
            });

            // Search Functionality
            const searchInput = document.getElementById('faqSearch');
            const noResults = document.getElementById('noResults');
            
            searchInput.addEventListener('input', performSearch);
            
            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeCategory = document.querySelector('.category-btn.active').getAttribute('data-category');
                let visibleItems = 0;
                
                faqItems.forEach(item => {
                    const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                    const category = item.getAttribute('data-category');
                    
                    const matchesSearch = question.includes(searchTerm) || answer.includes(searchTerm);
                    const matchesCategory = activeCategory === 'all' || category === activeCategory;
                    const isVisible = matchesSearch && matchesCategory && item.style.display !== 'none';
                    
                    if (isVisible) {
                        item.style.display = 'block';
                        visibleItems++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Show/hide no results message
                noResults.style.display = visibleItems === 0 ? 'block' : 'none';
            }

            // Auto-expand FAQ if it contains search term
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                if (searchTerm.length > 2) {
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                        
                        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                            item.classList.add('active');
                            item.querySelector('.faq-answer').classList.add('active');
                        }
                    });
                }
            });
        });
    </script>
    <script src="waistore-global.js"></script>
</body>
</html>