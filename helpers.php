<?php
// helpers.php

/**
 * Format date according to user's preference
 */
function formatDate($date, $format = null) {
    if (!$format) {
        $format = isset($_SESSION['date_format']) ? $_SESSION['date_format'] : 'Y-m-d';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

/**
 * Format time according to user's preference
 */
function formatTime($time, $format = null) {
    if (!$format) {
        $time_format = isset($_SESSION['time_format']) ? $_SESSION['time_format'] : '12';
        $format = ($time_format === '24') ? 'H:i' : 'h:i A';
    }
    
    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time;
    }
    
    return date($format, $timestamp);
}

/**
 * Format datetime according to user's preference
 */
function formatDateTime($datetime, $date_format = null, $time_format = null) {
    $formatted_date = formatDate($datetime, $date_format);
    $formatted_time = formatTime($datetime, $time_format);
    
    return $formatted_date . ' ' . $formatted_time;
}

/**
 * Get current theme class
 */
function getThemeClass() {
    return 'theme-' . (isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light');
}

/**
 * Get translated text (placeholder for future translation system)
 */
function __($text) {
    // This is a placeholder for future translation implementation
    // For now, it just returns the original text
    return $text;
}
?>