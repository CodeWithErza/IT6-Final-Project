<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data
$sales_query = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales,
        payment_method,
        COUNT(DISTINCT created_by) as cashier_count
    FROM orders
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN :start_date AND :end_date
    GROUP BY DATE(created_at), payment_method
    ORDER BY date DESC
";

$stmt = $conn->prepare($sales_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$sales_data = $stmt->fetchAll();

// Get inventory expenses data
$inventory_expenses_query = "
    SELECT 
        DATE(it.created_at) as date,
        SUM(it.quantity * COALESCE(it.unit_price, mi.price)) as total_expenses,
        COUNT(*) as transaction_count,
        'inventory' as expense_source
    FROM inventory_transactions it
    LEFT JOIN menu_items mi ON it.menu_item_id = mi.id
    WHERE it.transaction_type IN ('stock_in', 'adjustment')
    AND DATE(it.created_at) BETWEEN :start_date AND :end_date
    GROUP BY DATE(it.created_at)
    ORDER BY date DESC
";

$stmt = $conn->prepare($inventory_expenses_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$inventory_expenses_data = $stmt->fetchAll();

// Get general expenses data
$general_expenses_query = "
    SELECT 
        expense_date as date,
        SUM(amount) as total_expenses,
        COUNT(*) as transaction_count,
        'general' as expense_source,
        expense_type
    FROM expenses
    WHERE expense_date BETWEEN :start_date AND :end_date
    GROUP BY expense_date, expense_type
    ORDER BY date DESC
";

$stmt = $conn->prepare($general_expenses_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$general_expenses_data = $stmt->fetchAll();

// Calculate totals
$total_sales = 0;
$total_orders = 0;
$total_inventory_expenses = 0;
$total_general_expenses = 0;
$total_expenses = 0;
$sales_by_payment = [];
$expenses_by_type = [];
$daily_data = [];

foreach ($sales_data as $sale) {
    $date = $sale['date'];
    $total_sales += $sale['total_sales'];
    $total_orders += $sale['order_count'];
    
    if (!isset($sales_by_payment[$sale['payment_method']])) {
        $sales_by_payment[$sale['payment_method']] = 0;
    }
    $sales_by_payment[$sale['payment_method']] += $sale['total_sales'];
    
    if (!isset($daily_data[$date])) {
        $daily_data[$date] = [
            'sales' => 0,
            'orders' => 0,
            'inventory_expenses' => 0,
            'general_expenses' => 0,
            'total_expenses' => 0
        ];
    }
    $daily_data[$date]['sales'] += $sale['total_sales'];
    $daily_data[$date]['orders'] += $sale['order_count'];
}

foreach ($inventory_expenses_data as $expense) {
    $date = $expense['date'];
    $total_inventory_expenses += $expense['total_expenses'];
    
    if (!isset($expenses_by_type['inventory'])) {
        $expenses_by_type['inventory'] = 0;
    }
    $expenses_by_type['inventory'] += $expense['total_expenses'];
    
    if (!isset($daily_data[$date])) {
        $daily_data[$date] = [
            'sales' => 0,
            'orders' => 0,
            'inventory_expenses' => 0,
            'general_expenses' => 0,
            'total_expenses' => 0
        ];
    }
    $daily_data[$date]['inventory_expenses'] += $expense['total_expenses'];
    $daily_data[$date]['total_expenses'] += $expense['total_expenses'];
}

foreach ($general_expenses_data as $expense) {
    $date = $expense['date'];
    $expense_type = $expense['expense_type'];
    $total_general_expenses += $expense['total_expenses'];
    
    if (!isset($expenses_by_type[$expense_type])) {
        $expenses_by_type[$expense_type] = 0;
    }
    $expenses_by_type[$expense_type] += $expense['total_expenses'];
    
    if (!isset($daily_data[$date])) {
        $daily_data[$date] = [
            'sales' => 0,
            'orders' => 0,
            'inventory_expenses' => 0,
            'general_expenses' => 0,
            'total_expenses' => 0
        ];
    }
    $daily_data[$date]['general_expenses'] += $expense['total_expenses'];
    $daily_data[$date]['total_expenses'] += $expense['total_expenses'];
}

$total_expenses = $total_inventory_expenses + $total_general_expenses;
$net_profit = $total_sales - $total_expenses;
?>

<div class="container-fluid py-4">
    <h1 class="mt-4">
        <i class="fas fa-chart-line"></i>
        Summary Report
    </h1>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="summary.php" class="btn btn-secondary">Reset</a>
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
            <div class="card bg-danger text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo number_format($total_expenses, 2); ?></h4>
                    <div class="small">Total Expenses</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card <?php echo $net_profit >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0">₱<?php echo number_format($net_profit, 2); ?></h4>
                    <div class="small">Net Profit</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-0"><?php echo $total_orders; ?></h4>
                    <div class="small">Total Orders</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Cards -->
    <div class="row mb-4">
        <!-- Payment Method Breakdown -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <i class="fas fa-cash-register me-1"></i>
                    Sales by Payment Method
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_by_payment as $method => $amount): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $method === 'cash' ? 'success' : ($method === 'card' ? 'info' : 'secondary'); ?>">
                                                <?php echo ucfirst($method); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($amount, 2); ?></td>
                                        <td><?php echo number_format(($amount / $total_sales) * 100, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Expenses Breakdown -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar me-1"></i>
                    Expenses by Type
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Expense Type</th>
                                    <th>Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses_by_type as $type => $amount): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($type) {
                                                    'inventory' => 'secondary',
                                                    'ingredient' => 'primary',
                                                    'utility' => 'info',
                                                    'salary' => 'success',
                                                    'rent' => 'warning',
                                                    'equipment' => 'dark',
                                                    'maintenance' => 'danger',
                                                    default => 'light text-dark'
                                                };
                                            ?>">
                                                <?php echo ucfirst($type); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($amount, 2); ?></td>
                                        <td><?php echo number_format(($amount / $total_expenses) * 100, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th>Total</th>
                                    <th>₱<?php echo number_format($total_expenses, 2); ?></th>
                                    <th>100%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <i class="fas fa-calendar-alt me-1"></i>
            Daily Breakdown
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="summaryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sales</th>
                            <th>Inventory Expenses</th>
                            <th>General Expenses</th>
                            <th>Total Expenses</th>
                            <th>Profit</th>
                            <th>Orders</th>
                            <th>Profit Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_data as $date => $data): ?>
                            <?php 
                            $daily_profit = $data['sales'] - $data['total_expenses'];
                            $profit_margin = $data['sales'] > 0 ? ($daily_profit / $data['sales']) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($date)); ?></td>
                                <td>₱<?php echo number_format($data['sales'], 2); ?></td>
                                <td>₱<?php echo number_format($data['inventory_expenses'], 2); ?></td>
                                <td>₱<?php echo number_format($data['general_expenses'], 2); ?></td>
                                <td>₱<?php echo number_format($data['total_expenses'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $daily_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        ₱<?php echo number_format($daily_profit, 2); ?>
                                    </span>
                                </td>
                                <td><?php echo $data['orders']; ?></td>
                                <td>
                                    <span class="<?php echo $profit_margin >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($profit_margin, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#summaryTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 