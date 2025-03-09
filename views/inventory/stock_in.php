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
        u.username as user_name
    FROM inventory_transactions it
    JOIN menu_items m ON it.menu_item_id = m.id
    LEFT JOIN users u ON it.created_by = u.id
    WHERE it.transaction_type = 'stock_in'
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
$total_cost = 0;

foreach ($transactions as $transaction) {
    $total_quantity += $transaction['quantity'];
    $total_cost += $transaction['quantity'] * $transaction['unit_price'];
}

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">Stock In Transactions</h1>
            <p class="text-muted">View and manage all stock purchases and additions</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                <i class="fas fa-plus"></i> Add New Stock
            </button>
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
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Transactions</h5>
                    <h2 class="mb-0"><?php echo count($transactions); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Items Added</h5>
                    <h2 class="mb-0"><?php echo number_format($total_quantity); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Cost</h5>
                    <h2 class="mb-0">₱<?php echo number_format($total_cost, 2); ?></h2>
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
                    <a href="/ERC-POS/views/inventory/stock_in.php" class="btn btn-secondary">
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
                <table class="table table-hover align-middle" id="stockInTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Menu Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Cost</th>
                            <th>Supplier</th>
                            <th>Invoice #</th>
                            <th>Added By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): 
                            // Process supplier and invoice information
                            $supplier = $transaction['supplier'] ?? '-';
                            $invoiceNumber = $transaction['invoice_number'] ?? '-';
                            $notes = $transaction['notes'] ?? '';
                            
                            // Extract supplier from notes if not in the supplier field
                            if ($supplier === '-' && strpos($notes, 'Supplier:') !== false) {
                                preg_match('/Supplier: ([^|]+)/', $notes, $matches);
                                if (isset($matches[1])) {
                                    $supplier = trim($matches[1]);
                                    // Remove supplier info from notes
                                    $notes = str_replace('Supplier: ' . $matches[1], '', $notes);
                                    $notes = trim($notes, " |");
                                }
                            }
                            
                            // Extract invoice number from notes if not in the invoice_number field
                            if ($invoiceNumber === '-' && strpos($notes, 'OR/Invoice #:') !== false) {
                                preg_match('/OR\/Invoice #: ([^|]+)/', $notes, $matches);
                                if (isset($matches[1])) {
                                    $invoiceNumber = trim($matches[1]);
                                    // Remove invoice info from notes
                                    $notes = str_replace('OR/Invoice #: ' . $matches[1], '', $notes);
                                    $notes = trim($notes, " |");
                                }
                            }
                            
                            // Clean up notes
                            $notes = trim($notes);
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['menu_item_name']); ?>
                                    <?php if (!$transaction['is_active']): ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($transaction['quantity']); ?></td>
                                <td>₱<?php echo number_format($transaction['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($transaction['quantity'] * $transaction['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($supplier); ?></td>
                                <td><?php echo htmlspecialchars($invoiceNumber); ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($notes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <p class="text-muted mb-0">No stock in transactions found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel">Add New Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/ERC-POS/handlers/inventory/add_stock.php" method="POST" id="addStockForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="menu_item_id" class="form-label">Menu Item</label>
                            <select class="form-select" id="menu_item_id" name="menu_item_id" required>
                                <option value="">Select Item</option>
                                <?php foreach ($menu_items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">Transaction Date</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="unit_price" class="form-label">Unit Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="supplier" name="supplier">
                        </div>
                        <div class="col-md-6">
                            <label for="invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addStockForm" class="btn btn-primary">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#stockInTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
    
    // Auto-submit form when filters change
    document.querySelectorAll('#menu_item_id').forEach(function(element) {
        if (!element.closest('.modal')) {
            element.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 