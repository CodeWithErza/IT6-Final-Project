<?php
require_once '../../helpers/functions.php';
include '../../static/templates/header.php';

// Get all inventory items with their current stock levels
$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.name,
        m.price,
        m.image_path,
        c.name as category_name,
        COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Increase%' THEN quantity
                    WHEN transaction_type = 'adjustment' AND notes LIKE '%Decrease%' THEN -quantity
                    WHEN transaction_type = 'adjustment' THEN quantity
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

// Get history data for all items
$history_data = [];
$stmt = $conn->prepare("
    SELECT 
        it.*,
        u.username,
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
    LEFT JOIN users u ON it.created_by = u.id
    ORDER BY it.created_at DESC
");
$stmt->execute();
$all_transactions = $stmt->fetchAll();

// Group transactions by menu_item_id
foreach ($all_transactions as $transaction) {
    $menu_item_id = $transaction['menu_item_id'];
    if (!isset($history_data[$menu_item_id])) {
        $history_data[$menu_item_id] = [];
    }
    if (count($history_data[$menu_item_id]) < 100) { // Limit to 100 transactions per item
        $history_data[$menu_item_id][] = $transaction;
    }
}

// Group items by category
$items_by_category = [];
foreach ($inventory_items as $item) {
    $category = $item['category_name'] ?: 'Uncategorized';
    if (!isset($items_by_category[$category])) {
        $items_by_category[$category] = [];
    }
    $items_by_category[$category][] = $item;
}

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Default image if none is available
$default_image = 'assets/images/default-food.jpg';

// Set up assets directory
$assets_dir = $_SERVER['DOCUMENT_ROOT'] . '/ERC-POS/assets/images';

// Function to check if image exists
function image_exists($path) {
    $path = ltrim($path, '/');
    return file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path);
}
?>

<style>
    .category-filter {
        display: flex;
        justify-content: center;
        overflow-x: auto;
        padding: 15px 0;
        margin-bottom: 20px;
        gap: 10px;
    }
    .category-filter button {
        border-radius: 25px;
        padding: 10px 25px;
        font-weight: 500;
        min-width: 100px;
        border: 1px solid #dee2e6;
        background-color: white;
        color: #333;
    }
    .category-filter button.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    .item-card {
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s;
        height: 100%;
        border: 1px solid #f0f0f0;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .item-image {
        height: 180px;
        padding: 10px;
        margin: 10px;
        flex-grow: 1;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    .item-image img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
    .stock-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.8rem;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .item-card:hover .stock-badge {
        opacity: 1;
    }
    .stock-high {
        background-color: #28a745;
        color: white;
    }
    .stock-medium {
        background-color: #ffc107;
        color: black;
    }
    .stock-low {
        background-color: #dc3545;
        color: white;
    }
    .item-name {
        font-weight: bold;
        margin-bottom: 5px;
        font-size: 1.1rem;
        text-align: center;
    }
    .item-stock {
        text-align: center;
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .item-stock-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 2px;
    }
    .item-stock-number {
        font-size: 2rem;
        font-weight: 700;
        color: #0d6efd;
        line-height: 1;
    }
    .card-body {
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .top-actions {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        gap: 10px;
    }
    .top-actions a {
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 500;
        text-decoration: none;
        color: white;
    }
    .history-link {
        display: inline-block;
        padding: 5px 15px;
        background-color: #f8f9fa;
        color: #212529;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.85rem;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
    }
    .history-link:hover {
        background-color: #e9ecef;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Stock Levels</h4>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Top Action Buttons -->
    <div class="top-actions">
        <a href="/ERC-POS/views/inventory/stock_in.php" style="background-color: #28a745;">
            <i class="fas fa-arrow-circle-down"></i> Stock In
        </a>
        <a href="/ERC-POS/views/inventory/stock_out.php" style="background-color: #ffc107; color: black;">
            <i class="fas fa-arrow-circle-up"></i> Stock Out
        </a>
        <a href="/ERC-POS/views/inventory/stock_adjustment.php" style="background-color: #17a2b8;">
            <i class="fas fa-balance-scale"></i> Adjust Stock
        </a>
        <a href="/ERC-POS/views/inventory/history.php" style="background-color: #6c757d;">
            <i class="fas fa-history"></i> All Transactions
        </a>
    </div>

    <!-- Category Filter -->
    <div class="category-filter">
        <button class="active" data-category="all">All</button>
        <?php foreach (array_keys($items_by_category) as $category): ?>
            <button data-category="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
                                    </button>
                        <?php endforeach; ?>
    </div>

    <!-- Inventory Items Grid -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4 mb-4">
        <?php foreach ($inventory_items as $item): 
            // Determine stock level class
            $stock_class = 'stock-high';
            $stock_text = 'In Stock';
            
            if ($item['current_stock'] <= 5) {
                $stock_class = 'stock-low';
                $stock_text = 'Low Stock';
            } elseif ($item['current_stock'] <= 20) {
                $stock_class = 'stock-medium';
                $stock_text = 'Medium Stock';
            }
            
            // Get image URL or use default
            $image_url = '/ERC-POS/assets/images/default-food.jpg';
            
            // Check if there's an image with the same name as the menu item in assets/images
            $item_name = strtolower(trim($item['name']));
            $possible_extensions = ['.jpg', '.jpeg', '.png', '.webp', '.gif'];
            
            // First try exact match with item name
            foreach ($possible_extensions as $ext) {
                $test_path = $assets_dir . '/' . $item_name . $ext;
                if (file_exists($test_path)) {
                    $image_url = '/ERC-POS/assets/images/' . $item_name . $ext;
                    break;
                }
            }
            
            // If no exact match, try partial match
            if ($image_url == '/ERC-POS/assets/images/default-food.jpg') {
                $files = scandir($assets_dir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && 
                        (stripos($file, $item_name) !== false || 
                         stripos($item_name, pathinfo($file, PATHINFO_FILENAME)) !== false)) {
                        $image_url = '/ERC-POS/assets/images/' . $file;
                        break;
                    }
                }
            }
        ?>
            <div class="col item-container" data-category="<?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>">
                <div class="item-card">
                    <div class="item-image">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <span class="stock-badge <?php echo $stock_class; ?>"><?php echo $stock_text; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-stock">
                            <div class="item-stock-label">Stock</div>
                            <div class="item-stock-number"><?php echo number_format($item['current_stock']); ?></div>
                        </div>
                        <a href="#" class="history-link" onclick="showItemHistory(<?php echo htmlspecialchars(json_encode([
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'history' => $history_data[$item['id']] ?? []
                        ])); ?>)">
                            <i class="fas fa-history"></i> View History
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStockModalLabel">Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/ERC-POS/handlers/inventory/add_stock.php" method="POST" id="addStockForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="menu_item_id" class="form-label">Menu Item</label>
                            <select class="form-select" id="menu_item_id" name="menu_item_id" required>
                            <option value="">Select Item</option>
                            <?php foreach ($inventory_items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo $item['current_stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="unit_price" class="form-label">Unit Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">Transaction Date</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
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
                    <input type="hidden" id="adjust_menu_item_id" name="menu_item_id">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item</label>
                        <input type="text" class="form-control" id="item_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="current_stock" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="current_stock" readonly>
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
                        <textarea class="form-control" id="adjust_notes" name="notes" rows="3" required></textarea>
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

<!-- Item History Modal -->
<div class="modal fade" id="itemHistoryModal" tabindex="-1" aria-labelledby="itemHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemHistoryModalLabel">Item History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <style>
                    #itemHistoryTable th {
                        white-space: nowrap;
                        padding: 8px 12px;
                        background-color: #f8f9fa;
                        border-bottom: 2px solid #dee2e6;
                    }
                    #itemHistoryTable td {
                        padding: 8px 12px;
                        vertical-align: middle;
                    }
                    #itemHistoryTable {
                        margin-bottom: 0;
                    }
                    .table-responsive {
                        margin: -1rem;
                        padding: 1rem;
                    }
                </style>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="itemHistoryTable">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Stock After</th>
                                <th>Notes</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody id="itemHistoryTableBody">
                            <tr>
                                <td colspan="6" class="text-center">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category filter functionality
    const categoryButtons = document.querySelectorAll('.category-filter button');
    const itemContainers = document.querySelectorAll('.item-container');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide items based on category
            itemContainers.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Function to update notes based on adjustment type and stock change
function updateNotes() {
    const currentStock = parseInt(document.getElementById('current_stock').value) || 0;
    const newStock = parseInt(document.getElementById('new_stock').value) || 0;
    const adjustmentType = document.getElementById('adjustment_type').value;
    const notesField = document.getElementById('adjust_notes');
    
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

// Add event listeners for the adjust modal
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('adjustment_type').addEventListener('change', updateNotes);
    document.getElementById('new_stock').addEventListener('input', updateNotes);
    
    // Track if user has modified the notes field
    document.getElementById('adjust_notes').addEventListener('input', function() {
        this.dataset.userModified = 'true';
    });
});

function showItemHistory(itemData) {
    // Update modal title
    document.getElementById('itemHistoryModalLabel').textContent = itemData.name + ' - History';
    
    // Show the modal
    const historyModal = new bootstrap.Modal(document.getElementById('itemHistoryModal'));
    historyModal.show();
    
    const tbody = document.getElementById('itemHistoryTableBody');
    tbody.innerHTML = '';
    
    if (!itemData.history || itemData.history.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">No history found</td>
                    </tr>
                `;
        return;
    }
    
    itemData.history.forEach(transaction => {
        // Determine badge class based on transaction type
        let badgeClass = '';
        let typeLabel = '';
        switch(transaction.transaction_type) {
            case 'stock_in':
                badgeClass = 'bg-success';
                typeLabel = 'Stock In';
                break;
            case 'stock_out':
                badgeClass = 'bg-warning';
                typeLabel = 'Stock Out';
                break;
            case 'adjustment':
                badgeClass = 'bg-info';
                typeLabel = 'Adjustment';
                break;
            default:
                badgeClass = 'bg-secondary';
                typeLabel = transaction.transaction_type;
        }
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${new Date(transaction.created_at).toLocaleString()}</td>
            <td><span class="badge ${badgeClass}">${typeLabel}</span></td>
            <td>${transaction.quantity}</td>
            <td>${transaction.current_stock}</td>
            <td>${transaction.notes || '-'}</td>
            <td>${transaction.username || '-'}</td>
        `;
        tbody.appendChild(row);
    });
}
</script>

<?php include '../../static/templates/footer.php'; ?> 