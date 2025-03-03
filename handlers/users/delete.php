<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ERC-POS/index.php');
    exit;
}

// Check if ID was provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'User ID is required.';
    header('Location: /ERC-POS/views/users/index.php');
    exit;
}

$user_id = $_GET['id'];

// Prevent deleting self
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot delete your own account.';
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

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if user has any associated records
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM orders WHERE created_by = ?) as order_count,
            (SELECT COUNT(*) FROM inventory_transactions WHERE created_by = ?) as transaction_count
    ");
    $stmt->execute([$user_id, $user_id]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($counts['order_count'] > 0 || $counts['transaction_count'] > 0) {
        // If user has associated records, just deactivate them
        $stmt = $conn->prepare("
            UPDATE users 
            SET is_active = 0,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        $conn->commit();
        $_SESSION['success'] = 'User has associated records and has been deactivated instead of deleted.';
    } else {
        // If no associated records, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $conn->commit();
        $_SESSION['success'] = 'User deleted successfully.';
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/users/index.php'); 