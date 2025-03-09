<?php
require_once __DIR__ . '/../../helpers/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $is_inventory_item = isset($_POST['is_inventory_item']) ? 1 : 0;
    $initial_stock = $is_inventory_item ? ($_POST['initial_stock'] ?? 0) : 0;

    // Validate required fields
    if (empty($name)) {
        throw new Exception('Menu item name is required');
    }
    if (empty($category_id)) {
        throw new Exception('Category is required');
    }
    if (!is_numeric($price) || $price < 0) {
        throw new Exception('Valid price is required');
    }
    if ($is_inventory_item && (!is_numeric($initial_stock) || $initial_stock < 0)) {
        throw new Exception('Valid initial stock is required for inventory items');
    }

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
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
        // Insert menu item
        $stmt = $conn->prepare("
            INSERT INTO menu_items (name, category_id, price, is_inventory_item, image_path, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $price, $is_inventory_item, $image_path, $_SESSION['user_id']]);
        
        $menu_item_id = $conn->lastInsertId();

        // If it's an inventory item, create initial stock record
        if ($is_inventory_item && $initial_stock > 0) {
            // Add inventory transaction
            $stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (menu_item_id, transaction_type, quantity, notes, created_by)
                VALUES (?, 'stock_in', ?, 'Initial stock setup', ?)
            ");
            $stmt->execute([$menu_item_id, $initial_stock, $_SESSION['user_id']]);
        }

        // Log the action
        log_audit(
            $_SESSION['user_id'],
            'create',
            'menu_items',
            $menu_item_id,
            null,
            [
                'name' => $name,
                'category_id' => $category_id,
                'price' => $price,
                'is_inventory_item' => $is_inventory_item,
                'image_path' => $image_path,
                'initial_stock' => $initial_stock
            ]
        );

        $conn->commit();
        $_SESSION['success'] = "Menu item '{$name}' has been created successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        // Delete uploaded image if exists
        if ($image_path && file_exists(__DIR__ . '/../../' . $image_path)) {
            unlink(__DIR__ . '/../../' . $image_path);
        }
        throw $e;
    }

    header("Location: /ERC-POS/views/menu/index.php");
    exit;
} catch (Exception $e) {
    error_log("Error creating menu item: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: /ERC-POS/views/menu/create.php");
    exit;
} 