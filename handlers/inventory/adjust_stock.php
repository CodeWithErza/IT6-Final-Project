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
    header('Location: /ERC-POS/views/inventory/stock_adjustment.php');
    exit;
}

$menu_item_id = $_POST['menu_item_id'] ?? '';
$new_stock = $_POST['new_stock'] ?? '';
$notes = $_POST['notes'] ?? '';
$adjustment_type = $_POST['adjustment_type'] ?? 'other';

if (empty($menu_item_id) || !isset($new_stock) || !is_numeric($new_stock) || $new_stock < 0) {
    $_SESSION['error'] = 'Invalid input data';
    header('Location: /ERC-POS/views/inventory/stock_adjustment.php');
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
                    WHEN transaction_type = 'stock_in' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Increase%' THEN quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Decrease%' THEN -quantity
                    WHEN transaction_type = 'adjustment' THEN quantity
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
    
    // Format notes with adjustment type and direction
    $direction = ($difference > 0) ? 'Increase' : 'Decrease';
    $formatted_notes = $notes;
    if (!empty($adjustment_type) && $adjustment_type != 'other') {
        $type_label = ucfirst(str_replace('_', ' ', $adjustment_type));
        if (strpos($notes, $type_label) === false) {
            $formatted_notes = "[$type_label] $direction by " . abs($difference) . " - " . $formatted_notes;
        }
    } else if (strpos($notes, $direction) === false) {
        $formatted_notes = "$direction by " . abs($difference) . " - " . $formatted_notes;
    }

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
            ':transaction_type' => 'adjustment',
            ':quantity' => abs($difference),
            ':notes' => $formatted_notes,
            ':created_by' => $_SESSION['user_id']
        ]);
        
        // Update the menu_item's current_stock directly
        $stmt = $conn->prepare("
            UPDATE menu_items
            SET current_stock = :new_stock
            WHERE id = :menu_item_id
        ");
        
        $stmt->execute([
            ':new_stock' => $new_stock,
            ':menu_item_id' => $menu_item_id
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

header('Location: /ERC-POS/views/inventory/stock_adjustment.php'); 