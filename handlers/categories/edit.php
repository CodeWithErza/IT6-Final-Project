<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ERC-POS/views/dashboard/index.php");
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if (empty($id)) {
        throw new Exception('Category ID is required');
    }
    if (empty($name)) {
        throw new Exception('Category name is required');
    }

    // Get current category data
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception('Category not found');
    }

    // Check if new name already exists (excluding current category)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('A category with this name already exists');
    }

    // Update category
    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'update',
        'categories',
        $id,
        ['name' => $category['name']],
        ['name' => $name]
    );

    $_SESSION['success'] = "Category updated successfully!";
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Error updating category: " . $e->getMessage();
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} 