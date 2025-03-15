<?php
require_once __DIR__ . '/../../helpers/functions.php';
require_once '../../includes/session.php';
require_once '../../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ERC-POS/views/dashboard/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    echo "<script>
        alert('Invalid request method');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
    exit;
}

$name = trim($_POST['name'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 1; // Default to active for new categories

if (empty($name)) {
    $_SESSION['error'] = "Category name is required";
    echo "<script>
        alert('Category name is required');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
    exit;
}

try {
    // Check if category already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "A category with this name already exists";
        echo "<script>
            alert('A category with this name already exists');
            window.location.href = '/ERC-POS/views/settings/index.php';
        </script>";
        exit;
    }

    // Create the category
    $stmt = $conn->prepare("INSERT INTO categories (name, is_active) VALUES (?, ?)");
    $stmt->execute([$name, $is_active]);

    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'create',
        'categories',
        $conn->lastInsertId(),
        null,
        [
            'name' => $name,
            'created_by' => $_SESSION['user_id']
        ]
    );

    $_SESSION['success'] = "Category created successfully";
    echo "<script>
        alert('Category created successfully');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error creating category: " . $e->getMessage();
    echo "<script>
        alert('Error creating category: " . addslashes($e->getMessage()) . "');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
} 