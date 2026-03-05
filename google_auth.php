<?php
session_start();
include 'db.php';
require_once 'secrets.php';

if (isset($_POST['id_token'])) {
    $id_token = $_POST['id_token'];
    $client_id = GOOGLE_CLIENT_ID;

    // Verify the token with Google using cURL
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        exit();
    }

    curl_close($ch);

    $payload = json_decode($response, true);

    if (isset($payload['error'])) {
        echo 'Google API error: ' . $payload['error'];
        exit();
    }

    if (isset($payload['sub']) && $payload['aud'] == $client_id) {
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];

        // Check if user exists in database
        $stmt = $conn->prepare("SELECT id, username, full_name, store_name, has_seen_tutorial FROM users WHERE google_id = ? OR email = ?");
        $stmt->bind_param("ss", $google_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists, log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Check if user has completed tutorial
            if (!$user['has_seen_tutorial']) {
                $_SESSION['show_tutorial'] = true;
            }

            header("Location: dashboard.php");
            exit();
        } else {
            // Create new user with default values
            $username = explode('@', $email)[0];
            // Make sure username is unique
            $original_username = $username;
            $counter = 1;
            while (true) {
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows === 0) {
                    break;
                }

                $username = $original_username . $counter;
                $counter++;
            }

            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $store_name = $name . "'s Store"; // Default store name
            $has_seen_tutorial = false; // New users should see the tutorial

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, google_id, store_name, has_seen_tutorial) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $username, $password, $email, $name, $google_id, $store_name, $has_seen_tutorial);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;

                // Show tutorial for new users
                $_SESSION['show_tutorial'] = true;

                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error creating user: " . $conn->error;
            }
        }
    } else {
        echo "Invalid ID token or client ID mismatch";
    }
} else {
    echo "No ID token provided";
}
?>