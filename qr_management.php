<?php
// qr_management.php
header('Content-Type: application/json');

// Database connection
$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "waistore_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

$user_id = 1; // Assuming user is logged in with ID 1

// Handle different actions
$action = $_POST['action'] ?? '';

if ($action === 'upload' || $action === 'update') {
    // Handle QR code upload or update
    $qr_id = $_POST['qr_id'] ?? null;
    $payment_method = $_POST['payment_method'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if ($qr_id && $action === 'update') {
        // Update existing QR code
        if (isset($_FILES["qr_code_image"]) && $_FILES["qr_code_image"]["error"] == 0) {
            // Handle file upload for update
            $target_dir = "uploads/qr_codes/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["qr_code_image"]["name"], PATHINFO_EXTENSION);
            $filename = "qr_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $filename;
            
            if (!move_uploaded_file($_FILES["qr_code_image"]["tmp_name"], $target_file)) {
                echo json_encode(['success' => false, 'message' => 'Error uploading file']);
                exit;
            }
            
            // Delete old file
            $old_file_query = $conn->query("SELECT qr_code_image FROM user_qr_codes WHERE id = $qr_id AND user_id = $user_id");
            if ($old_file_query->num_rows > 0) {
                $old_file = $old_file_query->fetch_assoc()['qr_code_image'];
                if (file_exists($target_dir . $old_file)) {
                    unlink($target_dir . $old_file);
                }
            }
            
            $update_sql = "UPDATE user_qr_codes SET payment_method = ?, qr_code_image = ?, account_name = ?, account_number = ?, is_default = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssiii", $payment_method, $filename, $account_name, $account_number, $is_default, $qr_id, $user_id);
        } else {
            // Update without changing the file
            $update_sql = "UPDATE user_qr_codes SET payment_method = ?, account_name = ?, account_number = ?, is_default = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssiii", $payment_method, $account_name, $account_number, $is_default, $qr_id, $user_id);
        }
    } else {
        // Handle new QR code upload
        if (!isset($_FILES["qr_code_image"]) || $_FILES["qr_code_image"]["error"] != 0) {
            echo json_encode(['success' => false, 'message' => 'QR code image is required']);
            exit;
        }
        
        $target_dir = "uploads/qr_codes/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["qr_code_image"]["name"], PATHINFO_EXTENSION);
        $filename = "qr_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $filename;
        
        if (!move_uploaded_file($_FILES["qr_code_image"]["tmp_name"], $target_file)) {
            echo json_encode(['success' => false, 'message' => 'Error uploading file']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO user_qr_codes (user_id, payment_method, qr_code_image, account_name, account_number, is_default) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $user_id, $payment_method, $filename, $account_name, $account_number, $is_default);
    }
    
    // If setting as default, unset other defaults for the same payment method
    if ($is_default) {
        $conn->query("UPDATE user_qr_codes SET is_default = 0 WHERE user_id = $user_id AND payment_method = '$payment_method'");
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'QR code saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error saving QR code to database: ' . $conn->error]);
    }
    
} elseif ($action === 'get') {
    // Get user's QR codes for payment modal
    $result = $conn->query("SELECT * FROM user_qr_codes WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");
    $qr_codes = [];
    
    while ($row = $result->fetch_assoc()) {
        $qr_codes[] = $row;
    }
    
    echo json_encode(['success' => true, 'qr_codes' => $qr_codes]);
    
} elseif ($action === 'get_all') {
    // Get all user's QR codes for management section
    $result = $conn->query("SELECT * FROM user_qr_codes WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");
    $qr_codes = [];
    
    while ($row = $result->fetch_assoc()) {
        $qr_codes[] = $row;
    }
    
    echo json_encode(['success' => true, 'qr_codes' => $qr_codes]);
    
} elseif ($action === 'get_single') {
    // Get single QR code for editing
    $qr_id = $_POST['qr_id'];
    $result = $conn->query("SELECT * FROM user_qr_codes WHERE id = $qr_id AND user_id = $user_id");
    
    if ($result->num_rows > 0) {
        $qr_code = $result->fetch_assoc();
        echo json_encode(['success' => true, 'qr_code' => $qr_code]);
    } else {
        echo json_encode(['success' => false, 'message' => 'QR code not found']);
    }
    
} elseif ($action === 'delete') {
    // Delete QR code
    $qr_id = $_POST['qr_id'];
    
    // Get file name first
    $file_query = $conn->query("SELECT qr_code_image FROM user_qr_codes WHERE id = $qr_id AND user_id = $user_id");
    
    if ($file_query->num_rows > 0) {
        $file_name = $file_query->fetch_assoc()['qr_code_image'];
        $target_dir = "uploads/qr_codes/";
        
        // Delete from database
        $delete_query = $conn->query("DELETE FROM user_qr_codes WHERE id = $qr_id AND user_id = $user_id");
        
        if ($delete_query) {
            // Delete file
            if (file_exists($target_dir . $file_name)) {
                unlink($target_dir . $file_name);
            }
            echo json_encode(['success' => true, 'message' => 'QR code deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting QR code from database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'QR code not found']);
    }
    
} elseif ($action === 'set_default') {
    // Set QR code as default
    $qr_id = $_POST['qr_id'];
    
    // Get the payment method of the QR code to be set as default
    $qr_query = $conn->query("SELECT payment_method FROM user_qr_codes WHERE id = $qr_id AND user_id = $user_id");
    
    if ($qr_query->num_rows > 0) {
        $payment_method = $qr_query->fetch_assoc()['payment_method'];
        
        // Unset current default for this payment method
        $conn->query("UPDATE user_qr_codes SET is_default = 0 WHERE user_id = $user_id AND payment_method = '$payment_method'");
        
        // Set new default
        $update_query = $conn->query("UPDATE user_qr_codes SET is_default = 1 WHERE id = $qr_id AND user_id = $user_id");
        
        if ($update_query) {
            echo json_encode(['success' => true, 'message' => 'Default QR code updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error setting default QR code']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'QR code not found']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>