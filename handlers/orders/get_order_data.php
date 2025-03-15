<?php
require_once __DIR__ . '/../../helpers/functions.php';
check_login();

// Set content type to JSON
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

$order_id = intval($_GET['id']);

try {
    // Call stored procedure to get order details
    $stmt = $conn->prepare("CALL sp_get_order_details(?)");
    $stmt->execute([$order_id]);
    
    // Get order header from first result set
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Move to next result set for order items
    $stmt->nextRowset();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get business settings for receipt
    $stmt = $conn->prepare("
        SELECT setting_name, setting_value 
        FROM settings 
        WHERE setting_group IN ('business', 'system', 'receipt')
    ");
    $stmt->execute();
    $settings_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert settings to associative array
    $settings = [];
    foreach ($settings_result as $setting) {
        $settings[$setting['setting_name']] = $setting['setting_value'];
    }

    // Return data as JSON
    echo json_encode([
        'order' => $order,
        'items' => $order_items,
        'settings' => $settings
    ]);
} catch (Exception $e) {
    error_log("Error in get_order_data.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error retrieving order data. Please try again.']);
} 