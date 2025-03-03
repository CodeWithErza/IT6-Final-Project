<?php
require_once '../../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to continue']);
    exit;
}

// Validate input
$menu_item_id = $_GET['menu_item_id'] ?? '';

if (empty($menu_item_id) || !is_numeric($menu_item_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid menu item ID']);
    exit;
}

try {
    // Get transaction history
    $stmt = $conn->prepare("
        SELECT 
            it.*,
            u.username
        FROM inventory_transactions it
        JOIN users u ON it.created_by = u.id
        WHERE it.menu_item_id = :menu_item_id
        ORDER BY it.created_at DESC
    ");

    $stmt->execute([':menu_item_id' => $menu_item_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($history);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching history: ' . $e->getMessage()]);
} 