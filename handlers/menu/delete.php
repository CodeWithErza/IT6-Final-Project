<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception('Menu item ID is required');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Get menu item data first
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $menu_item = $stmt->fetch();

        if (!$menu_item) {
            throw new Exception('Menu item not found');
        }

        // Check for inventory transactions
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inventory_transactions WHERE menu_item_id = ?");
        $stmt->execute([$id]);
        $has_transactions = $stmt->fetchColumn() > 0;

        if ($has_transactions) {
            throw new Exception('Cannot delete menu item with inventory transactions');
        }

        // Delete the menu item
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);

        // Delete the image file if it exists
        if ($menu_item['image_path'] && file_exists(__DIR__ . '/../../' . $menu_item['image_path'])) {
            unlink(__DIR__ . '/../../' . $menu_item['image_path']);
        }

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'delete',
            'menu_items',
            $id,
            $menu_item,
            null
        );

        $conn->commit();
        $_SESSION['success'] = "Menu item '{$menu_item['name']}' has been deleted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} catch (Exception $e) {
    error_log("Error deleting menu item: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} 