<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $menu_item_id = $_POST['menu_item_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);

    // Validate input
    if (!$menu_item_id) {
        throw new Exception('Menu item ID is required');
    }
    if (!in_array($type, ['add', 'subtract'])) {
        throw new Exception('Invalid adjustment type');
    }
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than zero');
    }

    // Get menu item
    $stmt = $conn->prepare("
        SELECT m.*, 
               COALESCE(SUM(CASE 
                   WHEN it.transaction_type = 'initial' THEN it.quantity
                   WHEN it.transaction_type = 'stock_in' THEN it.quantity
                   WHEN it.transaction_type = 'stock_out' THEN -it.quantity
                   ELSE 0
               END), 0) as current_stock
        FROM menu_items m
        LEFT JOIN inventory_transactions it ON m.id = it.menu_item_id
        WHERE m.id = ?
        GROUP BY m.id
    ");
    $stmt->execute([$menu_item_id]);
    $menu_item = $stmt->fetch();

    if (!$menu_item) {
        throw new Exception('Menu item not found');
    }

    if (!$menu_item['is_inventory_item']) {
        throw new Exception('This menu item does not track inventory');
    }

    // Check if we have enough stock for subtraction
    if ($type === 'subtract' && $quantity > $menu_item['current_stock']) {
        throw new Exception('Not enough stock available');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Add inventory transaction
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions 
            (menu_item_id, transaction_type, quantity, notes, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $menu_item_id,
            $type === 'add' ? 'stock_in' : 'stock_out',
            $quantity,
            $type === 'add' ? 'Manual stock addition' : 'Manual stock reduction',
            $_SESSION['user_id']
        ]);

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'stock_adjustment',
            'menu_items',
            $menu_item_id,
            ['current_stock' => $menu_item['current_stock']],
            [
                'adjustment_type' => $type,
                'quantity' => $quantity,
                'new_stock' => $menu_item['current_stock'] + ($type === 'add' ? $quantity : -$quantity)
            ]
        );

        $conn->commit();

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => sprintf(
                'Stock has been %s by %d units.',
                $type === 'add' ? 'increased' : 'decreased',
                $quantity
            )
        ]);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Error adjusting stock: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
} 