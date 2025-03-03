<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$expense_type = isset($_GET['expense_type']) ? $_GET['expense_type'] : '';

// Prepare the base query
$query = "
    SELECT 
        it.*,
        mi.name as item_name,
        mi.price as unit_price,
        u.username as created_by
    FROM inventory_transactions it
    LEFT JOIN menu_items mi ON it.menu_item_id = mi.id
    LEFT JOIN users u ON it.created_by = u.id
    WHERE it.transaction_type IN ('stock_in', 'initial')
    AND DATE(it.created_at) BETWEEN :start_date AND :end_date
";

if ($expense_type) {
    $query .= " AND it.transaction_type = :expense_type";
}

$query .= " ORDER BY it.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
if ($expense_type) {
    $stmt->bindParam(':expense_type', $expense_type);
}
$stmt->execute();
$expenses = $stmt->fetchAll();

// Calculate totals
$total_expenses = 0;
$total_items = 0;
foreach ($expenses as $expense) {
    $total_expenses += $expense['quantity'] * $expense['unit_price'];
    $total_items += $expense['quantity'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Expenses Report</h1>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expense Type</label>
                    <select class="form-select" name="expense_type">
                        <option value="">All Types</option>
                        <option value="initial" <?php echo $expense_type === 'initial' ? 'selected' : ''; ?>>Initial Stock</option>
                        <option value="stock_in" <?php echo $expense_type === 'stock_in' ? 'selected' : ''; ?>>Stock In</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="expenses.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo number_format($total_expenses, 2); ?></h4>
                    <div class="small">Total Expenses</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0"><?php echo $total_items; ?></h4>
                    <div class="small">Total Items</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo $total_items > 0 ? number_format($total_expenses / $total_items, 2) : '0.00'; ?></h4>
                    <div class="small">Average Cost per Item</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Expenses Details
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Cost</th>
                            <th>Added By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($expense['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($expense['item_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $expense['transaction_type'] === 'initial' ? 'bg-primary' : 'bg-success'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $expense['transaction_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($expense['quantity']); ?></td>
                                <td>₱<?php echo number_format($expense['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($expense['quantity'] * $expense['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($expense['created_by']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($expense['notes'] ?: '-'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#expensesTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 