<?php
// appearance.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get appearance settings from DB
    $stmt = $conn->prepare("SELECT * FROM appearance_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appearance_settings = $result->fetch_assoc();
    $stmt->close();

    // Set default values if settings don't exist
    if (!$appearance_settings) {
        $appearance_settings = [
            'theme' => 'light',
            'language' => 'en',
            'date_format' => 'Y-m-d',
            'time_format' => '12'
        ];

        // Insert default settings
        $stmt = $conn->prepare("INSERT INTO appearance_settings (user_id, theme, language, date_format, time_format) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $appearance_settings['theme'], $appearance_settings['language'], $appearance_settings['date_format'], $appearance_settings['time_format']);
        $stmt->execute();
        $stmt->close();
    }

    // Sync to session
    $_SESSION['theme'] = $appearance_settings['theme'];
    $_SESSION['language'] = $appearance_settings['language'];
    $_SESSION['date_format'] = $appearance_settings['date_format'];
    $_SESSION['time_format'] = $appearance_settings['time_format'];

    // Get unread notifications count
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unread_res = $stmt->get_result();
    $unread_data = $unread_res->fetch_assoc();
    $unread_count = $unread_data['unread'] ?? 0;
    $stmt->close();
} else {
    // Defaults for guest
    if (!isset($_SESSION['theme']))
        $_SESSION['theme'] = 'light';
    $unread_count = 0;
}

// Global variable for CSS class
$theme_class = 'theme-' . (isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light');
?>