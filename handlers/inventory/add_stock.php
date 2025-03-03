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
$quantity = $_POST['quantity'] ?? '';
$unit_price = $_POST['unit_price'] ?? '';
$transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
$invoice_number = $_POST['invoice_number'] ?? '';
$supplier = $_POST['supplier'] ?? '';
$notes = $_POST['notes'] ?? '';

// Build notes with additional information
$transaction_notes = [];
if ($supplier) $transaction_notes[] = "Supplier: $supplier";
if ($invoice_number) $transaction_notes[] = "OR/Invoice #: $invoice_number";
if ($notes) $transaction_notes[] = $notes;
$final_notes = implode(" | ", $transaction_notes);

if (empty($menu_item_id) || empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid input data';
    header('Location: /ERC-POS/views/inventory/index.php');
    exit;
}

try {
    // Start transaction
    if (!$conn->beginTransaction()) {
        throw new Exception("Could not start transaction");
    }

    // First, check if the inventory_transactions table has the unit_price column
    $stmt = $conn->prepare("SHOW COLUMNS FROM inventory_transactions LIKE 'unit_price'");
    $stmt->execute();
    $column_exists = $stmt->fetch();

    if (!$column_exists) {
        // Add the unit_price column if it doesn't exist
        $conn->exec("ALTER TABLE inventory_transactions ADD COLUMN unit_price DECIMAL(10,2) DEFAULT NULL AFTER quantity");
    }

    // Insert inventory transaction
    $stmt = $conn->prepare("
        INSERT INTO inventory_transactions (
            menu_item_id,
            transaction_type,
            quantity,
            unit_price,
            notes,
            created_by,
            created_at
        ) VALUES (
            :menu_item_id,
            'stock_in',
            :quantity,
            :unit_price,
            :notes,
            :created_by,
            :created_at
        )
    ");

    $stmt->execute([
        ':menu_item_id' => $menu_item_id,
        ':quantity' => $quantity,
        ':unit_price' => $unit_price,
        ':notes' => $final_notes,
        ':created_by' => $_SESSION['user_id'],
        ':created_at' => $transaction_date
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

header('Location: /ERC-POS/views/inventory/index.php'); 