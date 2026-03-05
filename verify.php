<?php
// verify.php
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists
    $sql = "SELECT id, username FROM users WHERE verification_token = ? AND is_verified = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update user as verified
        $update_sql = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $user['id']);
        
        if ($update_stmt->execute()) {
            $success = "Your email has been verified successfully! You can now login.";
        } else {
            $error = "Error verifying your email. Please try again.";
        }
    } else {
        $error = "Invalid or expired verification token.";
    }
} else {
    $error = "No verification token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - WAISTORE</title>
    <style>
        /* Add similar styles as register.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2D5BFF, #1A46E0);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .verification-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .success {
            color: #34C759;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .error {
            color: #FF3B30;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .btn {
            background: #FF9E1A;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <a href="index.php" class="btn">Login Now</a>
        <?php elseif (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
            <a href="register.php" class="btn">Back to Registration</a>
        <?php endif; ?>
    </div>
    <script src="waistore-global.js"></script>
</body>
</html>