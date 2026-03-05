<?php
session_start();
require_once 'vendor/autoload.php';

// Database connection
$servername = getenv("DB_HOST") ?: "localhost:3307";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$dbname = getenv("DB_NAME") ?: "waistore_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'secrets.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri('http://localhost/WAISTORE2/google_auth.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user profile information from Google
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $google_id = $google_account_info->id;
    $email = $google_account_info->email;
    $name = $google_account_info->name;

    // Check if user exists in database
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Update user with Google ID and mark as verified
        $update_query = "UPDATE users SET google_id = ?, is_verified = 1 WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $google_id, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Google account connected successfully!";
        } else {
            $_SESSION['error'] = "Failed to connect Google account.";
        }
    }

    $stmt->close();
    $conn->close();

    header('Location: account.php');
    exit();
} else {
    // Handle error
    $_SESSION['error'] = "Google authentication failed.";
    header('Location: account.php');
    exit();
}
?>