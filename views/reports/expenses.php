<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$expense_type = isset($_GET['expense_type']) ? $_GET['expense_type'] : '';
$expense_source = isset($_GET['expense_source']) ? $_GET['expense_source'] : 'all';

// Initialize totals
$total_expenses = 0;
$total_items = 0;

// Get inventory transactions (if selected)
$inventory_expenses = [];
if ($expense_source == 'all' || $expense_source == 'inventory') {
    $query = "
        SELECT 
            it.*,
            mi.name as item_name,
            mi.price as unit_price,
            u.username as created_by,
            'inventory' as source
        FROM inventory_transactions it
        LEFT JOIN menu_items mi ON it.menu_item_id = mi.id
        LEFT JOIN users u ON it.created_by = u.id
        WHERE it.transaction_type IN ('stock_in', 'adjustment')
        AND DATE(it.created_at) BETWEEN :start_date AND :end_date
    ";

    if ($expense_type && $expense_type != 'all') {
        $query .= " AND it.transaction_type = :expense_type";
    }

    $query .= " ORDER BY it.created_at DESC";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    if ($expense_type && $expense_type != 'all') {
        $stmt->bindParam(':expense_type', $expense_type);
    }
    $stmt->execute();
    $inventory_expenses = $stmt->fetchAll();

    // Calculate inventory totals
    foreach ($inventory_expenses as $expense) {
        $item_cost = $expense['quantity'] * ($expense['unit_price'] ?? 0);
        $total_expenses += $item_cost;
        $total_items += $expense['quantity'];
    }
}

// Get general expenses (if selected)
$general_expenses = [];
if ($expense_source == 'all' || $expense_source == 'general') {
    $query = "
        SELECT 
            e.*,
            u.username as created_by,
            'general' as source
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE DATE(e.expense_date) BETWEEN :start_date AND :end_date
    ";

    if ($expense_type && $expense_type != 'all' && $expense_type != 'stock_in' && $expense_type != 'adjustment') {
        $query .= " AND e.expense_type = :expense_type";
    }

    $query .= " ORDER BY e.expense_date DESC, e.id DESC";

    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    if ($expense_type && $expense_type != 'all' && $expense_type != 'stock_in' && $expense_type != 'adjustment') {
        $stmt->bindParam(':expense_type', $expense_type);
    }
    $stmt->execute();
    $general_expenses = $stmt->fetchAll();

    // Add general expenses to total
    foreach ($general_expenses as $expense) {
        $total_expenses += $expense['amount'];
    }
}

// Combine all expenses for display
$all_expenses = [];

// Format inventory expenses for display
foreach ($inventory_expenses as $expense) {
    $item_cost = $expense['quantity'] * ($expense['unit_price'] ?? 0);
    $all_expenses[] = [
        'date' => $expense['created_at'],
        'description' => $expense['item_name'],
        'type' => $expense['transaction_type'],
        'amount' => $item_cost,
        'supplier' => $expense['supplier'] ?? '',
        'invoice' => $expense['invoice_number'] ?? '',
        'notes' => $expense['notes'],
        'created_by' => $expense['created_by'],
        'source' => 'inventory',
        'details' => [
            'quantity' => $expense['quantity'],
            'unit_price' => $expense['unit_price'] ?? 0
        ]
    ];
}

// Format general expenses for display
foreach ($general_expenses as $expense) {
    $all_expenses[] = [
        'date' => $expense['expense_date'] . ' ' . date('H:i:s', strtotime($expense['created_at'])),
        'description' => $expense['description'],
        'type' => $expense['expense_type'],
        'amount' => $expense['amount'],
        'supplier' => $expense['supplier'] ?? '',
        'invoice' => $expense['invoice_number'] ?? '',
        'notes' => $expense['notes'],
        'created_by' => $expense['created_by'],
        'source' => 'general',
        'details' => [
            'has_items' => strpos($expense['notes'], 'ITEMS INCLUDED:') !== false
        ]
    ];
}

// Sort all expenses by date (newest first)
usort($all_expenses, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>

<div class="container-fluid py-4">
    <h1 class="h2">
        <i class="fas fa-file-invoice-dollar me-2"></i>
        Comprehensive Expenses Report
    </h1>
    <p class="text-muted">Track all expenses including inventory purchases, ingredients, utilities, and other operational costs</p>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expense Type</label>
                    <select class="form-select" name="expense_type">
                        <option value="all">All Types</option>
                        <optgroup label="Inventory Transactions">
                            <option value="stock_in" <?php echo $expense_type === 'stock_in' ? 'selected' : ''; ?>>Stock In</option>
                            <option value="adjustment" <?php echo $expense_type === 'adjustment' ? 'selected' : ''; ?>>Stock Adjustment</option>
                        </optgroup>
                        <optgroup label="General Expenses">
                            <option value="ingredient" <?php echo $expense_type === 'ingredient' ? 'selected' : ''; ?>>Ingredient</option>
                            <option value="utility" <?php echo $expense_type === 'utility' ? 'selected' : ''; ?>>Utility</option>
                            <option value="salary" <?php echo $expense_type === 'salary' ? 'selected' : ''; ?>>Salary</option>
                            <option value="rent" <?php echo $expense_type === 'rent' ? 'selected' : ''; ?>>Rent</option>
                            <option value="equipment" <?php echo $expense_type === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                            <option value="maintenance" <?php echo $expense_type === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="other" <?php echo $expense_type === 'other' ? 'selected' : ''; ?>>Other</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expense Source</label>
                    <select class="form-select" name="expense_source">
                        <option value="all" <?php echo $expense_source === 'all' ? 'selected' : ''; ?>>All Sources</option>
                        <option value="inventory" <?php echo $expense_source === 'inventory' ? 'selected' : ''; ?>>Inventory Only</option>
                        <option value="general" <?php echo $expense_source === 'general' ? 'selected' : ''; ?>>General Expenses Only</option>
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
        <div class="col-xl-4 col-md-6">
            <div class="card bg-danger text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo number_format($total_expenses, 2); ?></h4>
                    <div class="small">Total Expenses</div>
                </div>
            </div>
        </div>
        <?php if ($expense_source == 'all' || $expense_source == 'inventory'): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-warning text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0"><?php echo $total_items; ?></h4>
                    <div class="small">Total Inventory Items</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-info text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0"><?php echo count($all_expenses); ?></h4>
                    <div class="small">Total Expense Transactions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Expenses Details
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Supplier</th>
                            <th>Invoice</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_expenses as $expense): ?>
                            <tr class="<?php echo $expense['source'] === 'inventory' ? 'table-light' : ''; ?>">
                                <td><?php echo date('M d, Y h:i A', strtotime($expense['date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($expense['description']); ?>
                                    <?php if ($expense['source'] === 'inventory'): ?>
                                        <small class="d-block text-muted">
                                            Qty: <?php echo number_format($expense['details']['quantity']); ?> × 
                                            ₱<?php echo number_format($expense['details']['unit_price'], 2); ?>
                                        </small>
                                    <?php elseif ($expense['details']['has_items']): ?>
                                        <small class="d-block text-muted">
                                            <i class="fas fa-list-ul"></i> Multiple items
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($expense['type']) {
                                            'stock_in' => 'success',
                                            'adjustment' => 'secondary',
                                            'ingredient' => 'primary',
                                            'utility' => 'info',
                                            'salary' => 'success',
                                            'rent' => 'warning',
                                            'equipment' => 'secondary',
                                            'maintenance' => 'dark',
                                            default => 'light text-dark'
                                        };
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $expense['type'])); ?>
                                    </span>
                                    <small class="d-block text-muted mt-1">
                                        <?php echo $expense['source'] === 'inventory' ? 'Inventory' : 'General'; ?>
                                    </small>
                                </td>
                                <td class="text-end">₱<?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($expense['supplier'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($expense['invoice'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($expense['created_by']); ?></td>
                                <td>
                                    <?php if ($expense['source'] === 'general'): ?>
                                        <a href="/ERC-POS/views/expenses/view.php?id=<?php echo $expense['id'] ?? ''; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary" 
                                                data-bs-toggle="popover" 
                                                data-bs-trigger="focus" 
                                                title="Notes" 
                                                data-bs-content="<?php echo htmlspecialchars($expense['notes'] ?: 'No notes available'); ?>">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    <?php endif; ?>
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
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 