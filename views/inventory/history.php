    <?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Add custom CSS for table headers
echo '<style>
    #historyTable th {
        white-space: nowrap;
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    #historyTable td {
        vertical-align: middle;
    }
</style>';

// Get filter values
$menu_item_id = $_GET['menu_item_id'] ?? '';
$transaction_type = $_GET['transaction_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with previous stock calculation
$query = "
    SELECT 
        it.*,
        m.name as menu_item_name,
        m.is_active,
        u.username as user_name,
        (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Increase%' THEN quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Decrease%' THEN -quantity
                    WHEN transaction_type = 'adjustment' THEN quantity
                END
            ), 0)
            FROM inventory_transactions
            WHERE menu_item_id = it.menu_item_id
            AND created_at < it.created_at
        ) as previous_stock,
        (
            SELECT COALESCE(SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Increase%' THEN quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Decrease%' THEN -quantity
                    WHEN transaction_type = 'adjustment' THEN quantity
                END
            ), 0)
            FROM inventory_transactions
            WHERE menu_item_id = it.menu_item_id
            AND created_at <= it.created_at
        ) as current_stock
    FROM inventory_transactions it
    JOIN menu_items m ON it.menu_item_id = m.id
    LEFT JOIN users u ON it.created_by = u.id
    WHERE 1=1
";

$params = [];

if ($menu_item_id) {
    $query .= " AND it.menu_item_id = ?";
    $params[] = $menu_item_id;
}

if ($transaction_type) {
    $query .= " AND it.transaction_type = ?";
    $params[] = $transaction_type;
}

if ($date_from) {
    $query .= " AND DATE(it.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(it.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY it.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get all menu items for filter
$menu_items_query = "
    SELECT id, name 
    FROM menu_items 
    WHERE is_inventory_item = 1 
    ORDER BY name
";
$menu_items = $conn->query($menu_items_query)->fetchAll();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">
                <i class="fas fa-history me-2"></i>
                Stock Management History
            </h1>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="menu_item_id" class="form-label">Menu Item</label>
                    <select class="form-select" id="menu_item_id" name="menu_item_id">
                        <option value="">All Items</option>
                        <?php foreach ($menu_items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" 
                                    <?php echo $menu_item_id == $item['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="transaction_type" class="form-label">Transaction Type</label>
                    <select class="form-select" id="transaction_type" name="transaction_type">
                        <option value="">All Types</option>
                        <option value="adjustment" <?php echo $transaction_type === 'adjustment' ? 'selected' : ''; ?>>Stock Adjustment</option>
                        <option value="stock_in" <?php echo $transaction_type === 'stock_in' ? 'selected' : ''; ?>>Stock In</option>
                        <option value="stock_out" <?php echo $transaction_type === 'stock_out' ? 'selected' : ''; ?>>Stock Out</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="/ERC-POS/views/inventory/history.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="historyTable">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Item</th>
                            <th class="text-nowrap">Type</th>
                            <th class="text-nowrap">Prev. Stock</th>
                            <th class="text-nowrap">Qty</th>
                            <th class="text-nowrap">Curr. Stock</th>
                            <th class="text-nowrap">Notes</th>
                            <th class="text-nowrap">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php 
                            // Determine if this was an increase or decrease
                            $adjustment_type = '';
                            if (strpos(strtolower($transaction['notes']), 'increase') !== false) {
                                $adjustment_type = 'Increase';
                                $badge_class = 'bg-success';
                            } elseif (strpos(strtolower($transaction['notes']), 'decrease') !== false) {
                                $adjustment_type = 'Decrease';
                                $badge_class = 'bg-danger';
                            } elseif (strpos($transaction['notes'], 'Initial Stock') !== false) {
                                $adjustment_type = 'Initial Stock';
                                $badge_class = 'bg-primary';
                            } else {
                                $adjustment_type = 'Adjustment';
                                $badge_class = 'bg-info';
                            }
                            ?>
                            <tr>
                                <td><?php echo date('Y-m-d g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['menu_item_name']); ?>
                                    <?php if (!$transaction['is_active']): ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($transaction['transaction_type']) {
                                        'adjustment' => 'bg-info',
                                        'stock_in' => 'bg-success',
                                        'stock_out' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    $type_label = match($transaction['transaction_type']) {
                                        'adjustment' => 'Stock Adjustment',
                                        'stock_in' => 'Stock In',
                                        'stock_out' => 'Stock Out',
                                        default => 'Unknown'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $type_label; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($transaction['previous_stock']); ?></td>
                                <td><?php echo number_format($transaction['quantity']); ?></td>
                                <td><?php echo number_format($transaction['current_stock']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-muted mb-0">No transactions found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#historyTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            { width: '100px', targets: 0 }, // Date
            { width: '120px', targets: 1 }, // Item
            { width: '80px', targets: 2 },  // Type
            { width: '60px', targets: 3 },  // Prev. Stock
            { width: '50px', targets: 4 },  // Qty
            { width: '60px', targets: 5 },  // Curr. Stock
            { width: '200px', targets: 6 }, // Notes
            { width: '80px', targets: 7 }   // By
        ],
        autoWidth: false,
        scrollX: true
    });
    
    // Auto-submit form when filters change
    document.querySelectorAll('#menu_item_id, #transaction_type').forEach(function(element) {
        element.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 