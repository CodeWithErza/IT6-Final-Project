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
            <h1 class="h2">Orders</h1>
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
                                    <a href="/ERC-POS/views/orders/view.php?id=<?php echo $order['id']; ?>" 
                                       class="text-decoration-none">
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
                                        <a href="/ERC-POS/views/orders/view.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-info" 
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
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 