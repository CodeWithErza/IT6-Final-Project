<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get all expenses with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter values
$expense_type = $_GET['expense_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT 
        e.*,
        u.username as created_by_name
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.expense_date BETWEEN :date_from AND :date_to
";

$params = [
    ':date_from' => $date_from,
    ':date_to' => $date_to
];

if (!empty($expense_type)) {
    $query .= " AND e.expense_type = :expense_type";
    $params[':expense_type'] = $expense_type;
}

if (!empty($search)) {
    $query .= " AND (e.description LIKE :search OR e.supplier LIKE :search OR e.invoice_number LIKE :search)";
    $params[':search'] = "%$search%";
}

// Count total records for pagination
$count_query = str_replace("SELECT \n        e.*,\n        u.username as created_by_name", "SELECT COUNT(*)", $query);
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Add order by and limit
$query .= " ORDER BY e.expense_date DESC, e.id DESC LIMIT :offset, :limit";
$params[':offset'] = $offset;
$params[':limit'] = $limit;

// Execute query
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    if ($key == ':offset' || $key == ':limit') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$expenses = $stmt->fetchAll();

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <h1 class="h2">
        <i class="fas fa-file-invoice-dollar me-2"></i>
        Expenses Management
    </h1>
    <p class="text-muted">Manage all expenses including ingredients (like eggs, spices, etc.), utilities, rent, salaries, and other operational costs. For inventory items that need stock tracking, please use the <a href="/ERC-POS/views/inventory/index.php">Stock Levels</a> functionality.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Add New Expense</h5>
                </div>
                <div class="card-body">
                    <form action="/ERC-POS/handlers/expenses/create.php" method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label for="expense_type" class="form-label">Expense Type</label>
                            <select class="form-select" id="expense_type" name="expense_type" required>
                                <option value="ingredient">Ingredient</option>
                                <option value="utility">Utility</option>
                                <option value="salary">Salary</option>
                                <option value="rent">Rent</option>
                                <option value="equipment">Equipment</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="expense_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="supplier" class="form-label">Supplier/Vendor</label>
                            <input type="text" class="form-control" id="supplier" name="supplier">
                        </div>
                        <div class="col-md-3">
                            <label for="invoice_number" class="form-label">Invoice/Receipt Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Expense Items</label>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50%">Item Description</th>
                                            <th width="20%">Quantity</th>
                                            <th width="20%">Price (₱)</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <input type="text" class="form-control item-description" placeholder="e.g., Eggs, Rice, Cooking Oil" required>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control item-quantity" min="1" value="1">
                                                    <select class="form-select item-unit">
                                                        <option value="pcs">pcs</option>
                                                        <option value="kg">kg</option>
                                                        <option value="g">g</option>
                                                        <option value="L">L</option>
                                                        <option value="mL">mL</option>
                                                        <option value="pack">pack</option>
                                                        <option value="box">box</option>
                                                        <option value="sack">sack</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control item-price" min="0" step="0.01" placeholder="0.00" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-times"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2">
                                                <button type="button" class="btn btn-sm btn-success" id="addItemRow">
                                                    <i class="fas fa-plus me-1"></i> Add Item
                                                </button>
                                            </td>
                                            <td colspan="2" class="text-end">
                                                <span class="fw-bold">Total: ₱<span id="itemsTotal">0.00</span></span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <input type="hidden" name="items_list" id="items_list_hidden">
                            <input type="hidden" name="description" id="description_hidden">
                            <input type="hidden" name="amount" id="amount_hidden">
                            <input type="hidden" name="multiple_items" value="1">
                        </div>
                        
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Expenses</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Description, supplier, invoice...">
                </div>
                <div class="col-md-2">
                    <label for="expense_type_filter" class="form-label">Expense Type</label>
                    <select class="form-select" id="expense_type_filter" name="expense_type">
                        <option value="">All Types</option>
                        <option value="ingredient" <?php echo $expense_type === 'ingredient' ? 'selected' : ''; ?>>Ingredient</option>
                        <option value="utility" <?php echo $expense_type === 'utility' ? 'selected' : ''; ?>>Utility</option>
                        <option value="salary" <?php echo $expense_type === 'salary' ? 'selected' : ''; ?>>Salary</option>
                        <option value="rent" <?php echo $expense_type === 'rent' ? 'selected' : ''; ?>>Rent</option>
                        <option value="equipment" <?php echo $expense_type === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                        <option value="maintenance" <?php echo $expense_type === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="other" <?php echo $expense_type === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="/ERC-POS/views/expenses/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Expenses List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Supplier</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No expenses found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
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
                                    </td>
                                    <td><?php echo $expense['supplier'] ? htmlspecialchars($expense['supplier']) : '-'; ?></td>
                                    <td><?php echo $expense['invoice_number'] ? htmlspecialchars($expense['invoice_number']) : '-'; ?></td>
                                    <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                                    <td>
                                        <a href="http://localhost/ERC-POS/views/expenses/view.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&expense_type=<?php echo urlencode($expense_type); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&expense_type=<?php echo urlencode($expense_type); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&expense_type=<?php echo urlencode($expense_type); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsTable = document.getElementById('itemsTable');
    const itemsListHidden = document.getElementById('items_list_hidden');
    const descriptionHidden = document.getElementById('description_hidden');
    const amountHidden = document.getElementById('amount_hidden');
    const itemsTotalSpan = document.getElementById('itemsTotal');
    const expenseTypeSelect = document.getElementById('expense_type');
    const supplierField = document.getElementById('supplier');
    const invoiceField = document.getElementById('invoice_number');
    const notesField = document.getElementById('notes');
    
    // Configure form based on expense type
    function configureFormByExpenseType(expenseType) {
        const config = {
            ingredient: {
                supplierRequired: true,
                invoiceRequired: true,
                supplierLabel: 'Supplier',
                notesPlaceholder: 'Additional notes about the ingredients...',
                itemLabel: 'Item Description',
                itemPlaceholder: 'e.g., Eggs, Rice, Cooking Oil',
                units: ['pcs', 'kg', 'g', 'L', 'mL', 'pack', 'box', 'sack']
            },
            utility: {
                supplierRequired: true,
                invoiceRequired: true,
                supplierLabel: 'Service Provider',
                notesPlaceholder: 'Additional notes about utilities...',
                itemLabel: 'Utility Description',
                itemPlaceholder: 'e.g., Electricity Bill, Water Bill',
                units: ['month', 'billing']
            },
            salary: {
                supplierRequired: false,
                invoiceRequired: false,
                supplierLabel: 'Employee Name',
                notesPlaceholder: 'Additional notes about salary payment...',
                itemLabel: 'Employee Name',
                itemPlaceholder: 'Enter employee name',
                units: ['month', 'day', 'hour']
            },
            rent: {
                supplierRequired: true,
                invoiceRequired: true,
                supplierLabel: 'Landlord/Property Owner',
                notesPlaceholder: 'Additional notes about rental payment...',
                itemLabel: 'Rent Description',
                itemPlaceholder: 'e.g., Monthly Rent, Security Deposit',
                units: ['month', 'year']
            },
            equipment: {
                supplierRequired: true,
                invoiceRequired: true,
                supplierLabel: 'Vendor',
                notesPlaceholder: 'Equipment details, warranty information...',
                itemLabel: 'Equipment Description',
                itemPlaceholder: 'e.g., Kitchen Equipment, Furniture',
                units: ['unit', 'set', 'pcs']
            },
            maintenance: {
                supplierRequired: true,
                invoiceRequired: true,
                supplierLabel: 'Service Provider',
                notesPlaceholder: 'Maintenance details, service performed...',
                itemLabel: 'Service Description',
                itemPlaceholder: 'e.g., Equipment Repair, Cleaning Service',
                units: ['service', 'hour', 'day']
            },
            other: {
                supplierRequired: false,
                invoiceRequired: false,
                supplierLabel: 'Supplier/Vendor',
                notesPlaceholder: 'Provide details about this expense...',
                itemLabel: 'Item Description',
                itemPlaceholder: 'Enter item or service description',
                units: ['pcs', 'unit', 'service']
            }
        };

        const settings = config[expenseType];
        
        // Update supplier field visibility and requirements
        if (expenseType === 'salary') {
            supplierField.parentElement.style.display = 'none';
            supplierField.required = false;
            supplierField.value = '';
        } else {
            supplierField.parentElement.style.display = '';
            supplierField.required = settings.supplierRequired;
            document.querySelector('label[for="supplier"]').textContent = settings.supplierLabel;
            supplierField.placeholder = `Enter ${settings.supplierLabel.toLowerCase()}...`;
        }
        
        // Update invoice field
        invoiceField.required = settings.invoiceRequired;
        
        // Update item table labels and placeholders
        const itemDescriptionHeader = document.querySelector('#itemsTable th:first-child');
        const itemDescriptionInputs = document.querySelectorAll('.item-description');
        const unitSelects = document.querySelectorAll('.item-unit');
        
        itemDescriptionHeader.textContent = settings.itemLabel;
        itemDescriptionInputs.forEach(input => {
            input.placeholder = settings.itemPlaceholder;
        });

        // Update unit options
        unitSelects.forEach(select => {
            // Store current value
            const currentValue = select.value;
            // Clear existing options
            select.innerHTML = '';
            // Add new options
            settings.units.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit;
                option.textContent = unit;
                select.appendChild(option);
            });
            // Try to restore previous value if it exists in new options
            if (settings.units.includes(currentValue)) {
                select.value = currentValue;
            }
        });
        
        // Update notes placeholder
        notesField.placeholder = settings.notesPlaceholder;
    }
    
    // Configure form on initial load
    configureFormByExpenseType(expenseTypeSelect.value);
    
    // Configure form when expense type changes
    expenseTypeSelect.addEventListener('change', function() {
        configureFormByExpenseType(this.value);
    });
    
    // Add new item row
    document.getElementById('addItemRow').addEventListener('click', function() {
        const tbody = document.querySelector('#itemsTable tbody');
        const newRow = document.querySelector('.item-row').cloneNode(true);
        
        // Clear values in the new row
        newRow.querySelector('.item-description').value = '';
        newRow.querySelector('.item-quantity').value = '1';
        newRow.querySelector('.item-price').value = '';
        
        // Add event listeners to the new row
        addRowEventListeners(newRow);
        
        tbody.appendChild(newRow);
    });
    
    // Add event listeners to the initial row
    addRowEventListeners(document.querySelector('.item-row'));
    
    // Function to add event listeners to a row
    function addRowEventListeners(row) {
        // Remove item button
        row.querySelector('.remove-item').addEventListener('click', function() {
            // Don't remove if it's the only row
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
                updateItemsList();
                calculateTotal();
                generateDescription();
            }
        });
        
        // Update on input change
        const inputs = row.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                updateItemsList();
                calculateTotal();
                generateDescription();
            });
            input.addEventListener('input', function() {
                updateItemsList();
                calculateTotal();
                generateDescription();
            });
        });
    }
    
    // Function to update the hidden items list field
    function updateItemsList() {
        const rows = document.querySelectorAll('.item-row');
        let itemsList = [];
        
        rows.forEach(row => {
            const description = row.querySelector('.item-description').value.trim();
            const quantity = row.querySelector('.item-quantity').value;
            const unit = row.querySelector('.item-unit').value;
            const price = row.querySelector('.item-price').value;
            
            if (description) {
                let itemText = description;
                if (quantity && unit) {
                    itemText += ` (${quantity} ${unit})`;
                }
                if (price) {
                    itemText += ` - ₱${parseFloat(price).toFixed(2)}`;
                }
                itemsList.push(itemText);
            }
        });
        
        itemsListHidden.value = itemsList.join('\n');
    }
    
    // Function to calculate total
    function calculateTotal() {
        const rows = document.querySelectorAll('.item-row');
        let total = 0;
        
        rows.forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            total += quantity * price;
        });
        
        itemsTotalSpan.textContent = total.toFixed(2);
        amountHidden.value = total.toFixed(2);
    }
    
    // Function to generate description from items
    function generateDescription() {
        const rows = document.querySelectorAll('.item-row');
        let descriptions = [];
        
        rows.forEach(row => {
            const description = row.querySelector('.item-description').value.trim();
            if (description) {
                descriptions.push(description);
            }
        });
        
        // Generate a summary description
        let summaryDescription = '';
        if (descriptions.length === 1) {
            // If only one item, use it as the description
            summaryDescription = descriptions[0];
        } else if (descriptions.length > 1) {
            // If multiple items, create a summary
            const expenseType = document.getElementById('expense_type').value;
            const supplier = document.getElementById('supplier').value.trim();
            
            if (supplier) {
                summaryDescription = `${descriptions.length} ${expenseType} items from ${supplier}`;
            } else {
                summaryDescription = `${descriptions.length} ${expenseType} items`;
            }
        }
        
        descriptionHidden.value = summaryDescription;
    }
    
    // Form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        updateItemsList();
        calculateTotal();
        generateDescription();
        
        // Validate that we have at least one item with description and price
        const rows = document.querySelectorAll('.item-row');
        let valid = false;
        
        rows.forEach(row => {
            const description = row.querySelector('.item-description').value.trim();
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            
            if (description && price > 0) {
                valid = true;
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please add at least one item with a description and price.');
            return false;
        }
    });
    
    // Initialize on page load
    updateItemsList();
    calculateTotal();
    generateDescription();
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 