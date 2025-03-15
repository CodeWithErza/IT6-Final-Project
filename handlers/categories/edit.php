<?php
require_once __DIR__ . '/../../helpers/functions.php';

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

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($id) || empty($name)) {
    $_SESSION['error'] = "Category ID and name are required";
    echo "<script>
        alert('Category ID and name are required');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
    exit;
}

try {
    // Get current category data
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception('Category not found');
    }

    // Check if another category with the same name exists (excluding current category)
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "A category with this name already exists";
        echo "<script>
            alert('A category with this name already exists');
            window.location.href = '/ERC-POS/views/settings/index.php';
        </script>";
        exit;
    }

    // Update the category
    $stmt = $conn->prepare("UPDATE categories SET name = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$name, $is_active, $id]);

    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'update',
        'categories',
        $id,
        ['name' => $category['name']],
        ['name' => $name]
    );

    $_SESSION['success'] = "Category updated successfully";
    echo "<script>
        alert('Category updated successfully');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
} catch (Exception $e) {
    $_SESSION['error'] = "Error updating category: " . $e->getMessage();
    echo "<script>
        alert('Error updating category: " . addslashes($e->getMessage()) . "');
        window.location.href = '/ERC-POS/views/settings/index.php';
    </script>";
} 