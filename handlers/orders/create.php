<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if request is POST and has JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST) && empty(file_get_contents('php://input'))) {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required data
    if (empty($data['items'])) {
        echo json_encode(['success' => false, 'error' => 'No items in order']);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    // Generate order number (YYYYMMDD-XXXX format)
    $date = date('Ymd');
    $stmt = $conn->prepare("
        SELECT COUNT(*) + 1 as next_number 
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $orderNumber = $date . '-' . str_pad($result['next_number'], 4, '0', STR_PAD_LEFT);

    // Insert order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            order_number,
            subtotal_amount,
            discount_type,
            discount_amount,
            total_amount,
            cash_received,
            cash_change,
            payment_method,
            notes,
            status,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
    ");
    $stmt->execute([
        $orderNumber,
        $data['subtotal'],
        $data['discount_type'],
        $data['discount_amount'],
        $data['total'],
        $data['amount_received'],
        $data['change'],
        $data['payment_method'],
        $data['notes'],
        $_SESSION['user_id']
    ]);
    
    $orderId = $conn->lastInsertId();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (
            order_id,
            menu_item_id,
            quantity,
            unit_price,
            subtotal
        ) VALUES (?, ?, ?, ?, ?)
    ");

    // Process each item
    foreach ($data['items'] as $item) {
        $subtotal = $item['quantity'] * $item['unit_price'];
        $stmt->execute([
            $orderId,
            $item['menu_item_id'],
            $item['quantity'],
            $item['unit_price'],
            $subtotal
        ]);

        // Update inventory if menu item is tracked
        $inventoryStmt = $conn->prepare("
            INSERT INTO inventory_transactions (
                menu_item_id,
                transaction_type,
                quantity,
                notes,
                created_by,
                created_at
            ) 
            SELECT 
                ?,
                'stock_out',
                ?,
                CONCAT('Order: ', ?),
                ?,
                NOW()
            FROM menu_items 
            WHERE id = ? AND is_inventory_item = 1
        ");
        $inventoryStmt->execute([
            $item['menu_item_id'],
            $item['quantity'],
            $orderNumber,
            $_SESSION['user_id'],
            $item['menu_item_id']
        ]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order completed successfully',
        'order_number' => $orderNumber
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Error processing order: ' . $e->getMessage()
    ]);
} 