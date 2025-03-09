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
    header('Location: /ERC-POS/views/inventory/stock_in.php');
    exit;
}

$menu_item_id = $_POST['menu_item_id'] ?? '';
$quantity = $_POST['quantity'] ?? '';
$unit_price = $_POST['unit_price'] ?? '';
$transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
$invoice_number = $_POST['invoice_number'] ?? '';
$supplier = $_POST['supplier'] ?? '';
$notes = $_POST['notes'] ?? '';

// Keep notes as just notes, don't add supplier and invoice info
$final_notes = $notes;

if (empty($menu_item_id) || empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid input data';
    header('Location: /ERC-POS/views/inventory/stock_in.php');
    exit;
}

try {
    // Start transaction
    if (!$conn->beginTransaction()) {
        throw new Exception("Could not start transaction");
    }

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

    // Calculate new stock level
    $new_stock = $current_stock + $quantity;

    // Insert inventory transaction
    $stmt = $conn->prepare("
        INSERT INTO inventory_transactions (
            menu_item_id,
            transaction_type,
            quantity,
            unit_price,
            supplier,
            invoice_number,
            notes,
            created_by,
            created_at
        ) VALUES (
            :menu_item_id,
            'stock_in',
            :quantity,
            :unit_price,
            :supplier,
            :invoice_number,
            :notes,
            :created_by,
            :created_at
        )
    ");

    $stmt->execute([
        ':menu_item_id' => $menu_item_id,
        ':quantity' => $quantity,
        ':unit_price' => $unit_price,
        ':supplier' => $supplier,
        ':invoice_number' => $invoice_number,
        ':notes' => $final_notes,
        ':created_by' => $_SESSION['user_id'],
        ':created_at' => $transaction_date
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

    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception("Could not commit transaction");
    }

    $_SESSION['success'] = 'Stock added successfully';
} catch (Exception $e) {
    // Only rollback if a transaction is active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = 'Error adding stock: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/inventory/stock_in.php'); 