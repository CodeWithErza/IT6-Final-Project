<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if (isset($_SESSION['user_id'])) {
        // Log the logout action
        log_audit($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
    }
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Start a new session for error messages
    session_start();
    
    // Clear the access token cookie if it exists
    if (isset($_COOKIE['access_token'])) {
        setcookie('access_token', '', time() - 3600, '/');
    }
    
    // Redirect to the root URL which will then redirect to login
    header("Location: /ERC-POS/");
    exit;
} catch (\Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    $_SESSION['error'] = "System error: Please try again later.";
    header("Location: /ERC-POS/");
    exit;
} 