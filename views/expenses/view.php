<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get expense ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['error'] = 'Invalid expense ID';
    header('Location: /ERC-POS/views/expenses/index.php');
    exit;
}

// Get expense details
$stmt = $conn->prepare("
    SELECT 
        e.*,
        u.username as created_by_name
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) {
    $_SESSION['error'] = 'Expense not found';
    header('Location: /ERC-POS/views/expenses/index.php');
    exit;
}

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
        <a href="/ERC-POS/views/expenses/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Expenses
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i>
                <?php echo htmlspecialchars($expense['description']); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Expense Type:</th>
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
                        </tr>
                        <tr>
                            <th>Amount:</th>
                            <td class="fw-bold">₱<?php echo number_format($expense['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?php echo date('F d, Y', strtotime($expense['expense_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Added By:</th>
                            <td><?php echo htmlspecialchars($expense['created_by_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Added On:</th>
                            <td><?php echo date('F d, Y h:i A', strtotime($expense['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Supplier/Vendor:</th>
                            <td><?php echo $expense['supplier'] ? htmlspecialchars($expense['supplier']) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Invoice/Receipt:</th>
                            <td><?php echo $expense['invoice_number'] ? htmlspecialchars($expense['invoice_number']) : '-'; ?></td>
                        </tr>
                        <?php if (!empty($general_notes)): ?>
                        <tr>
                            <th>Notes:</th>
                            <td><?php echo nl2br(htmlspecialchars($general_notes)); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php if ($has_multiple_items && !empty($items_list)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="border-bottom pb-2">Items Included</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="50%">Item Description</th>
                                    <th width="20%">Quantity</th>
                                    <th width="25%">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_items_cost = 0;
                                foreach ($items_list as $index => $item): 
                                    // Parse the item string to extract components
                                    $price_parts = explode(' - ₱', $item);
                                    $item_with_qty = $price_parts[0];
                                    $price = isset($price_parts[1]) ? floatval($price_parts[1]) : null;
                                    
                                    // Extract quantity if available
                                    $qty_match = [];
                                    $description = $item_with_qty;
                                    $quantity = '';
                                    
                                    if (preg_match('/^(.*?)\s*\((\d+\.?\d*)\s*([a-zA-Z]+)\)$/', $item_with_qty, $qty_match)) {
                                        $description = trim($qty_match[1]);
                                        $quantity = $qty_match[2] . ' ' . $qty_match[3];
                                    }
                                    
                                    // Add to total if price is available
                                    if ($price !== null) {
                                        $total_items_cost += $price;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($description); ?></td>
                                    <td><?php echo htmlspecialchars($quantity); ?></td>
                                    <td><?php echo $price !== null ? '₱' . number_format($price, 2) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th>₱<?php echo number_format($total_items_cost, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 