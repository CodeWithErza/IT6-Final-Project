<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT 
        o.*,
        u.username as created_by_name,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON u.id = o.created_by
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($status) {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (o.order_number LIKE ? OR o.notes LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">
                <i class="fas fa-receipt me-2"></i>
                Order History
            </h1>
        </div>
        <div class="col-md-6 text-end">
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
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Order number or notes">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="/ERC-POS/views/orders/index.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date & Time</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="#" class="text-decoration-none view-receipt" data-id="<?php echo $order['id']; ?>">
                                        <?php echo htmlspecialchars($order['order_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?></td>
                                <td><?php echo number_format($order['item_count']); ?> items</td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                    $badge_class = match($order['status']) {
                                        'pending' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($order['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($order['created_by_name']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="#" class="btn btn-sm btn-info view-receipt" 
                                           data-id="<?php echo $order['id']; ?>"
                                           title="View Order">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <a href="/ERC-POS/views/orders/edit.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success complete-order" 
                                                    data-id="<?php echo $order['id']; ?>"
                                                    title="Complete Order">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger cancel-order" 
                                                    data-id="<?php echo $order['id']; ?>"
                                                    title="Cancel Order">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-muted mb-0">No orders found.</p>
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
// Auto-submit form when filters change
document.getElementById('status').addEventListener('change', function() {
    this.form.submit();
});

// Date range validation
document.querySelector('form').addEventListener('submit', function(e) {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    if (dateFrom && dateTo && dateFrom > dateTo) {
        e.preventDefault();
        alert('Date From cannot be later than Date To');
    }
});

// Complete order
document.querySelectorAll('.complete-order').forEach(function(button) {
    button.addEventListener('click', function() {
        if (confirm('Are you sure you want to complete this order?')) {
            const orderId = this.dataset.id;
            fetch('/ERC-POS/handlers/orders/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${orderId}&status=completed`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Error completing order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error completing order');
            });
        }
    });
});

// Cancel order
document.querySelectorAll('.cancel-order').forEach(function(button) {
    button.addEventListener('click', function() {
        if (confirm('Are you sure you want to cancel this order?')) {
            const orderId = this.dataset.id;
            fetch('/ERC-POS/handlers/orders/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${orderId}&status=cancelled`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Error cancelling order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling order');
            });
        }
    });
});

// View Receipt
document.querySelectorAll('.view-receipt').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const orderId = this.dataset.id;
        
        // Show loading spinner
        const loadingSpinner = document.createElement('div');
        loadingSpinner.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75';
        loadingSpinner.style.zIndex = '9999';
        loadingSpinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(loadingSpinner);
        
        // Fetch order data
        fetch(`/ERC-POS/handlers/orders/get_order_data.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                // Remove loading spinner
                document.body.removeChild(loadingSpinner);
                
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                // Show receipt modal
                showReceipt(data.order, data.items, data.settings);
            })
            .catch(error => {
                console.error('Error:', error);
                document.body.removeChild(loadingSpinner);
                alert('Error loading receipt');
            });
    });
});

// Function to show receipt
function showReceipt(order, items, settings) {
    const receiptModal = document.createElement('div');
    receiptModal.className = 'modal fade';
    receiptModal.id = 'dynamicReceiptModal';
    
    // Format date
    const orderDate = new Date(order.created_at);
    const formattedDate = orderDate.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    
    // Create receipt HTML
    receiptModal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="receipt-content">
                        <div class="text-center mb-3">
                            ${settings.show_receipt_logo === '1' ? 
                                `<img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" style="max-width: 80px; margin-bottom: 10px;">` : ''}
                            <h4>${settings.business_name || 'ERC Carinderia'}</h4>
                            ${settings.business_address ? `<p class="mb-1">${settings.business_address}</p>` : ''}
                            ${settings.business_phone ? `<p class="mb-1">${settings.business_phone}</p>` : ''}
                            <p class="mb-1">Order #${order.order_number}</p>
                            <p class="mb-1">${formattedDate}</p>
                            <p class="mb-1">Cashier: ${order.created_by_name}</p>
                        </div>
                        <div class="border-top border-bottom py-3 mb-3">
                            ${items.map(item => `
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <div>${item.menu_item_name}</div>
                                        <div class="text-muted small">₱${parseFloat(item.unit_price).toFixed(2)} × ${item.quantity}</div>
                                    </div>
                                    <div>₱${parseFloat(item.subtotal).toFixed(2)}</div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>₱${parseFloat(order.subtotal_amount).toFixed(2)}</span>
                            </div>
                            ${parseFloat(order.discount_amount) > 0 ? `
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount ${order.discount_type ? `(${order.discount_type})` : ''}:</span>
                                    <span>-₱${parseFloat(order.discount_amount).toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Total:</strong>
                                <strong>₱${parseFloat(order.total_amount).toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Amount Received:</span>
                                <span>₱${parseFloat(order.cash_received).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Change:</span>
                                <span>₱${parseFloat(order.cash_change).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="mb-1">${settings.receipt_footer || 'Thank you for your business!'}</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printReceiptBtn">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                    <a href="/ERC-POS/views/orders/view.php?id=${order.id}" class="btn btn-info" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i>View Full Details
                    </a>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(receiptModal);
    
    const modal = new bootstrap.Modal(receiptModal);
    modal.show();
    
    // Print receipt
    document.getElementById('printReceiptBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Order Receipt</title>
                    <style>
                        body {
                            font-family: 'Courier New', monospace;
                            font-size: 12px;
                            line-height: 1.4;
                            margin: 0;
                            padding: 20px;
                        }
                        .receipt-container {
                            width: 80mm;
                            margin: 0 auto;
                        }
                        .text-center {
                            text-align: center;
                        }
                        .mb-1 {
                            margin-bottom: 5px;
                        }
                        .mb-2 {
                            margin-bottom: 10px;
                        }
                        .mb-3 {
                            margin-bottom: 15px;
                        }
                        .py-3 {
                            padding-top: 15px;
                            padding-bottom: 15px;
                        }
                        .border-top {
                            border-top: 1px dashed #ccc;
                        }
                        .border-bottom {
                            border-bottom: 1px dashed #ccc;
                        }
                        .d-flex {
                            display: flex;
                        }
                        .justify-content-between {
                            justify-content: space-between;
                        }
                        .text-muted {
                            color: #6c757d;
                        }
                        .small {
                            font-size: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        ${document.getElementById('receipt-content').innerHTML}
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });
    
    receiptModal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(receiptModal);
    });
}

// Add print styles
const printStyles = document.createElement('style');
printStyles.textContent = `
    @media print {
        body * {
            visibility: hidden;
        }
        #dynamicReceiptModal .modal-content * {
            visibility: visible;
        }
        #dynamicReceiptModal .modal-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        #dynamicReceiptModal .modal-footer {
            display: none;
        }
    }
`;
document.head.appendChild(printStyles);
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 