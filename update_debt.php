<?php
// update_debt.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
if ($_POST['action'] === 'send_reminder') {
    // Simulate sending reminder
    $debt_id = $_POST['debt_id'];
    $reminder_method = $_POST['reminder_method'];
    $message = $_POST['message'];

    // In a real application, you would integrate with SMS/email APIs here
    error_log("Reminder sent for debt ID: $debt_id via $reminder_method - Message: $message");

    echo json_encode(['success' => true, 'message' => 'Reminder sent successfully']);
}
?>