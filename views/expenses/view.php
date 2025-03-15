<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get expense ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize error variable
$error = '';

if (!$id) {
    $error = 'Invalid expense ID';
} else {
    // Get expense details
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            COALESCE(u1.username, 'System') as created_by_name,
            COALESCE(u2.username, 'System') as updated_by_name
        FROM expenses e
        LEFT JOIN users u1 ON e.created_by = u1.id
        LEFT JOIN users u2 ON e.updated_by = u2.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $expense = $stmt->fetch();

    if (!$expense) {
        $error = 'Expense not found';
    }
}

// If there's an error, show it and provide a redirect script
if ($error): ?>
    <div class="container-fluid px-4">
        <div class="alert alert-danger mt-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '/ERC-POS/views/expenses/index.php';
        }, 2000); // Redirect after 2 seconds
    </script>
    <?php
    include __DIR__ . '/../../static/templates/footer.php';
    exit;
endif;

// Check if notes contain multiple items
$has_multiple_items = strpos($expense['notes'], 'ITEMS INCLUDED:') !== false;
$items_list = [];

if ($has_multiple_items) {
    // Extract the items list from the notes
    $parts = explode('ITEMS INCLUDED:', $expense['notes'], 2);
    $general_notes = trim($parts[0]);
    
    // Parse the items list
    $items_text = trim($parts[1]);
    $items_array = explode("\n", $items_text);
    
    foreach ($items_array as $item) {
        if (trim($item)) {
            $items_list[] = trim($item);
        }
    }
} else {
    $general_notes = $expense['notes'];
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Expense Details</h1>
        <div>
            <button type="button" class="btn btn-primary me-2" id="toggleEdit">
                <i class="fas fa-edit me-2"></i>Edit
            </button>
            <button type="submit" class="btn btn-success me-2" id="saveChanges" form="expenseForm" style="display: none;">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
            <a href="/ERC-POS/views/expenses/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Expenses
            </a>
        </div>
    </div>

    <form id="expenseForm" action="/ERC-POS/handlers/expenses/edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
        <!-- Hidden fields for old values -->
        <input type="hidden" name="old_description" value="<?php echo htmlspecialchars($expense['description']); ?>">
        <input type="hidden" name="old_amount" value="<?php echo $expense['amount']; ?>">
        <input type="hidden" name="old_expense_type" value="<?php echo $expense['expense_type']; ?>">
        <input type="hidden" name="old_expense_date" value="<?php echo $expense['expense_date']; ?>">
        <input type="hidden" name="old_supplier" value="<?php echo htmlspecialchars($expense['supplier'] ?? ''); ?>">
        <input type="hidden" name="old_invoice_number" value="<?php echo htmlspecialchars($expense['invoice_number'] ?? ''); ?>">
        <input type="hidden" name="old_notes" value="<?php echo htmlspecialchars($expense['notes'] ?? ''); ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    <span id="descriptionDisplay"><?php echo htmlspecialchars($expense['description']); ?></span>
                    <input type="text" class="form-control form-control-sm d-none editable-field" 
                           name="description" value="<?php echo htmlspecialchars($expense['description']); ?>" required>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Expense Type:</th>
                                <td>
                                    <span id="expenseTypeDisplay" class="badge bg-<?php 
                                        echo match($expense['expense_type']) {
                                            'ingredient' => 'primary',
                                            'utility' => 'info',
                                            'salary' => 'success',
                                            'rent' => 'warning',
                                            'equipment' => 'secondary',
                                            'maintenance' => 'dark',
                                            default => 'light text-dark'
                                        };
                                    ?>">
                                        <?php echo ucfirst($expense['expense_type']); ?>
                                    </span>
                                    <select class="form-select form-select-sm d-none editable-field" name="expense_type" required>
                                        <?php
                                        $expense_types = ['ingredient', 'utility', 'salary', 'rent', 'equipment', 'maintenance', 'other'];
                                        foreach ($expense_types as $type):
                                        ?>
                                        <option value="<?php echo $type; ?>" <?php echo $expense['expense_type'] === $type ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($type); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td>
                                    <span id="amountDisplay" class="fw-bold">₱<?php echo number_format($expense['amount'], 2); ?></span>
                                    <div class="input-group input-group-sm d-none editable-field">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" name="amount" 
                                               value="<?php echo $expense['amount']; ?>" step="0.01" min="0" required>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>
                                    <span id="dateDisplay"><?php echo date('F d, Y', strtotime($expense['expense_date'])); ?></span>
                                    <input type="date" class="form-control form-control-sm d-none editable-field" 
                                           name="expense_date" value="<?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th>Added By:</th>
                                <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Added On:</th>
                                <td><?php echo date('F d, Y h:i A', strtotime($expense['created_at'])); ?></td>
                            </tr>
                            <?php if ($expense['updated_by']): ?>
                            <tr>
                                <th>Last Updated By:</th>
                                <td><?php echo htmlspecialchars($expense['updated_by_name'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated On:</th>
                                <td><?php echo date('F d, Y h:i A', strtotime($expense['updated_at'])); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Supplier/Vendor:</th>
                                <td>
                                    <span id="supplierDisplay"><?php echo $expense['supplier'] ? htmlspecialchars($expense['supplier']) : '-'; ?></span>
                                    <input type="text" class="form-control form-control-sm d-none editable-field" 
                                           name="supplier" value="<?php echo htmlspecialchars($expense['supplier'] ?? ''); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Invoice/Receipt:</th>
                                <td>
                                    <span id="invoiceDisplay"><?php echo $expense['invoice_number'] ? htmlspecialchars($expense['invoice_number']) : '-'; ?></span>
                                    <input type="text" class="form-control form-control-sm d-none editable-field" 
                                           name="invoice_number" value="<?php echo htmlspecialchars($expense['invoice_number'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php if (!empty($general_notes)): ?>
                            <tr>
                                <th>Notes:</th>
                                <td>
                                    <span id="notesDisplay"><?php echo nl2br(htmlspecialchars($general_notes)); ?></span>
                                    <textarea class="form-control form-control-sm d-none editable-field" 
                                              name="notes" rows="3"><?php echo htmlspecialchars($expense['notes'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <?php if ($has_multiple_items && !empty($items_list)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="border-bottom pb-2">
                            Items Included
                            <button type="button" class="btn btn-sm btn-outline-primary float-end d-none editable-field" id="addItemBtn">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="45%">Item Description</th>
                                        <th width="20%">Quantity</th>
                                        <th width="20%">Price</th>
                                        <th width="10%" class="d-none editable-field">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_items_cost = 0;
                                    foreach ($items_list as $index => $item): 
                                        $price_parts = explode(' - ₱', $item);
                                        $item_with_qty = $price_parts[0];
                                        $price = isset($price_parts[1]) ? floatval($price_parts[1]) : null;
                                        
                                        $qty_match = [];
                                        $description = $item_with_qty;
                                        $quantity = '';
                                        
                                        if (preg_match('/^(.*?)\s*\((\d+\.?\d*)\s*([a-zA-Z]+)\)$/', $item_with_qty, $qty_match)) {
                                            $description = trim($qty_match[1]);
                                            $quantity = $qty_match[2] . ' ' . $qty_match[3];
                                        }
                                        
                                        if ($price !== null) {
                                            $total_items_cost += $price;
                                        }
                                    ?>
                                    <tr data-index="<?php echo $index; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="item-display"><?php echo htmlspecialchars($description); ?></span>
                                            <input type="text" class="form-control form-control-sm d-none editable-field item-description" 
                                                   value="<?php echo htmlspecialchars($description); ?>">
                                        </td>
                                        <td>
                                            <span class="item-display"><?php echo htmlspecialchars($quantity); ?></span>
                                            <div class="input-group input-group-sm d-none editable-field">
                                                <input type="number" class="form-control item-quantity" 
                                                       value="<?php echo $qty_match[2] ?? ''; ?>" step="0.01" min="0">
                                                <input type="text" class="form-control item-unit" 
                                                       value="<?php echo $qty_match[3] ?? ''; ?>" placeholder="unit">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="item-display"><?php echo $price !== null ? '₱' . number_format($price, 2) : '-'; ?></span>
                                            <div class="input-group input-group-sm d-none editable-field">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control item-price" 
                                                       value="<?php echo $price ?? ''; ?>" step="0.01" min="0">
                                            </div>
                                        </td>
                                        <td class="d-none editable-field">
                                            <button type="button" class="btn btn-sm btn-danger delete-item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary">
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th colspan="2" id="itemsTotal">₱<?php echo number_format($total_items_cost, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<style>
.editable-field {
    margin: -4px 0;
}
.form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
.input-group-sm .form-control {
    font-size: 0.875rem;
}
.item-unit {
    width: 80px !important;
    flex: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleEditBtn = document.getElementById('toggleEdit');
    const saveChangesBtn = document.getElementById('saveChanges');
    const editableFields = document.querySelectorAll('.editable-field');
    const displayElements = document.querySelectorAll('[id$="Display"], .item-display');
    const addItemBtn = document.getElementById('addItemBtn');
    const itemsTable = document.getElementById('itemsTable');
    const expenseForm = document.getElementById('expenseForm');

    // Function to format number as currency
    function formatCurrency(number) {
        return '₱' + parseFloat(number).toFixed(2);
    }

    // Function to calculate total
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-price').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('itemsTotal').textContent = formatCurrency(total);
    }

    // Function to create new item row
    function createItemRow(index) {
        const tr = document.createElement('tr');
        tr.dataset.index = index;
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <input type="text" class="form-control form-control-sm item-description" placeholder="Item description">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control item-quantity" step="0.01" min="0" placeholder="Qty">
                    <input type="text" class="form-control item-unit" placeholder="unit">
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control item-price" step="0.01" min="0" placeholder="0.00">
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger delete-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        return tr;
    }

    // Add new item
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function() {
            const tbody = itemsTable.querySelector('tbody');
            const index = tbody.children.length;
            tbody.appendChild(createItemRow(index));
            calculateTotal();
        });
    }

    // Delete item
    if (itemsTable) {
        itemsTable.addEventListener('click', function(e) {
            if (e.target.closest('.delete-item')) {
                if (confirm('Are you sure you want to delete this item?')) {
                    e.target.closest('tr').remove();
                    // Renumber rows
                    itemsTable.querySelectorAll('tbody tr').forEach((tr, index) => {
                        tr.dataset.index = index;
                        tr.children[0].textContent = index + 1;
                    });
                    calculateTotal();
                }
            }
        });

        // Listen for price changes
        itemsTable.addEventListener('input', function(e) {
            if (e.target.classList.contains('item-price')) {
                calculateTotal();
            }
        });
    }

    // Handle form submission
    expenseForm.addEventListener('submit', function(e) {
        if (itemsTable) {
            // Collect items data
            const items = [];
            itemsTable.querySelectorAll('tbody tr').forEach(tr => {
                const description = tr.querySelector('.item-description').value;
                const quantity = tr.querySelector('.item-quantity').value;
                const unit = tr.querySelector('.item-unit').value;
                const price = tr.querySelector('.item-price').value;
                
                if (description && quantity && unit && price) {
                    items.push(`${description} (${quantity} ${unit}) - ₱${price}`);
                }
            });

            // Update notes field with items
            const notesField = document.querySelector('[name="notes"]');
            let notes = notesField.value.split('ITEMS INCLUDED:')[0].trim();
            if (items.length > 0) {
                notes += '\n\nITEMS INCLUDED:\n' + items.join('\n');
            }
            notesField.value = notes;
        }
    });

    toggleEditBtn.addEventListener('click', function() {
        // Toggle visibility of edit/save buttons
        toggleEditBtn.style.display = 'none';
        saveChangesBtn.style.display = 'inline-block';

        // Show editable fields, hide display elements
        editableFields.forEach(field => {
            field.classList.remove('d-none');
        });
        displayElements.forEach(element => {
            element.style.display = 'none';
        });
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 