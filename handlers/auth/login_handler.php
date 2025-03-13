<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        header("Location: /ERC-POS/views/auth/login.php");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required!";
        header("Location: /ERC-POS/views/auth/login.php");
        exit;
    }

    $user = verify_user($username, $password);

    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));

        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['access_token'] = $token;

        // Update last login timestamp
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Log the successful login
        log_audit($user['id'], 'login', 'users', $user['id']);

        // Redirect to dashboard or saved redirect URL
        $redirect_url = $_SESSION['redirect_url'] ?? '/ERC-POS/index.php';
        unset($_SESSION['redirect_url']); // Clear the saved URL
        
        header("Location: " . $redirect_url);
        exit;
    } else {
        $_SESSION['error'] = "Invalid username or password!";
        header("Location: /ERC-POS/views/auth/login.php");
        exit;
    }
} catch (\Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = "System error: Please try again later.";
    header("Location: /ERC-POS/views/auth/login.php");
    exit;
}

// Function to verify user credentials
function verify_user($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
} 