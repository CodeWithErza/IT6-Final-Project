<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /ERC-POS/login.php');
    exit;
}

// Get input
$username = trim($_POST['username']);
$password = $_POST['password'];

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Username and password are required.';
    header('Location: /ERC-POS/login.php');
    exit;
}

try {
    // Get user
    $stmt = $conn->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Check if user exists and password is correct
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Invalid username or password.';
        header('Location: /ERC-POS/login.php');
        exit;
    }

    // Check if user is active
    if (!$user['is_active']) {
        $_SESSION['error'] = 'Your account has been deactivated. Please contact an administrator.';
        header('Location: /ERC-POS/login.php');
        exit;
    }

    // Update last login time
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Redirect to sales order page
    header('Location: /ERC-POS/index.php');
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error during login: ' . $e->getMessage();
    header('Location: /ERC-POS/login.php');
} 