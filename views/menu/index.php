<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get filter values
$category_id = $_GET['category_id'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Build query
$query = "
    SELECT 
        m.*,
        c.name as category_name,
        COALESCE(SUM(CASE 
            WHEN it.transaction_type = 'initial' THEN it.quantity
            WHEN it.transaction_type = 'stock_in' THEN it.quantity
            WHEN it.transaction_type = 'stock_out' THEN -it.quantity
            ELSE 0
        END), 0) as current_stock
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN inventory_transactions it ON m.id = it.menu_item_id
    WHERE 1=1
";

$params = [];

if ($category_id) {
    $query .= " AND m.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $query .= " AND (m.name LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $query .= " AND m.is_active = ?";
    $params[] = ($status === 'active' ? 1 : 0);
}

$query .= " GROUP BY m.id ORDER BY m.name";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$menu_items = $stmt->fetchAll();

// Get all categories for filter
$categories = get_categories();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">Menu Items</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="/ERC-POS/views/menu/create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Menu Item
            </a>
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
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name...">
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Menu Items List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px">Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th style="width: 100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?php 
                                        echo $item['image_path'] 
                                            ? '/ERC-POS/' . $item['image_path'] 
                                            : '/ERC-POS/assets/images/no_image placeholder.png'; 
                                        ?>" 
                                         class="img-thumbnail" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <?php if ($item['is_inventory_item']): ?>
                                        <?php echo number_format($item['current_stock']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="/ERC-POS/handlers/menu/toggle_status.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">
                                            <?php if ($item['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="/ERC-POS/views/menu/edit.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($item['is_inventory_item']): ?>
                                        <a href="/ERC-POS/views/inventory/adjust.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="Adjust Stock">
                                            <i class="fas fa-boxes"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form action="/ERC-POS/handlers/menu/delete.php" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this menu item? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($menu_items)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">No menu items found.</p>
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
document.querySelectorAll('#category_id, #status').forEach(function(element) {
    element.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 