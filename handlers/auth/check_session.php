<?php
// Set JSON content type header
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Return session status
echo json_encode([
    'logged_in' => $logged_in,
    'user_id' => $logged_in ? $_SESSION['user_id'] : null,
    'username' => $logged_in ? $_SESSION['username'] : null,
    'role' => $logged_in ? $_SESSION['role'] : null
]); 