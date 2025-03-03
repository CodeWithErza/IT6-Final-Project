<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ERC-POS/index.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate input
$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$role = $_POST['role'];

// Validate username
if (empty($username)) {
    $_SESSION['error'] = 'Username is required.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Check username length
if (strlen($username) < 3 || strlen($username) > 50) {
    $_SESSION['error'] = 'Username must be between 3 and 50 characters.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Username already exists.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate password
if (empty($password)) {
    $_SESSION['error'] = 'Password is required.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Check password length
if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Check if passwords match
if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate role
if (!in_array($role, ['admin', 'staff'])) {
    $_SESSION['error'] = 'Invalid role selected.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

try {
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, is_active, created_at)
        VALUES (?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$username, $hashed_password, $role]);

    $_SESSION['success'] = 'User created successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error creating user: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/users/index.php'); 