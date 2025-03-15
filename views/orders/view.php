<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if it's an AJAX request
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Only include header if not an AJAX request
if (!$is_ajax) {
    include __DIR__ . '/../../static/templates/header.php';
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    if ($is_ajax) {
        echo json_encode(['error' => 'Invalid order ID']);
        exit;
    } else {
        $_SESSION['error'] = 'Invalid order ID';
        header('Location: /ERC-POS/views/orders/index.php');
        exit;
    }
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("
    SELECT 
        o.*,
        u.username as created_by_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.created_by
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    if ($is_ajax) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    } else {
        $_SESSION['error'] = 'Order not found';
        header('Location: /ERC-POS/views/orders/index.php');
        exit;
    }
}

// Get order items
$stmt = $conn->prepare("
    SELECT 
        oi.*,
        m.name as menu_item_name
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Get business settings for receipt
$stmt = $conn->prepare("
    SELECT setting_name, setting_value 
    FROM settings 
    WHERE setting_group IN ('business', 'system', 'receipt')
");
$stmt->execute();
$settings_result = $stmt->fetchAll();

// Convert settings to associative array
$settings = [];
foreach ($settings_result as $setting) {
    $settings[$setting['setting_name']] = $setting['setting_value'];
}

// If it's an AJAX request, only return the receipt HTML
if ($is_ajax) {
    // Include the receipt HTML
    include __DIR__ . '/receipt_template.php';
    exit;
}

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">
                <i class="fas fa-receipt me-2"></i>
                Order Details
            </h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="/ERC-POS/views/orders/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
            <button class="btn btn-primary" id="printReceiptBtn">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Order Number:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($order['order_number']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Date & Time:</div>
                        <div class="col-md-8"><?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            <?php
                            $badge_class = match($order['status']) {
                                'pending' => 'bg-warning',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Payment Method:</div>
                        <div class="col-md-8"><?php echo ucfirst($order['payment_method']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Subtotal:</div>
                        <div class="col-md-8">₱<?php echo number_format($order['subtotal_amount'], 2); ?></div>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Discount:</div>
                        <div class="col-md-8">
                            ₱<?php echo number_format($order['discount_amount'], 2); ?>
                            <?php if ($order['discount_type']): ?>
                                (<?php echo ucfirst($order['discount_type']); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Total Amount:</div>
                        <div class="col-md-8">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Cash Received:</div>
                        <div class="col-md-8">₱<?php echo number_format($order['cash_received'], 2); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Change:</div>
                        <div class="col-md-8">₱<?php echo number_format($order['cash_change'], 2); ?></div>
                    </div>
                    <?php if ($order['notes']): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Notes:</div>
                        <div class="col-md-8"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Created By:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($order['created_by_name']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Preview -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Receipt Preview</h5>
                </div>
                <div class="card-body">
                    <?php include __DIR__ . '/receipt_template.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Order Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($item['menu_item_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td><?php echo $item['notes'] ? htmlspecialchars($item['notes']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($order_items)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">No items found for this order.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Receipt Styles */
.receipt-container {
    background-color: white;
    max-width: 350px;
    margin: 0 auto;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.receipt-logo {
    max-width: 80px;
    height: auto;
    margin-bottom: 10px;
}

.business-name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 5px;
}

.business-address, .business-phone {
    margin-bottom: 5px;
}

.receipt-info {
    margin: 10px 0;
}

.receipt-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.receipt-divider {
    border-top: 1px dashed #ccc;
    margin: 10px 0;
}

.receipt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.receipt-table th {
    text-align: left;
    padding: 3px 0;
    border-bottom: 1px solid #ccc;
}

.receipt-table td {
    padding: 3px 0;
}

.item-name {
    width: 40%;
}

.item-qty {
    width: 15%;
    text-align: center;
}

.item-price, .item-total {
    width: 22.5%;
    text-align: right;
}

.total-row {
    font-weight: bold;
    font-size: 14px;
    margin: 5px 0;
}

.receipt-footer {
    margin-top: 15px;
    font-size: 11px;
}

@media print {
    body * {
        visibility: hidden;
    }
    #receipt, #receipt * {
        visibility: visible;
    }
    #receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: none;
        box-shadow: none;
        padding: 0;
    }
    .receipt-container {
        box-shadow: none;
    }
}
</style>

<script>
document.getElementById('printReceiptBtn').addEventListener('click', function() {
    window.print();
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 