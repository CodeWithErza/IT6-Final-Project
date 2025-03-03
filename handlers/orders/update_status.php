<?php
require_once __DIR__ . '/../../helpers/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? '';

    if (!$id) {
        throw new Exception('Order ID is required');
    }
    if (!in_array($status, ['completed', 'cancelled'])) {
        throw new Exception('Invalid status');
    }

    // Get current order
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    if ($order['status'] !== 'pending') {
        throw new Exception('Only pending orders can be updated');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = ?,
                updated_by = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$status, $_SESSION['user_id'], $id]);

        // If completing the order and it has inventory items, update stock
        if ($status === 'completed') {
            // Get order items that are inventory items
            $stmt = $conn->prepare("
                SELECT 
                    oi.menu_item_id,
                    oi.quantity,
                    m.name
                FROM order_items oi
                JOIN menu_items m ON oi.menu_item_id = m.id
                WHERE oi.order_id = ? AND m.is_inventory_item = 1
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            // Update stock for each item
            foreach ($items as $item) {
                // Add inventory transaction
                $stmt = $conn->prepare("
                    INSERT INTO inventory_transactions 
                    (menu_item_id, transaction_type, quantity, notes, created_by)
                    VALUES (?, 'stock_out', ?, ?, ?)
                ");
                $stmt->execute([
                    $item['menu_item_id'],
                    $item['quantity'],
                    "Order #{$order['order_number']} completed",
                    $_SESSION['user_id']
                ]);
            }
        }

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'update_status',
            'orders',
            $id,
            ['status' => $order['status']],
            ['status' => $status]
        );

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Error updating order status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 