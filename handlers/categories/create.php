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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        throw new Exception('Category name is required');
    }

    // Check if category name already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('A category with this name already exists');
    }

    // Create category
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);

    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'create',
        'categories',
        $conn->lastInsertId(),
        null,
        ['name' => $name, 'description' => $description]
    );

    $_SESSION['success'] = "Category created successfully!";
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Error creating category: " . $e->getMessage();
    header("Location: /ERC-POS/views/settings/index.php");
    exit;
} 