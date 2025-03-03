<?php
require_once '../../helpers/functions.php';
include '../../static/templates/header.php';

// Get all inventory items with their current stock levels
$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.name,
        m.price,
        c.name as category_name,
        COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' OR transaction_type = 'adjustment' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                END
            )
            FROM inventory_transactions 
            WHERE menu_item_id = m.id
            ), 0
        ) as current_stock,
        (
            SELECT COUNT(*) 
            FROM inventory_transactions 
            WHERE menu_item_id = m.id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as transaction_count
    FROM menu_items m 
    LEFT JOIN categories c ON m.category_id = c.id 
    WHERE m.is_inventory_item = 1 
    ORDER BY m.name
");
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Inventory Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
            <i class="fas fa-plus"></i> Add Stock
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Price</th>
                            <th>Last 30 Days Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['current_stock'] <= 10 ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $item['current_stock']; ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <?php echo $item['transaction_count']; ?> transactions
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewHistory(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="addStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="adjustStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['current_stock']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm" action="../../handlers/inventory/add_stock.php" method="POST">
                    <input type="hidden" name="menu_item_id" id="addStockItemId">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <select class="form-select" name="menu_item_id" id="itemSelect" required>
                            <option value="">Select Item</option>
                            <?php foreach ($inventory_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo $item['current_stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" id="quantity" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit Price</label>
                                <input type="number" class="form-control" name="unit_price" id="unitPrice" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" id="totalAmount" readonly>
                        <input type="hidden" name="total_amount" id="hiddenTotalAmount">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d H:i'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">OR/Invoice #</label>
                                <input type="text" class="form-control" name="invoice_number" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" name="supplier" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional"></textarea>
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustStockForm" action="../../handlers/inventory/adjust_stock.php" method="POST">
                    <input type="hidden" name="menu_item_id" id="adjustStockItemId">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="adjustStockItemName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="currentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Stock Level</label>
                        <input type="number" class="form-control" name="new_stock" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Adjustment</label>
                        <textarea class="form-control" name="notes" rows="2" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="adjustStockForm" class="btn btn-primary">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Stock History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="historyTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Details</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- History data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#inventoryTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25
    });

    // Calculate total amount when quantity or unit price changes
    $('#quantity, #unitPrice').on('input', calculateTotal);
    
    // Set initial unit price when item is selected
    $('#itemSelect').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const price = selectedOption.data('price');
        $('#unitPrice').val(price);
        calculateTotal();
    });

    // Reset form when modal is closed
    $('#addStockModal').on('hidden.bs.modal', function() {
        $('#addStockForm')[0].reset();
        $('#totalAmount').val('');
        $('#hiddenTotalAmount').val('');
    });

    // Set the selected item when opening modal
    $('#addStockModal').on('show.bs.modal', function(e) {
        const itemId = $(e.relatedTarget).data('id');
        if (itemId) {
            $('#itemSelect').val(itemId).trigger('change');
        }
    });
});

function calculateTotal() {
    const quantity = parseFloat($('#quantity').val()) || 0;
    const unitPrice = parseFloat($('#unitPrice').val()) || 0;
    const total = quantity * unitPrice;
    $('#totalAmount').val('₱' + total.toFixed(2));
    $('#hiddenTotalAmount').val(total.toFixed(2));
}

// Function to view stock history
function viewHistory(itemId) {
    // Clear previous history
    $('#historyTableBody').empty();
    
    // Show loading indicator
    $('#historyTableBody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
    
    // Show the modal
    $('#historyModal').modal('show');
    
    // Fetch history data
    fetch(`../../handlers/inventory/get_history.php?menu_item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            $('#historyTableBody').empty();
            
            data.forEach(record => {
                const unitPrice = record.unit_price ? parseFloat(record.unit_price) : 0;
                const quantity = parseInt(record.quantity);
                const total = unitPrice * quantity;
                
                const row = `
                    <tr>
                        <td>${new Date(record.created_at).toLocaleString()}</td>
                        <td>
                            <span class="badge ${record.transaction_type === 'stock_in' ? 'bg-success' : record.transaction_type === 'adjustment' ? 'bg-warning' : 'bg-danger'}">
                                ${record.transaction_type === 'stock_in' ? 'Stock In' : record.transaction_type === 'stock_out' ? 'Stock Out' : 'Adjustment'}
                            </span>
                        </td>
                        <td>${record.quantity}</td>
                        <td>${record.unit_price ? '₱' + parseFloat(record.unit_price).toFixed(2) : '-'}</td>
                        <td>${record.unit_price ? '₱' + total.toFixed(2) : '-'}</td>
                        <td>${record.notes || '-'}</td>
                        <td>${record.username}</td>
                    </tr>
                `;
                $('#historyTableBody').append(row);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            $('#historyTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading history</td></tr>');
        });
}

// Function to add stock
function addStock(itemId, itemName) {
    $('#itemSelect').val(itemId).trigger('change');
    $('#addStockModal').modal('show');
}

// Function to adjust stock
function adjustStock(itemId, itemName, currentStock) {
    $('#adjustStockItemId').val(itemId);
    $('#adjustStockItemName').val(itemName);
    $('#currentStock').val(currentStock);
    $('#adjustStockModal').modal('show');
}
</script>

<style>
.badge {
    font-size: 0.9rem;
    padding: 0.4rem 0.6rem;
}

.table th {
    white-space: nowrap;
}

.btn-group-sm > .btn,
.btn-sm {
    padding: 0.25rem 0.5rem;
    margin: 0 0.1rem;
}
</style>

<?php include '../../static/templates/footer.php'; ?> 