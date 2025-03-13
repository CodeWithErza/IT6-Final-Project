<?php
require_once __DIR__ . '/helpers/functions.php';
include __DIR__ . '/static/templates/header.php';

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
            <h1 class="h2 mb-4">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard Overview
            </h1>
        </div>
    </div>

    <div class="row">
        <!-- Sales Today -->
        <div class="col-md-3 mb-4">
            <a href="/ERC-POS/views/reports/sales.php" class="text-decoration-none">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-cash-register fa-lg"></i>
                    </div>
                    <div class="stats-title">Sales Today</div>
                    <div class="stats-value">₱<?php echo number_format($today_stats['total_sales'], 2); ?></div>
                    <div class="stats-change positive">
                        <i class="fas fa-chart-line me-1"></i>
                        Daily Revenue
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Orders Today -->
        <div class="col-md-3 mb-4">
            <a href="/ERC-POS/views/orders/index.php" class="text-decoration-none">
                <div class="stats-card">
                    <div class="stats-icon accent">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                    </div>
                    <div class="stats-title">Orders Today</div>
                    <div class="stats-value"><?php echo $today_stats['total_orders']; ?></div>
                    <div class="stats-change">
                        <i class="fas fa-receipt me-1"></i>
                        Total Transactions
                    </div>
                </div>
            </a>
        </div>

        <!-- Low Stock Items -->
        <div class="col-md-3 mb-4">
            <a href="/ERC-POS/views/inventory/index.php" class="text-decoration-none">
                <div class="stats-card">
                    <div class="stats-icon highlight">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div class="stats-title">Low Stock Items</div>
                    <div class="stats-value"><?php echo count($low_stock_items); ?></div>
                    <div class="stats-change <?php echo count($low_stock_items) > 0 ? 'negative' : 'positive'; ?>">
                        <i class="fas fa-boxes me-1"></i>
                        Inventory Alert
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Total Menu Items -->
        <div class="col-md-3 mb-4">
            <a href="/ERC-POS/views/menu/index.php" class="text-decoration-none">
                <div class="stats-card">
                    <div class="stats-icon secondary">
                        <i class="fas fa-utensils fa-lg"></i>
                    </div>
                    <div class="stats-title">Menu Items</div>
                    <div class="stats-value"><?php echo $menu_items_count; ?></div>
                    <div class="stats-change">
                        <i class="fas fa-list me-1"></i>
                        Active Products
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Low Stock Alert -->
        <div class="col-md-6">
            <a href="/ERC-POS/views/inventory/index.php" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>Low Stock Alert
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($low_stock_items) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-box me-2"></i>Item</th>
                                            <th><i class="fas fa-layer-group me-2"></i>Stock</th>
                                            <th><i class="fas fa-tag me-2"></i>Category</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($low_stock_items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <?php echo $item['current_stock']; ?> left
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-folder me-1"></i>
                                                        <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-success py-4">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h5>All items are well stocked!</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Orders -->
        <div class="col-md-6">
            <a href="/ERC-POS/views/orders/index.php" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Orders
                        </h5>
                        <span class="badge bg-white text-primary">View All</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($recent_orders): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr class="order-row" onclick="window.location.href='/ERC-POS/views/orders/view.php?id=<?php echo $order['id']; ?>'">
                                                <td class="align-middle" style="width: 40%">
                                                    <div class="d-flex align-items-center">
                                                        <div class="order-icon me-3">
                                                            <i class="fas fa-receipt fa-lg text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                                            <small class="text-muted">
                                                                <i class="far fa-clock me-1"></i>
                                                                <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle text-end" style="width: 25%">
                                                    <div class="fw-bold">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                                </td>
                                                <td class="align-middle text-end" style="width: 35%">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?> me-2">
                                                            <i class="fas fa-<?php echo $order['status'] === 'completed' ? 'check' : 'clock'; ?> me-1"></i>
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user-circle me-1"></i>
                                                            <?php echo htmlspecialchars($order['username']); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                <h5>No recent orders</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
/* Additional Dashboard Styles */
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border-left: 4px solid var(--primary-color);
    height: 100%;
    color: inherit;
    position: relative;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-left-width: 6px;
}

a:hover .stats-card {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-left-width: 6px;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    color: inherit;
}

/* Preserve text colors when using links */
a .stats-title {
    color: #6c757d;
}

a .stats-value {
    color: #333;
}

a .stats-change {
    color: #6c757d;
}

a .stats-change.positive {
    color: #28a745;
}

a .stats-change.negative {
    color: #dc3545;
}

a:hover .card-header {
    background-color: var(--warning-color);
}

/* Add subtle indicator for clickable cards */
.stats-card::after,
.card::after {
    content: '';
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 24px;
    height: 24px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

a:hover .stats-card::after,
a:hover .card::after {
    opacity: 1;
}

.stats-card::after {
    content: '\f054';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    font-size: 0.8rem;
    color: var(--primary-color);
}

.card::after {
    content: '\f054';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    font-size: 0.8rem;
    color: var(--primary-color);
}

/* Recent Orders Table Styles */
.order-row {
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.order-row:hover {
    background-color: rgba(var(--primary-rgb), 0.05) !important;
}

.order-row:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.order-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(var(--primary-rgb), 0.1);
}

.table > :not(caption) > * > * {
    padding: 1rem;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.card-header .badge {
    padding: 0.5em 1em;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

a:hover .card-header .badge {
    background-color: var(--primary-color) !important;
    color: white !important;
}

/* Make entire row clickable */
.order-row td {
    position: relative;
    z-index: 1;
}

.order-row::after {
    content: '\f054';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-color);
    opacity: 0;
    transition: all 0.3s ease;
}

.order-row:hover::after {
    opacity: 1;
    right: 0.5rem;
}
</style>

<?php include __DIR__ . '/static/templates/footer.php'; ?> 