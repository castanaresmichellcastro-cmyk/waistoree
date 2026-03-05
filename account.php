<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php'; // Adjust path if needed

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $full_name = $user['full_name'];
    $store_name = $user['store_name'];
    $username = $user['username'];
    $email = $user['email'];
    $is_verified = $user['is_verified'];
    $google_id = $user['google_id'];
    $profile_image = isset($user['profile_image']) ? $user['profile_image'] : null;
} else {
    $full_name = "User";
    $store_name = "Store";
    $username = "username";
    $email = "email@example.com";
    $is_verified = 0;
    $google_id = null;
    $profile_image = null;
}
$stmt->close();

// Ensure profile_image column exists
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($col_check->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $error_message = "Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.";
    } elseif ($file['size'] > $max_size) {
        $error_message = "File too large. Maximum size is 5MB.";
    } else {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Delete old profile image if it exists
        if (!empty($profile_image) && file_exists($profile_image)) {
            unlink($profile_image);
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $update_img = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $update_img->bind_param("si", $filepath, $user_id);
            $update_img->execute();
            $update_img->close();
            $profile_image = $filepath;
            $success_message = "Profile photo updated successfully!";
        } else {
            $error_message = "Failed to upload file. Please try again.";
        }
    }
}

// Get user stats
$products_query = "SELECT COUNT(*) as product_count FROM products WHERE user_id = ?";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products_result = $stmt->get_result();
$product_count = $products_result->fetch_assoc()['product_count'] ?? 0;
$stmt->close();

// Calculate monthly sales
$month_start = date('Y-m-01');
$sales_query = "SELECT SUM(total_amount) as monthly_sales FROM transactions 
               WHERE user_id = ? AND status = 'paid' AND created_at >= ?";
$stmt = $conn->prepare($sales_query);
$stmt->bind_param("is", $user_id, $month_start);
$stmt->execute();
$sales_result = $stmt->get_result();
$monthly_sales = $sales_result->fetch_assoc()['monthly_sales'] ?? 0;
$stmt->close();

// Function to generate verification token
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

// Function to send verification email using PHPMailer (same as register.php)
function sendVerificationEmail($email, $token, $username, $full_name)
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
        $mail->addAddress($email, $full_name);
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
                
                <h2>Verify Your Email, " . htmlspecialchars($full_name) . "!</h2>
                <p>You requested to verify your email address for your WAISTORE account. To complete the verification, please click the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $verification_link . "' class='button'>Verify Email Address</a>
                </div>
                
                <p>Or copy and paste this link in your browser:<br>
                <a href='" . $verification_link . "'>" . $verification_link . "</a></p>
                
                <p>If you didn't request this verification, please ignore this email.</p>
                
                <div class='footer'>
                    <p>&copy; 2025 WAISTORE. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Verify Your WAISTORE Account\n\nHello " . $full_name . ",\n\nYou requested to verify your email address for your WAISTORE account. To complete the verification, please visit this link:\n\n" . $verification_link . "\n\nIf you didn't request this verification, please ignore this email.\n\nBest regards,\nWAISTORE Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Alternative simple mail function (if PHPMailer fails)
function sendSimpleVerificationEmail($email, $token, $username, $full_name)
{
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;

    $subject = "Verify Your WAISTORE Account";
    $message = "
    Verify Your WAISTORE Account

    Hello " . $full_name . ",

    You requested to verify your email address for your WAISTORE account. To complete the verification, please visit this link:

    " . $verification_link . "

    If you didn't request this verification, please ignore this email.

    Best regards,
    WAISTORE Team
    ";

    $headers = "From: WAISTORE <noreply@waistore.com>\r\n";
    $headers .= "Reply-To: support@waistore.com\r\n";

    return mail($email, $subject, $message, $headers);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fullName'])) {
    $full_name = $_POST['fullName'];
    $store_name = $_POST['storeName'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    // Update basic account info
    $update_query = "UPDATE users SET full_name = ?, store_name = ?, username = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $full_name, $store_name, $username, $email, $user_id);

    if ($stmt->execute()) {
        $success_message = "Account information updated successfully!";

        // If email changed, reset verification status
        if ($email != $user['email']) {
            $update_verification = "UPDATE users SET is_verified = 0 WHERE id = ?";
            $stmt2 = $conn->prepare($update_verification);
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $stmt2->close();
            $is_verified = 0;
        }
    } else {
        $error_message = "Failed to update account information.";
    }
    $stmt->close();

    // Handle password change if provided
    if (
        isset($_POST['currentPassword']) && !empty($_POST['currentPassword']) &&
        isset($_POST['newPassword']) && !empty($_POST['newPassword'])
    ) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];

        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            // Verify current password
            $password_query = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($password_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            if (password_verify($current_password, $user_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_password_query);
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Failed to update password.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
        $stmt->close();
    }
}

// Handle email verification request
if (isset($_POST['verify_email'])) {
    // Generate verification token
    $verification_token = generateToken();

    // Save token to database
    $token_query = "UPDATE users SET verification_token = ? WHERE id = ?";
    $stmt = $conn->prepare($token_query);
    $stmt->bind_param("si", $verification_token, $user_id);

    if ($stmt->execute()) {
        // Try to send verification email with PHPMailer first
        $email_sent = sendVerificationEmail($email, $verification_token, $username, $full_name);

        // If PHPMailer fails, try simple mail
        if (!$email_sent) {
            $email_sent = sendSimpleVerificationEmail($email, $verification_token, $username, $full_name);
        }

        if ($email_sent) {
            $success_message = "Verification email sent successfully! Please check your inbox (and spam folder).";
        } else {
            // If email fails, still provide the verification link for manual use
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $verification_token;
            $error_message = "Failed to send verification email. For testing, you can use this verification link: " .
                "<a href='$verification_link' style='color: #2D5BFF;'>$verification_link</a>";
        }
    } else {
        $error_message = "Error generating verification token.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAISTORE - My Account</title>
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

        nav a:hover,
        nav a.active {
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        /* Page Styles */
        .page {
            padding: 30px 0;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
        }

        /* Account Styles */
        .account-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .account-container {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 4px solid var(--primary);
        }

        .profile-name {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .profile-role {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat-item {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .account-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .account-card h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .input-with-icon input {
            padding-left: 45px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-secondary {
            background-color: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7a7a7a;
        }

        /* Email Verification Styles */
        .verification-status {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .verified {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .unverified {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .verification-icon {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .verification-text {
            flex: 1;
        }

        /* Messages */
        .error-message {
            background-color: #fdecea;
            color: #b71c1c;
            padding: 12px;
            border: 1px solid #f44336;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .error-message i {
            margin-right: 6px;
            color: #d32f2f;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .success-message i {
            margin-right: 6px;
            color: #2e7d32;
        }

        /* Google Verification Section */
        .google-verification {
            margin-top: 30px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }

        .google-verification h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
        }

        .google-verification p {
            margin-bottom: 15px;
            color: var(--gray);
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

            .form-actions {
                flex-direction: column;
            }

            .footer-content {
                flex-direction: column;
            }

            .profile-stats {
                grid-template-columns: 1fr;
            }
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
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="inventory.php"><i class="fas fa-box"></i> Inventory</a></li>
                        <li><a href="pos.php"><i class="fas fa-cash-register"></i> POS</a></li>
                        <li><a href="debts.php"><i class="fas fa-file-invoice-dollar"></i> Utang</a></li>
                        <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                    </ul>
                </nav>
                <div class="user-actions">
                    <a href="notifications.php" class="btn btn-outline"><i class="fas fa-bell"></i></a>
                    <a href="settings.php" class="btn btn-outline"><i class="fas fa-cog"></i></a>
                    <button class="btn btn-primary"><i class="fas fa-user"></i> My Account</button>
                    <a href="logout.php" class="btn btn-outline" style="margin-left:10px;"><i
                            class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Account Page -->
    <section class="page">
        <div class="container">
            <div class="page-header">
                <h1>My Account</h1>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to
                    Dashboard</a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="account-container">
                <div class="profile-card">
                    <!-- Profile Photo with Upload -->
                    <div class="profile-photo-wrapper" style="position:relative;display:inline-block;cursor:pointer;"
                        onclick="document.getElementById('profilePhotoInput').click();">
                        <img src="<?php echo !empty($profile_image) && file_exists($profile_image) ? htmlspecialchars($profile_image) . '?v=' . time() : 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=6366F1&color=fff&size=200'; ?>"
                            alt="Profile" class="profile-image" id="profilePreview">
                        <div class="photo-upload-overlay"
                            style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.7));color:white;text-align:center;padding:10px 8px 8px;border-radius:0 0 50% 50%;font-size:0.75rem;font-weight:600;opacity:0;transition:opacity 0.2s ease;">
                            <i class="fas fa-camera" style="font-size:1rem;"></i><br>Change Photo
                        </div>
                    </div>
                    <form id="profilePhotoForm" method="POST" enctype="multipart/form-data" style="display:none;">
                        <input type="file" name="profile_photo" id="profilePhotoInput"
                            accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAndUploadPhoto(this);">
                    </form>
                    <h2 class="profile-name">
                        <?php echo htmlspecialchars($full_name); ?>
                    </h2>
                    <p class="profile-role">Grocery Store Owner</p>
                    <p>
                        <?php echo htmlspecialchars($store_name); ?>
                    </p>

                    <!-- Email Verification Status -->
                    <div class="verification-status <?php echo $is_verified ? 'verified' : 'unverified'; ?>">
                        <?php if ($is_verified): ?>
                            <i class="fas fa-check-circle verification-icon"></i>
                            <div class="verification-text">
                                <strong>Email Verified</strong>
                                <p>Your email address is confirmed</p>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle verification-icon"></i>
                            <div class="verification-text">
                                <strong>Email Not Verified</strong>
                                <p>Please verify your email address</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo $product_count; ?>
                            </div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">₱
                                <?php echo number_format($monthly_sales, 2); ?>
                            </div>
                            <div class="stat-label">Monthly Sales</div>
                        </div>
                    </div>
                </div>

                <div class="account-card">
                    <h2>Account Information</h2>

                    <!-- Email Verification Notice -->
                    <?php if (!$is_verified): ?>
                        <div class="verification-status unverified">
                            <i class="fas fa-exclamation-circle verification-icon"></i>
                            <div class="verification-text">
                                <strong>Email Verification Required</strong>
                                <p>Please verify your email address to access all features</p>
                            </div>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="verify_email" class="btn btn-primary btn-sm">Verify
                                    Email</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <form id="accountForm" method="POST" action="">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="fullName" name="fullName" class="form-control"
                                    value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="storeName">Store Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-store"></i>
                                <input type="text" id="storeName" name="storeName" class="form-control"
                                    value="<?php echo htmlspecialchars($store_name); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user-circle"></i>
                                <input type="text" id="username" name="username" class="form-control"
                                    value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>

                        <h2 style="margin-top: 30px;">Change Password</h2>

                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="currentPassword" name="currentPassword" class="form-control"
                                    placeholder="Enter current password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="newPassword" name="newPassword" class="form-control"
                                    placeholder="Enter new password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirmPassword" name="confirmPassword" class="form-control"
                                    placeholder="Confirm new password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary"
                                onclick="window.location.href='dashboard.php'">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>

                    <!-- Email Verification Section -->
                    <div class="google-verification">
                        <h3><i class="fas fa-envelope"></i> Email Verification</h3>
                        <p>Verify your email address to ensure you receive important notifications and can reset your
                            password if needed.</p>

                        <?php if ($is_verified): ?>
                            <div class="verification-status verified">
                                <i class="fas fa-check-circle verification-icon"></i>
                                <div class="verification-text">
                                    <strong>Email Verified</strong>
                                    <p>Your email address has been successfully verified</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="verification-status unverified">
                                <i class="fas fa-exclamation-circle verification-icon"></i>
                                <div class="verification-text">
                                    <strong>Email Not Verified</strong>
                                    <p>Click the button below to send a verification email</p>
                                </div>
                            </div>
                            <form method="POST">
                                <button type="submit" name="verify_email" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Verification Email
                                </button>
                            </form>
                        <?php endif; ?>
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
                    <p>Smart Grocery Store Management System</p>
                    <p>Empowering Filipino grocery store owners with digital tools for sales, inventory, and utang management.</p>
                </div>
                <div class="footer-section">
                    <h3>Contact & Support</h3>
                    <p><i class="fas fa-envelope"></i> waistore1@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                    <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="about_us.php" style="color: #ccc;">About Us</a></p>
                    <p><a href="dashboard.php" style="color: #ccc;">Features</a></p>
                    <p><a href="faqs.php" style="color: #ccc;">FAQs</a></p>
                    <p><a href="privacy_policy.php" style="color: #ccc;">Privacy Policy</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WAISTORE. All rights reserved</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-hide messages after 5 seconds
            setTimeout(function () {
                const errorMessage = document.querySelector('.error-message');
                const successMessage = document.querySelector('.success-message');

                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }

                if (successMessage) {
                    successMessage.style.display = 'none';
                }
            }, 5000);
        });
    </script>
    <script src="waistore-global.js"></script>
    <script>
        function previewAndUploadPhoto(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate client-side
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 5MB.');
                    return;
                }

                // Preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Auto-submit
                document.getElementById('profilePhotoForm').submit();
            }
        }
    </script>
    <style>
        .profile-photo-wrapper:hover .photo-upload-overlay {
            opacity: 1 !important;
        }

        .profile-photo-wrapper .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
            transition: transform 0.2s ease;
        }

        .profile-photo-wrapper:hover .profile-image {
            transform: scale(1.03);
        }
    </style>
</body>

</html>