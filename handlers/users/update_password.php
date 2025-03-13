<?php
require_once __DIR__ . '/../../helpers/functions.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /ERC-POS/views/profile/index.php');
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: /ERC-POS/views/profile/index.php');
    exit;
}

// Check if new password matches confirmation
if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'New password and confirmation do not match';
    header('Location: /ERC-POS/views/profile/index.php');
    exit;
}

// Get user's current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    $_SESSION['error'] = 'Current password is incorrect';
    header('Location: /ERC-POS/views/profile/index.php');
    exit;
}

// Update password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");

try {
    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
    $_SESSION['success'] = 'Password updated successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to update password. Please try again.';
}

header('Location: /ERC-POS/views/profile/index.php');
exit; 