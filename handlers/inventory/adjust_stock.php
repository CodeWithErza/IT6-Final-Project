<?php
require_once '../../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to continue';
    header('Location: /ERC-POS/login.php');
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: /ERC-POS/views/inventory/index.php');
    exit;
}

$menu_item_id = $_POST['menu_item_id'] ?? '';
$new_stock = $_POST['new_stock'] ?? '';
$notes = $_POST['notes'] ?? '';

if (empty($menu_item_id) || !isset($new_stock) || !is_numeric($new_stock) || $new_stock < 0) {
    $_SESSION['error'] = 'Invalid input data';
    header('Location: /ERC-POS/views/inventory/index.php');
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get current stock
    $stmt = $conn->prepare("
        SELECT COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN transaction_type = 'in' THEN quantity
                    WHEN transaction_type = 'out' THEN -quantity
                END
            )
            FROM inventory_transactions 
            WHERE menu_item_id = :menu_item_id
            ), 0
        ) as current_stock
    ");
    $stmt->execute([':menu_item_id' => $menu_item_id]);
    $current_stock = $stmt->fetchColumn();

    // Calculate difference
    $difference = $new_stock - $current_stock;

    if ($difference != 0) {
        // Insert adjustment transaction
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions (
                menu_item_id,
                transaction_type,
                quantity,
                notes,
                created_by
            ) VALUES (
                :menu_item_id,
                :transaction_type,
                :quantity,
                :notes,
                :created_by
            )
        ");

        $stmt->execute([
            ':menu_item_id' => $menu_item_id,
            ':transaction_type' => $difference > 0 ? 'in' : 'out',
            ':quantity' => abs($difference),
            ':notes' => $notes . ' (Stock Adjustment)',
            ':created_by' => $_SESSION['user_id']
        ]);
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Stock adjusted successfully';
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = 'Error adjusting stock: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/inventory/index.php'); 