<?php
require_once '../../helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get order ID
$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    http_response_code(400);
    exit('Order ID is required');
}

try {
    // Get order data with all necessary information
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            u.username as created_by_name,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN users u ON u.id = o.created_by
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        exit('Order not found');
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT 
            oi.*,
            m.name as menu_item_name
        FROM order_items oi
        JOIN menu_items m ON m.id = oi.menu_item_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

    // Get settings
    $stmt = $conn->prepare("
        SELECT setting_name, setting_value 
        FROM settings 
        WHERE setting_group IN ('business', 'receipt')
    ");
    $stmt->execute();
    $settings_result = $stmt->fetchAll();

    // Convert settings to associative array
    $settings = [];
    foreach ($settings_result as $setting) {
        $settings[$setting['setting_name']] = $setting['setting_value'];
    }

    // Generate receipt HTML
    $receipt_html = '
        <div class="receipt-container">
            <div class="text-center mb-3">
                <img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" style="max-width: 80px; margin-bottom: 10px;">
                <h4>' . htmlspecialchars($settings['business_name'] ?? 'ERC Carinderia') . '</h4>
                ' . ($settings['business_address'] ? '<p class="mb-1">' . nl2br(htmlspecialchars($settings['business_address'])) . '</p>' : '') . '
                ' . ($settings['business_phone'] ? '<p class="mb-1">' . htmlspecialchars($settings['business_phone']) . '</p>' : '') . '
                <p class="mb-1">Order #' . htmlspecialchars($order['order_number']) . '</p>
                <p class="mb-1">' . date('Y-m-d g:i A', strtotime($order['created_at'])) . '</p>
                <p class="mb-1">Cashier: ' . htmlspecialchars($order['created_by_name']) . '</p>
            </div>
            
            <div class="border-top border-bottom py-3 mb-3">
                ' . implode('', array_map(function($item) {
                    return '
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <div>' . htmlspecialchars($item['menu_item_name']) . '</div>
                                <div class="text-muted small">₱' . number_format($item['unit_price'], 2) . ' × ' . $item['quantity'] . '</div>
                            </div>
                            <div>₱' . number_format($item['unit_price'] * $item['quantity'], 2) . '</div>
                        </div>';
                }, $order_items)) . '
            </div>
            
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>₱' . number_format($order['subtotal_amount'], 2) . '</span>
                </div>
                ' . ($order['discount_amount'] > 0 ? '
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount ' . ($order['discount_type'] ? '(' . ucfirst($order['discount_type']) . ')' : '') . ':</span>
                        <span>-₱' . number_format($order['discount_amount'], 2) . '</span>
                    </div>
                ' : '') . '
                <div class="d-flex justify-content-between mb-2">
                    <strong>Total:</strong>
                    <strong>₱' . number_format($order['total_amount'], 2) . '</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Amount Received:</span>
                    <span>₱' . number_format($order['cash_received'], 2) . '</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Change:</span>
                    <span>₱' . number_format($order['cash_change'], 2) . '</span>
                </div>
                ' . ($order['payment_method'] !== 'cash' ? '
                    <div class="d-flex justify-content-between mt-2">
                        <span>Payment Method:</span>
                        <span>' . strtoupper($order['payment_method']) . '</span>
                    </div>
                ' : '') . '
                ' . ($order['notes'] ? '
                    <div class="mt-2">
                        <span>Notes:</span>
                        <p class="small mt-1">' . htmlspecialchars($order['notes']) . '</p>
                    </div>
                ' : '') . '
            </div>
            
            <div class="text-center">
                <p class="mb-1">' . htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for your business!') . '</p>
                <p class="small text-muted mb-0">Please come again</p>
            </div>
        </div>
    ';

    echo $receipt_html;
} catch (Exception $e) {
    http_response_code(500);
    exit('Error loading receipt: ' . $e->getMessage());
} 