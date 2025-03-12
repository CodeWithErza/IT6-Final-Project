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
$user_id = $_POST['user_id'];
$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$full_name = trim($_POST['full_name']);
$role = $_POST['role'];
$is_active = $_POST['is_active'];

// Validate user_id
if (!$user_id) {
    $_SESSION['error'] = 'User ID is required.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = 'User not found.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

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

// Check if username already exists (excluding current user)
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $user_id]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Username already exists.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate full name
if (empty($full_name)) {
    $_SESSION['error'] = 'Full name is required.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

if (strlen($full_name) > 100) {
    $_SESSION['error'] = 'Full name must not exceed 100 characters.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate role
if (!in_array($role, ['admin', 'staff'])) {
    $_SESSION['error'] = 'Invalid role selected.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

// Validate status
if (!in_array($is_active, ['0', '1'])) {
    $_SESSION['error'] = 'Invalid status selected.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Update user details
    if ($password) {
        // Validate new password
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

        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update with new password
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, 
                password = ?,
                full_name = ?,
                role = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $hashed_password, $full_name, $role, $is_active, $user_id]);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, 
                full_name = ?,
                role = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $full_name, $role, $is_active, $user_id]);
    }

    // If user is deactivated, log them out
    if ($is_active == '0' && $user_id == $_SESSION['user_id']) {
        session_destroy();
        $conn->commit();
        header('Location: /ERC-POS/login.php');
        exit;
    }

    $conn->commit();
    $_SESSION['success'] = 'User updated successfully.';
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Error updating user: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/users/index.php'); 