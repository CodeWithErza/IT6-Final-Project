<?php
require_once __DIR__ . '/../../helpers/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $category_id = $_POST['category_id'] ?? null;
    $item_id = $_POST['item_id'] ?? null;
    $new_index = $_POST['new_index'] ?? null;

    if (!$category_id || !$item_id || !is_numeric($new_index)) {
        throw new Exception('Missing or invalid parameters');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Get current order of items in the category
        $stmt = $conn->prepare("
            SELECT id, display_order 
            FROM menu_items 
            WHERE category_id = ? 
            ORDER BY display_order, name
        ");
        $stmt->execute([$category_id]);
        $items = $stmt->fetchAll();

        // Create new order array
        $order = array_map(function($item) {
            return $item['id'];
        }, $items);

        // Remove item from current position and insert at new position
        $current_pos = array_search($item_id, $order);
        if ($current_pos !== false) {
            array_splice($order, $current_pos, 1);
        }
        array_splice($order, $new_index, 0, $item_id);

        // Update display_order for all affected items
        foreach ($order as $index => $id) {
            $stmt = $conn->prepare("
                UPDATE menu_items 
                SET display_order = ?, 
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$index * 10, $_SESSION['user_id'], $id]);
        }

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'update_order',
            'menu_items',
            $item_id,
            ['old_index' => $current_pos],
            ['new_index' => $new_index]
        );

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Error updating menu item order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 