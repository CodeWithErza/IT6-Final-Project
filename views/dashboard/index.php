<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get today's stats
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_sales
    FROM orders o
    WHERE DATE(o.created_at) = ? AND o.status = 'completed'
");
$stmt->execute([$today]);
$today_stats = $stmt->fetch();

// Get low stock items
$stmt = $conn->prepare("
    SELECT 
        m.*,
        COALESCE(SUM(CASE 
            WHEN it.transaction_type = 'initial' THEN it.quantity
            WHEN it.transaction_type = 'stock_in' THEN it.quantity
            WHEN it.transaction_type = 'stock_out' THEN -it.quantity
            ELSE 0
        END), 0) as current_stock
    FROM menu_items m
    LEFT JOIN inventory_transactions it ON m.id = it.menu_item_id
    WHERE m.is_inventory_item = 1 
    AND m.is_active = 1
    GROUP BY m.id
    HAVING current_stock <= 10
    ORDER BY current_stock ASC
    LIMIT 5
");
$stmt->execute();
$low_stock_items = $stmt->fetchAll();

// Get total menu items
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM menu_items
    WHERE is_active = 1
");
$stmt->execute();
$menu_items_count = $stmt->fetch()['total'];

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.created_by = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h2 mb-4">Dashboard</h1>
        </div>
    </div>
    
    <div class="row">
        <!-- Sales Today -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sales Today</h5>
                    <h2 class="card-text">₱<?php echo number_format($today_stats['total_sales'], 2); ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Orders Today -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Orders Today</h5>
                    <h2 class="card-text"><?php echo $today_stats['total_orders']; ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Items -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h2 class="card-text"><?php echo count($low_stock_items); ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Total Menu Items -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Menu Items</h5>
                    <h2 class="card-text"><?php echo $menu_items_count; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Low Stock Alert -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Low Stock Alert</h5>
                </div>
                <div class="card-body">
                    <?php if ($low_stock_items): ?>
                        <ul class="list-group">
                            <?php foreach ($low_stock_items as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $item['current_stock']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No items are running low on stock.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_orders): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Cashier</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
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
                                            </td>
                                            <td><?php echo htmlspecialchars($order['username'] ?? 'System'); ?></td>
                                            <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent orders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 