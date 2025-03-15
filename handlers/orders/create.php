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

    // Start transaction for order creation
    begin_transaction('order_creation');
    
    // Generate order number (YYYYMMDD-XXXX format) with retry mechanism
    $maxRetries = 5;
    $retryCount = 0;
    $orderNumber = null;
    $orderCreated = false;
    
    while (!$orderCreated && $retryCount < $maxRetries) {
        try {
            $date = date('Ymd');
            
            // Get the latest order number for today
            $stmt = $conn->prepare("
                SELECT MAX(SUBSTRING_INDEX(order_number, '-', -1)) as last_number 
                FROM orders 
                WHERE order_number LIKE ?
            ");
            $stmt->execute([$date . '-%']);
            $result = $stmt->fetch();
            
            $nextNumber = 1;
            if ($result && $result['last_number']) {
                $nextNumber = intval($result['last_number']) + 1;
            }
            
            $orderNumber = $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Insert order with the generated order number
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
            $orderCreated = true;
        } catch (PDOException $e) {
            // If duplicate entry error, retry with a new order number
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $retryCount++;
                // Small delay to reduce chance of collision
                usleep(100000); // 100ms delay
            } else {
                // For other errors, rethrow
                throw $e;
            }
        }
    }
    
    // If we couldn't create an order after max retries, throw an exception
    if (!$orderCreated) {
        throw new Exception("Failed to generate a unique order number after $maxRetries attempts");
    }

    // Create savepoint before adding items
    begin_transaction('order_items');
    
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

        // Note: Stock out functionality has been removed and replaced with a more flexible expenses system
    }
    
    // If we get here, everything succeeded
    commit_transaction();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order completed successfully',
        'order_number' => $orderNumber
    ]);

} catch (Exception $e) {
    // Rollback to the beginning if anything fails
    rollback_transaction('order_creation');
    
    echo json_encode([
        'success' => false,
        'error' => 'Error processing order: ' . $e->getMessage()
    ]);
} 