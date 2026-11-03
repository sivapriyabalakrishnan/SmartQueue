<?php
/**
 * Database Configuration - Smart Queue Manager
 */

// Database credentials
$conn = new mysqli("localhost", "root", "", "smartqueue");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Sanitize function
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

// Generate queue number
function generate_queue_number() {
    return 'Q' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

// Start session
session_start();

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>
