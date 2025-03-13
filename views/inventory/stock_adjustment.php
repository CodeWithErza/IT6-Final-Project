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
    WHERE it.transaction_type = 'adjustment'
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

// Get all menu items for filter with accurate current stock
$menu_items_query = "
    SELECT 
        mi.id, 
        mi.name,
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
            WHERE menu_item_id = mi.id
        ) as current_stock
    FROM menu_items mi
    WHERE mi.is_inventory_item = 1 
    ORDER BY mi.name
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
                <i class="fas fa-balance-scale me-2"></i>
                Stock Adjustments
            </h1>
            <p class="text-muted">Manage inventory adjustments and corrections</p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                <i class="fas fa-balance-scale me-2"></i>New Adjustment
            </button>
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
                    <a href="/ERC-POS/views/inventory/stock_adjustment.php" class="btn btn-secondary">
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
                <table class="table table-hover align-middle" id="adjustmentsTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Menu Item</th>
                            <th>Quantity</th>
                            <th>Adjustment Type</th>
                            <th>Reason</th>
                            <th>Adjusted By</th>
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
                                <td><?php echo number_format($transaction['quantity']); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $adjustment_type; ?></span></td>
                                <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">No stock adjustments found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustStockModalLabel">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/ERC-POS/handlers/inventory/adjust_stock.php" method="POST" id="adjustStockForm">
                    <div class="mb-3">
                        <label for="adjust_menu_item_id" class="form-label">Menu Item</label>
                        <select class="form-select" id="adjust_menu_item_id" name="menu_item_id" required onchange="updateCurrentStock(this)">
                            <option value="">Select Item</option>
                            <?php foreach ($menu_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" data-current="<?php echo $item['current_stock']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Current stock values for each item -->
                    <?php foreach ($menu_items as $item): ?>
                    <input type="hidden" id="stock_<?php echo $item['id']; ?>" value="<?php echo $item['current_stock']; ?>">
                    <?php endforeach; ?>
                    
                    <div class="mb-3">
                        <label for="current_stock" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="current_stock" value="0" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_stock" class="form-label">New Stock Level</label>
                        <input type="number" class="form-control" id="new_stock" name="new_stock" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_type" class="form-label">Adjustment Type</label>
                        <select class="form-select" id="adjustment_type" name="adjustment_type">
                            <option value="physical_count">Physical Count</option>
                            <option value="correction">Correction</option>
                            <option value="damage">Damaged/Expired</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" required></textarea>
                        <small class="form-text text-muted">Please provide a reason for this adjustment</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="adjustStockForm" class="btn btn-primary">Save Adjustment</button>
            </div>
        </div>
    </div>
</div>

<script>
// Direct function to update current stock
function updateCurrentStock(selectElement) {
    const selectedId = selectElement.value;
    if (!selectedId) return;
    
    // Try to get the current stock from the hidden input
    const stockInput = document.getElementById('stock_' + selectedId);
    if (stockInput) {
        const currentStock = stockInput.value;
        console.log('Direct update from hidden input - Current stock:', currentStock);
        
        document.getElementById('current_stock').value = currentStock;
        document.getElementById('new_stock').value = currentStock;
    } else {
        // Fallback to the data attribute
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const currentStock = selectedOption.dataset.current || 0;
        console.log('Direct update from data attribute - Current stock:', currentStock);
        
        document.getElementById('current_stock').value = currentStock;
        document.getElementById('new_stock').value = currentStock;
    }
}

// Create a mapping of item IDs to current stock values
const itemStocks = {
    <?php foreach ($menu_items as $item): ?>
    <?php echo $item['id']; ?>: <?php echo $item['current_stock']; ?>,
    <?php endforeach; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#adjustmentsTable').DataTable({
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
    
    // Update current stock when menu item is selected in adjustment modal
    document.getElementById('adjust_menu_item_id').addEventListener('change', function() {
        console.log('Menu item changed');
        const selectedId = this.value;
        const currentStock = itemStocks[selectedId] || 0;
        console.log('Selected ID:', selectedId);
        console.log('Current stock from mapping:', currentStock);
        
        // Also try to get it from the data attribute
        const selectedOption = this.options[this.selectedIndex];
        const dataCurrentStock = selectedOption.dataset.current || 0;
        console.log('Current stock from data attribute:', dataCurrentStock);
        
        // Set the value using both approaches
        document.getElementById('current_stock').value = currentStock;
        document.getElementById('new_stock').value = currentStock;
    });
    
    // Initialize current stock when modal is opened
    $('#adjustStockModal').on('shown.bs.modal', function () {
        console.log('Modal shown');
        const selectElement = document.getElementById('adjust_menu_item_id');
        if (selectElement.options.length > 1) {
            // Select the first item by default if none is selected
            if (selectElement.selectedIndex === 0) {
                selectElement.selectedIndex = 1;
            }
            // Trigger the change event to update the current stock
            selectElement.dispatchEvent(new Event('change'));
        }
    });
    
    // Also initialize when the document is loaded
    const selectElement = document.getElementById('adjust_menu_item_id');
    if (selectElement && selectElement.options.length > 1) {
        // Select the first item by default if none is selected
        if (selectElement.selectedIndex === 0) {
            selectElement.selectedIndex = 1;
        }
        // Trigger the change event to update the current stock
        selectElement.dispatchEvent(new Event('change'));
    }
    
    // Update notes based on adjustment type and stock change
    document.getElementById('adjustment_type').addEventListener('change', updateNotes);
    document.getElementById('new_stock').addEventListener('input', updateNotes);
    
    function updateNotes() {
        const currentStock = parseInt(document.getElementById('current_stock').value) || 0;
        const newStock = parseInt(document.getElementById('new_stock').value) || 0;
        const adjustmentType = document.getElementById('adjustment_type').value;
        const notesField = document.getElementById('notes');
        
        let adjustmentDirection = '';
        if (newStock > currentStock) {
            adjustmentDirection = 'Increase';
        } else if (newStock < currentStock) {
            adjustmentDirection = 'Decrease';
        } else {
            adjustmentDirection = 'No change';
        }
        
        const difference = Math.abs(newStock - currentStock);
        
        let noteText = '';
        switch(adjustmentType) {
            case 'physical_count':
                noteText = `${adjustmentDirection} by ${difference} after physical count`;
                break;
            case 'correction':
                noteText = `${adjustmentDirection} by ${difference} to correct inventory error`;
                break;
            case 'damage':
                noteText = `${adjustmentDirection} by ${difference} due to damaged/expired items`;
                break;
            case 'other':
                noteText = `${adjustmentDirection} by ${difference} - `;
                break;
        }
        
        // Only set the value if the user hasn't modified it yet
        if (!notesField.dataset.userModified) {
            notesField.value = noteText;
        }
    }
    
    // Track if user has modified the notes field
    document.getElementById('notes').addEventListener('input', function() {
        this.dataset.userModified = 'true';
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 