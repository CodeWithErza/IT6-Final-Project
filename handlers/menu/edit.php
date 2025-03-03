<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $is_inventory_item = isset($_POST['is_inventory_item']) ? 1 : 0;
    $remove_image = isset($_POST['remove_image']) ? 1 : 0;

    // Validate required fields
    if (!$id) {
        throw new Exception('Menu item ID is required');
    }
    if (empty($name)) {
        throw new Exception('Menu item name is required');
    }
    if (empty($category_id)) {
        throw new Exception('Category is required');
    }
    if (!is_numeric($price) || $price < 0) {
        throw new Exception('Valid price is required');
    }

    // Get current menu item data
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $menu_item = $stmt->fetch();

    if (!$menu_item) {
        throw new Exception('Menu item not found');
    }

    // Handle image upload/removal
    $image_path = $menu_item['image_path'];
    
    if ($remove_image) {
        // Delete the existing image file
        if ($image_path && file_exists(__DIR__ . '/../../' . $image_path)) {
            unlink(__DIR__ . '/../../' . $image_path);
        }
        $image_path = null;
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file: ' . $file['error']);
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and WebP files are allowed.');
        }

        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 2MB.');
        }

        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../../uploads/menu_items';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Delete the existing image file if it exists
        if ($menu_item['image_path'] && file_exists(__DIR__ . '/../../' . $menu_item['image_path'])) {
            unlink(__DIR__ . '/../../' . $menu_item['image_path']);
        }

        // Generate unique filename
        $extension = match($mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new Exception('Unsupported image type')
        };
        $filename = uniqid('menu_', true) . '.' . $extension;
        $image_path = 'uploads/menu_items/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../../' . $image_path)) {
            throw new Exception('Error saving uploaded file');
        }
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update menu item
        $stmt = $conn->prepare("
            UPDATE menu_items 
            SET name = ?,
                category_id = ?,
                price = ?,
                image_path = ?,
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $category_id, $price, $image_path, $_SESSION['user_id'], $id]);

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'update',
            'menu_items',
            $id,
            [
                'name' => $menu_item['name'],
                'category_id' => $menu_item['category_id'],
                'price' => $menu_item['price'],
                'image_path' => $menu_item['image_path']
            ],
            [
                'name' => $name,
                'category_id' => $category_id,
                'price' => $price,
                'image_path' => $image_path
            ]
        );

        $conn->commit();
        $_SESSION['success'] = "Menu item '{$name}' has been updated successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        // Delete uploaded image if exists
        if ($image_path && $image_path !== $menu_item['image_path'] && file_exists(__DIR__ . '/../../' . $image_path)) {
            unlink(__DIR__ . '/../../' . $image_path);
        }
        throw $e;
    }

    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} catch (Exception $e) {
    error_log("Error updating menu item: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: /ERC-POS/views/menu/edit.php?id=" . ($id ?? ''));
    exit;
} 