<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('Menu item ID is required');
    }

    // Get current menu item data
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $menu_item = $stmt->fetch();

    if (!$menu_item) {
        throw new Exception('Menu item not found');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Toggle status
        $new_status = !$menu_item['is_active'];
        $stmt = $conn->prepare("UPDATE menu_items SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'update',
            'menu_items',
            $id,
            ['is_active' => $menu_item['is_active']],
            ['is_active' => $new_status]
        );

        $conn->commit();
        $_SESSION['success'] = "Menu item '" . $menu_item['name'] . "' has been " . ($new_status ? 'activated' : 'deactivated') . ".";
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} catch (Exception $e) {
    error_log("Error toggling menu item status: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} 