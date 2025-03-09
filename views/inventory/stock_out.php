<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$menu_item_id = $_GET['menu_item_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT 
        it.*,
        m.name as menu_item_name,
        m.is_active,
        u.username as user_name,
        o.order_number
    FROM inventory_transactions it
    JOIN menu_items m ON it.menu_item_id = m.id
    LEFT JOIN users u ON it.created_by = u.id
    LEFT JOIN orders o ON it.notes LIKE CONCAT('%Order: ', o.order_number, '%')
        OR it.notes LIKE CONCAT('%Order #', o.id, '%')
    WHERE it.transaction_type = 'stock_out'
";

$params = [];

if ($menu_item_id) {
    $query .= " AND it.menu_item_id = ?";
    $params[] = $menu_item_id;
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

// Calculate totals
$total_quantity = 0;

foreach ($transactions as $transaction) {
    $total_quantity += $transaction['quantity'];
}

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">Stock Out Transactions</h1>
            <p class="text-muted">View all stock removals and sales</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Transactions</h5>
                    <h2 class="mb-0"><?php echo count($transactions); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Items Removed</h5>
                    <h2 class="mb-0"><?php echo number_format($total_quantity); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
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
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="/ERC-POS/views/inventory/stock_out.php" class="btn btn-secondary">
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
                <table class="table table-hover align-middle" id="stockOutTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date & Time</th>
                            <th>Menu Item</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): 
                            // Extract order number if present
                            $orderNumber = '-';
                            $reason = 'Stock Removal';
                            
                            // First check if we have an order_number from the join
                            if (!empty($transaction['order_number'])) {
                                $orderNumber = $transaction['order_number'];
                                $reason = 'Sale';
                            }
                            // Check for "Order #" format
                            else if (preg_match('/Order #(\d+)/', $transaction['notes'], $matches)) {
                                $orderNumber = $matches[1];
                                $reason = 'Sale';
                            } 
                            // Check for "Order: YYYYMMDD-NNNN" format
                            else if (preg_match('/Order: (\d{8}-\d{4})/', $transaction['notes'], $matches)) {
                                $orderNumber = $matches[1];
                                $reason = 'Sale';
                            }
                            // Check for just the order number format without prefix
                            else if (preg_match('/(\d{8}-\d{4})/', $transaction['notes'], $matches)) {
                                $orderNumber = $matches[1];
                                $reason = 'Sale';
                            }
                            // If no order number but notes exist
                            else if (!empty($transaction['notes'])) {
                                // Remove order information from the reason
                                $cleanedNotes = $transaction['notes'];
                                $cleanedNotes = preg_replace('/Order: \d{8}-\d{4}/', '', $cleanedNotes);
                                $cleanedNotes = preg_replace('/Order #\d+/', '', $cleanedNotes);
                                $cleanedNotes = preg_replace('/\d{8}-\d{4}/', '', $cleanedNotes);
                                
                                // Remove "Stock Adjustment" and related text
                                $cleanedNotes = preg_replace('/\(Stock Adjustment\)/', '', $cleanedNotes);
                                $cleanedNotes = preg_replace('/Stock Adjustment/', '', $cleanedNotes);
                                $cleanedNotes = trim($cleanedNotes);
                                
                                if (!empty($cleanedNotes)) {
                                    $reason = $cleanedNotes;
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo $orderNumber; ?></td>
                                <td><?php echo date('Y-m-d g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['menu_item_name']); ?>
                                    <?php if (!$transaction['is_active']): ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($transaction['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($reason); ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">No stock out transactions found.</p>
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
    $('#stockOutTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
    
    // Auto-submit form when filters change
    document.querySelectorAll('#menu_item_id').forEach(function(element) {
        element.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 