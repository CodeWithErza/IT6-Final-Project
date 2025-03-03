<?php

session_start();

require_once __DIR__ . '/../helpers/database.php';

// Check if user is logged in and active
if (!isset($_SESSION['user_id']) || !isset($_SESSION['access_token'])) {
    // Store the current URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: /ERC-POS/views/auth/login.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        // User is no longer active, force logout
        session_unset();
        session_destroy();
        header("Location: /ERC-POS/views/auth/login.php");
        exit;
    }
} catch (\Exception $e) {
    // Log error and redirect to login
    error_log("Authentication error: " . $e->getMessage());
    session_unset();
    session_destroy();
    header("Location: /ERC-POS/views/auth/login.php");
    exit;
}
