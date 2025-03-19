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

// Get cart items
$cart_items_json = $_POST['cart_items'] ?? '';

// Log the raw cart items JSON for debugging
error_log("Raw cart items JSON: " . $cart_items_json);

$cart_items = json_decode($cart_items_json, true);

// Log the decoded cart items for debugging
error_log("Decoded cart items: " . print_r($cart_items, true));

// Get common transaction details
$transaction_date = $_POST['transaction_date'] ?? date('Y-m-d H:i:s');
$invoice_number = $_POST['invoice_number'] ?? '';
$supplier = $_POST['supplier'] ?? '';
$notes = $_POST['notes'] ?? '';

// Validate cart items
if (empty($cart_items) || !is_array($cart_items)) {
    $_SESSION['error'] = 'No items in cart';
    header('Location: /ERC-POS/views/inventory/index.php');
    exit;
}

try {
    // Start transaction
    begin_transaction('stock_in');

    $total_items = 0;
    $total_cost = 0;

    // Process each item in the cart
    foreach ($cart_items as $item) {
        $menu_item_id = $item['id'] ?? '';
        $quantity = $item['quantity'] ?? 0;
        $unit_price = $item['unitPrice'] ?? 0;

        // Validate item data
        if (empty($menu_item_id) || !is_numeric($quantity) || $quantity <= 0 || !is_numeric($unit_price) || $unit_price < 0) {
            throw new Exception('Invalid item data: ' . print_r($item, true));
        }

        // Insert inventory transaction directly
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions (
                menu_item_id,
                transaction_type,
                quantity,
                unit_price,
                notes,
                supplier,
                invoice_number,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $menu_item_id,
            'stock_in',
            $quantity,
            $unit_price,
            $notes,
            $supplier,
            $invoice_number,
            $_SESSION['user_id'],
            $transaction_date
        ]);

        // Update menu item stock
        $stmt = $conn->prepare("
            UPDATE menu_items 
            SET current_stock = current_stock + ? 
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $menu_item_id]);

        // Update totals
        $total_items += $quantity;
        $total_cost += $quantity * $unit_price;
    }

    // Commit transaction
    commit_transaction();

    $_SESSION['success'] = "Stock added successfully: $total_items items with a total cost of ₱" . number_format($total_cost, 2);
} catch (Exception $e) {
    // Rollback transaction on error
    rollback_transaction();
    
    error_log("Error in add_stock.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error adding stock: ' . $e->getMessage();
}

header('Location: /ERC-POS/views/inventory/index.php'); 