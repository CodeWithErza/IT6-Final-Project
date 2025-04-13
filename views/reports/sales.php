<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Prepare the base query
$query = "
    SELECT 
        o.id,
        o.order_number,
        o.created_at,
        o.total_amount,
        o.payment_method,
        o.status,
        u.username as created_by,
        COUNT(oi.id) as item_count,
        GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
    FROM orders o
    LEFT JOIN users u ON o.created_by = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN :start_date AND :end_date
";

if ($payment_method) {
    $query .= " AND o.payment_method = :payment_method";
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
if ($payment_method) {
    $stmt->bindParam(':payment_method', $payment_method);
}
$stmt->execute();
$orders = $stmt->fetchAll();

// Calculate totals
$total_sales = 0;
$total_orders = count($orders);
foreach ($orders as $order) {
    $total_sales += $order['total_amount'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sales Report</h1>
    
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
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="card" <?php echo $payment_method === 'card' ? 'selected' : ''; ?>>Card</option>
                        <option value="gcash" <?php echo $payment_method === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="sales.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo number_format($total_sales, 2); ?></h4>
                    <div class="small">Total Sales</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0"><?php echo $total_orders; ?></h4>
                    <div class="small">Total Orders</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo $total_orders > 0 ? number_format($total_sales / $total_orders, 2) : '0.00'; ?></h4>
                    <div class="small">Average Order Value</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Sales Details
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="salesTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date & Time</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Cashier</th>
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
                                <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <span class="text-muted">
                                        <?php echo htmlspecialchars($order['items']); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['created_by']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#salesTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    // View receipt functionality
    document.querySelectorAll('.view-receipt').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            
            // Show modal with loading state
            modal.show();
            
            // Fetch receipt data
            fetch(`/ERC-POS/handlers/orders/get_receipt.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('receiptContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('receiptContent').innerHTML = 
                        '<div class="alert alert-danger">Error loading receipt</div>';
                });
        });
    });

    // Print receipt function
    window.printReceipt = function() {
        const printContent = document.getElementById('receiptContent').innerHTML;
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
                        .text-center { text-align: center; }
                        .receipt-logo { max-width: 60px; margin-bottom: 10px; }
                        .receipt-divider { 
                            border-top: 1px dashed #ccc;
                            margin: 10px 0;
                        }
                        .receipt-table {
                            width: 100%;
                            margin: 10px 0;
                            border-collapse: collapse;
                        }
                        .receipt-table th,
                        .receipt-table td {
                            text-align: left;
                            padding: 3px;
                        }
                        .receipt-row {
                            display: flex;
                            justify-content: space-between;
                            margin: 5px 0;
                        }
                        .total-row {
                            font-weight: bold;
                            margin: 10px 0;
                        }
                        @media print {
                            body { margin: 0; padding: 0; }
                            .receipt-container { width: 100%; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };

    // Add print styles
    const printStyles = document.createElement('style');
    printStyles.textContent = `
        @media print {
            body * {
                visibility: hidden;
            }
            #receiptModal .modal-content * {
                visibility: visible;
            }
            #receiptModal .modal-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            #receiptModal .modal-footer {
                display: none;
            }
        }
    `;
    document.head.appendChild(printStyles);
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 