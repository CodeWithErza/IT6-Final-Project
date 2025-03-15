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
    .cart-item {
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        border-left: 3px solid var(--primary-color);
    }
    .cart-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    .cart-item-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .cart-item-details {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }
    .cart-item-quantity, .cart-item-price {
        color: #555;
    }
    .cart-item-total {
        font-weight: 600;
        color: var(--primary-color);
    }
    .stock-in-cart-items {
        max-height: 400px;
        overflow-y: auto;
    }
    .empty-cart-message {
        color: #aaa;
    }
    #recentTransactionsTable th {
        white-space: nowrap;
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    #recentTransactionsTable td {
        vertical-align: middle;
    }
    /* Stats Card Styles */
    .stats-card {
        background: #fff;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        transition: transform 0.3s ease;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
    .stats-icon i {
        font-size: 1.5rem;
        color: #fff;
    }
    .stats-icon.primary {
        background: var(--primary-color);
    }
    .stats-icon.accent {
        background: var(--accent-color);
    }
    .stats-icon.highlight {
        background: var(--highlight-color);
    }
    .stats-info {
        flex: 1;
    }
    .stats-title {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .stats-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
    }
    /* Filter Styles */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-container--bootstrap-5 .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
    }
    /* DataTable Styles */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dt-buttons {
        margin-bottom: 1rem;
    }
    .dt-button {
        background: #f8f9fa !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 0.25rem !important;
        padding: 0.375rem 0.75rem !important;
        margin-right: 0.5rem !important;
    }
    .dt-button:hover {
        background: #e9ecef !important;
    }
    .summary-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .summary-card:hover {
        transform: translateY(-5px);
    }
    .summary-card .card-body {
        padding: 1.5rem;
    }
    .summary-card .card-title {
        margin-bottom: 1rem;
        font-size: 1rem;
        opacity: 0.9;
    }
    .summary-card .card-text {
        margin-bottom: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    #recentTransactionsTable th {
        white-space: nowrap;
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    #recentTransactionsTable td {
        vertical-align: middle;
    }
    .dt-buttons {
        margin-bottom: 1rem;
    }
    .dt-button {
        background-color: #f8f9fa !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 4px !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.9rem !important;
        margin-right: 0.5rem !important;
    }
    .dt-button:hover {
        background-color: #e9ecef !important;
        border-color: #dee2e6 !important;
    }
    /* Summary Cards */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .summary-card {
        background: #fff;
        border-radius: 10px;
        padding: 1.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid var(--primary-color);
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .summary-card.transactions {
        border-left-color: var(--primary-color);
    }

    .summary-card.items {
        border-left-color: var(--accent-color);
    }

    .summary-card.cost {
        border-left-color: var(--highlight-color);
    }

    .summary-card .card-title {
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-card .card-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0;
    }

    /* Stock-in Cart */
    .stock-in-cart {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-left: 1rem;
        position: sticky;
        top: 80px;
    }

    .stock-in-cart .cart-header {
        background: var(--primary-color);
        color: white;
        padding: 1rem;
        border-radius: 10px 10px 0 0;
        font-weight: 600;
    }

    .cart-items {
        padding: 1rem;
        max-height: calc(100vh - 400px);
        overflow-y: auto;
    }

    .cart-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 3px solid var(--accent-color);
        transition: transform 0.2s ease;
    }

    .cart-item:hover {
        transform: translateX(5px);
    }

    .cart-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .cart-item-name {
        font-weight: 600;
        color: #2c3e50;
    }

    .cart-item-remove {
        color: var(--primary-color);
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .cart-item-remove:hover {
        color: #dc3545;
    }

    .cart-item-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .cart-item-detail {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cart-item-label {
        color: #6c757d;
    }

    .cart-item-value {
        font-weight: 500;
        color: #2c3e50;
    }

    .cart-total {
        background: #f8f9fa;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        border-radius: 0 0 10px 10px;
    }

    .cart-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #2c3e50;
    }

    .empty-cart-message {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .empty-cart-message i {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    /* Transaction Form */
    .transaction-form {
        background: #fff;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
    }

    .transaction-form .form-group {
        margin-bottom: 1rem;
    }

    .transaction-form label {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }

    .transaction-form .btn-primary {
        width: 100%;
        margin-top: 1rem;
    }

    /* Recent Transactions */
    .recent-transactions {
        margin-top: 2rem;
    }

    .recent-transactions .card-header {
        background: var(--primary-color);
        color: white;
        font-weight: 600;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 0.375rem 0.75rem;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(210, 102, 95, 0.25);
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-box me-2"></i>
            Stock Levels
        </h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Side - Stock Levels -->
        <div class="col-lg-8">
    <!-- Top Action Buttons -->
    <div class="top-actions">
        <a href="/ERC-POS/views/inventory/stock_adjustment.php" style="background-color: #17a2b8;">
            <i class="fas fa-balance-scale"></i> Adjust Stock
        </a>
        <a href="/ERC-POS/views/expenses/index.php" style="background-color: #ffc107; color: black;">
            <i class="fas fa-file-invoice-dollar"></i> Expenses
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
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
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
                                <div class="d-flex gap-2 mt-2">
                                    <button class="btn btn-sm btn-primary add-to-cart-btn" 
                                            onclick="addToCart(<?php echo htmlspecialchars(json_encode([
                                                'id' => $item['id'],
                                                'name' => $item['name'],
                                                'currentStock' => $item['current_stock']
                                            ])); ?>)">
                                        <i class="fas fa-plus"></i> Add Stock
                                    </button>
                                    <a href="#" class="btn btn-sm btn-outline-secondary" onclick="showItemHistory(<?php echo htmlspecialchars(json_encode([
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'history' => $history_data[$item['id']] ?? []
                        ])); ?>)">
                                        <i class="fas fa-history"></i>
                        </a>
                                </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

        <!-- Right Side - Stock-In Cart -->
        <div class="col-lg-4">
            <div class="stock-in-cart">
                <div class="cart-header">
                    <i class="fas fa-shopping-cart me-2"></i>Stock-In Cart
            </div>
                <div class="cart-items">
                    <!-- Cart items will be added here dynamically -->
                    <div class="empty-cart-message">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No items in cart. Click "Add Stock" on any item to add it to the cart.</p>
                    </div>
                        </div>

                <!-- Transaction Form -->
                <div class="transaction-form">
                    <form id="stockInForm" action="/ERC-POS/handlers/inventory/add_stock.php" method="POST">
                        <input type="hidden" id="cart_items" name="cart_items" value="">
                        
                        <div class="form-group">
                            <label for="transaction_date">Transaction Date</label>
                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                        
                        <div class="row">
                        <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier">Supplier</label>
                                    <input type="text" class="form-control" id="supplier" name="supplier">
                            </div>
                        </div>
                        <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_number">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_number" name="invoice_number">
                        </div>
                    </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitStockIn" disabled>
                                <i class="fas fa-save me-2"></i>Save Stock-In Transaction
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Stock-In Transactions -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-history me-2"></i>Recent Stock-In Transactions
            </h5>
            <a href="/ERC-POS/views/inventory/history.php" class="btn btn-sm btn-outline-primary">
                View All History
            </a>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" class="row g-3 mb-4" id="stockInFilterForm">
                <div class="col-md-3">
                    <label for="filter_menu_item" class="form-label">Menu Item</label>
                    <select class="form-select" id="filter_menu_item" name="filter_menu_item">
                        <option value="">All Items</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="filter_date_from" name="filter_date_from">
                </div>
                <div class="col-md-3">
                    <label for="filter_date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="filter_date_to" name="filter_date_to">
                </div>
                <div class="col-md-3">
                    <label for="filter_supplier" class="form-label">Supplier</label>
                    <input type="text" class="form-control" id="filter_supplier" name="filter_supplier">
                    </div>
                </form>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card transactions">
                    <h6 class="card-title">
                        <i class="fas fa-exchange-alt me-2"></i>Total Transactions
                    </h6>
                    <p class="card-value" id="totalTransactions">0</p>
            </div>
                <div class="summary-card items">
                    <h6 class="card-title">
                        <i class="fas fa-boxes me-2"></i>Total Items Added
                    </h6>
                    <p class="card-value" id="totalItems">0</p>
            </div>
                <div class="summary-card cost">
                    <h6 class="card-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Total Cost
                    </h6>
                    <p class="card-value" id="totalCost">₱0.00</p>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-hover" id="recentTransactionsTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Cost</th>
                            <th>Supplier</th>
                            <th>Invoice #</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get recent stock-in transactions
                        $transactions_query = "
                            SELECT 
                                it.*,
                                m.name as item_name,
                                u.username as username
                            FROM inventory_transactions it
                            JOIN menu_items m ON it.menu_item_id = m.id
                            LEFT JOIN users u ON it.created_by = u.id
                            WHERE it.transaction_type = 'stock_in'
                            ORDER BY it.created_at DESC
                            LIMIT 100
                        ";
                        $transactions = $conn->query($transactions_query)->fetchAll();
                        
                        foreach ($transactions as $transaction):
                            $total_cost = $transaction['quantity'] * $transaction['unit_price'];
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                <td><?php echo number_format($transaction['quantity']); ?></td>
                                <td>₱<?php echo number_format($transaction['unit_price'], 2); ?></td>
                                <td>₱<?php echo number_format($total_cost, 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['supplier'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['invoice_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['username'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Item Quantity Modal -->
<div class="modal fade" id="itemQuantityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item to Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_item_id">
                <input type="hidden" id="modal_item_name">
                <input type="hidden" id="modal_current_stock">
                
                    <div class="mb-3">
                    <label for="modal_quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="modal_quantity" min="1" value="1" required>
                    </div>
                
                    <div class="mb-3">
                    <label for="modal_unit_price" class="form-label">Unit Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="modal_unit_price" min="0.01" step="0.01" value="0.00" required>
                    </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAddToCart">Add to Cart</button>
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
// Initialize the cart items array
window.cartItems = [];

// Function to add item to cart (opens modal)
function addToCart(itemData) {
    // Set modal values
    document.getElementById('modal_item_id').value = itemData.id;
    document.getElementById('modal_item_name').value = itemData.name;
    document.getElementById('modal_current_stock').value = itemData.currentStock;
    document.getElementById('modal_quantity').value = 1;
    document.getElementById('modal_unit_price').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('itemQuantityModal'));
    modal.show();
    
    // Focus on quantity field
    document.getElementById('modal_quantity').focus();
}

// Function to add item to cart (after modal confirmation)
function addItemToCart(itemId, itemName, currentStock, quantity, unitPrice) {
    // Check if item already exists in cart
    const existingItemIndex = window.cartItems.findIndex(item => item.id === itemId);
    
    if (existingItemIndex !== -1) {
        // Update existing item
        window.cartItems[existingItemIndex].quantity += quantity;
        window.cartItems[existingItemIndex].unitPrice = unitPrice;
    } else {
        // Add new item
        window.cartItems.push({
            id: itemId,
            name: itemName,
            currentStock: currentStock,
            quantity: quantity,
            unitPrice: unitPrice
        });
    }
    
    // Update cart display
    updateCartDisplay();
}

// Function to remove item from cart
function removeFromCart(index) {
    window.cartItems.splice(index, 1);
    updateCartDisplay();
}

// Function to update cart display
function updateCartDisplay() {
    const cartContainer = document.querySelector('.cart-items');
    const emptyCartMessage = document.querySelector('.empty-cart-message');
    const submitButton = document.getElementById('submitStockIn');
    
    // Clear current cart display
    cartContainer.innerHTML = '';
    
    if (window.cartItems.length === 0) {
        // Show empty cart message
        cartContainer.appendChild(emptyCartMessage);
        submitButton.disabled = true;
    } else {
        // Hide empty cart message and enable submit button
        emptyCartMessage.remove();
        submitButton.disabled = false;
        
        // Add items to cart display
        window.cartItems.forEach((item, index) => {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="cart-item-header">
                    <div>
                        <div class="cart-item-name">${item.name}</div>
                        <small class="text-muted">Current Stock: ${item.currentStock}</small>
                    </div>
                    <button type="button" class="btn-close" onclick="removeFromCart(${index})" aria-label="Remove item"></button>
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-detail">
                        <span class="cart-item-label">Quantity:</span>
                        <span class="cart-item-value">${item.quantity}</span>
                    </div>
                    <div class="cart-item-detail">
                        <span class="cart-item-label">Unit Price:</span>
                        <span class="cart-item-value">₱${item.unitPrice.toFixed(2)}</span>
                    </div>
                    <div class="cart-item-detail" style="grid-column: 1 / -1">
                        <span class="cart-item-label">Total:</span>
                        <span class="cart-item-value" style="color: var(--primary-color)">₱${(item.quantity * item.unitPrice).toFixed(2)}</span>
                    </div>
                </div>
            `;
            cartContainer.appendChild(cartItem);
        });
    }
    
    // Update hidden input with cart items
    document.getElementById('cart_items').value = JSON.stringify(window.cartItems);
}

// Function to clear cart
function clearCart() {
    if (window.cartItems.length > 0 && confirm('Are you sure you want to clear the cart?')) {
        window.cartItems = [];
        updateCartDisplay();
    }
}

// Set up the confirm add to cart button
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for recent transactions
    $('#recentTransactionsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    document.getElementById('confirmAddToCart').addEventListener('click', function() {
        const itemId = document.getElementById('modal_item_id').value;
        const itemName = document.getElementById('modal_item_name').value;
        const currentStock = parseInt(document.getElementById('modal_current_stock').value);
        const quantity = parseInt(document.getElementById('modal_quantity').value);
        const unitPrice = parseFloat(document.getElementById('modal_unit_price').value);
        
        if (quantity <= 0) {
            alert('Quantity must be greater than 0');
            return;
        }
        
        if (unitPrice <= 0) {
            alert('Unit price must be greater than 0');
            return;
        }
        
        // Add item to cart
        addItemToCart(itemId, itemName, currentStock, quantity, unitPrice);
        
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('itemQuantityModal'));
        modal.hide();
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

// Initialize DataTable for recent transactions
$(document).ready(function() {
    // Check if DataTable is already initialized
    let transactionsTable;
    if (!$.fn.DataTable.isDataTable('#recentTransactionsTable')) {
        transactionsTable = $('#recentTransactionsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
    } else {
        transactionsTable = $('#recentTransactionsTable').DataTable();
    }

    // Function to update summary cards
    function updateSummaryCards(data) {
        $('#totalTransactions').text(data.length);
        
        let totalItems = 0;
        let totalCost = 0;
        
        data.forEach(row => {
            totalItems += parseInt(row[2].replace(/,/g, ''));
            totalCost += parseFloat(row[4].replace('₱', '').replace(/,/g, ''));
        });
        
        $('#totalItems').text(totalItems.toLocaleString());
        $('#totalCost').text('₱' + totalCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    // Update summary initially
    updateSummaryCards(transactionsTable.rows().data().toArray());

    // Handle filter changes
    $('#stockInFilterForm select, #stockInFilterForm input').on('change', function() {
        const menuItem = $('#filter_menu_item').val();
        const dateFrom = $('#filter_date_from').val();
        const dateTo = $('#filter_date_to').val();
        const supplier = $('#filter_supplier').val().toLowerCase();

        // Remove any existing custom filters
        $.fn.dataTable.ext.search.pop();

        // Add new custom filter
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            let valid = true;

            // Menu item filter
            if (menuItem && data[1] !== menuItem) {
                valid = false;
            }

            // Date range filter
            if (dateFrom || dateTo) {
                const date = new Date(data[0]);
                if (dateFrom && date < new Date(dateFrom)) valid = false;
                if (dateTo && date > new Date(dateTo)) valid = false;
            }

            // Supplier filter
            if (supplier && !data[5].toLowerCase().includes(supplier)) {
                valid = false;
            }

            return valid;
        });

        transactionsTable.draw();

        // Update summary cards with filtered data
        updateSummaryCards(transactionsTable.rows({search: 'applied'}).data().toArray());
    });
});
</script>

<?php include '../../static/templates/footer.php'; ?> 