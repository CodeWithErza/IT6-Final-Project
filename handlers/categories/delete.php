<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ERC-POS/views/dashboard/index.php");
    exit;
}

try {
    // Validate and sanitize input
    $id = intval($_GET['id'] ?? 0);

    if (empty($id)) {
        throw new Exception('Category ID is required');
    }

    // Get current category data
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception('Category not found');
    }

    // Check if category has menu items
    $stmt = $conn->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete category that has menu items');
    }

    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'delete',
        'categories',
        $id,
        ['name' => $category['name'], 'description' => $category['description']],
        null
    );

    $_SESSION['success'] = "Category deleted successfully!";
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} 