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
                <?php
                // Group menu items by category
                $grouped_items = [];
                foreach ($menu_items as $item) {
                    $category_id = $item['category_id'];
                    if (!isset($grouped_items[$category_id])) {
                        $grouped_items[$category_id] = [
                            'name' => $item['category_name'],
                            'items' => []
                        ];
                    }
                    $grouped_items[$category_id]['items'][] = $item;
                }
                ?>

                <?php if (empty($menu_items)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No menu items found.</p>
                    </div>
                <?php else: ?>
                    <style>
                        .menu-table th,
                        .menu-table td {
                            vertical-align: middle;
                        }
                        .menu-table th:nth-child(1), /* Image */
                        .menu-table td:nth-child(1) {
                            width: 80px;
                        }
                        .menu-table th:nth-child(2), /* Name */
                        .menu-table td:nth-child(2) {
                            width: 30%;
                        }
                        .menu-table th:nth-child(3), /* Price */
                        .menu-table td:nth-child(3) {
                            width: 15%;
                        }
                        .menu-table th:nth-child(4), /* Stock */
                        .menu-table td:nth-child(4) {
                            width: 15%;
                        }
                        .menu-table th:nth-child(5), /* Status */
                        .menu-table td:nth-child(5) {
                            width: 15%;
                        }
                        .menu-table th:nth-child(6), /* Actions */
                        .menu-table td:nth-child(6) {
                            width: 15%;
                            text-align: right;
                        }
                        .category-header {
                            background-color: #f8f9fa;
                            padding: 1rem;
                            margin: 1.5rem 0 1rem 0;
                            border-radius: 0.25rem;
                            border-left: 4px solid #1e3c72;
                        }
                    </style>

                    <?php foreach ($grouped_items as $category_id => $category): ?>
                        <div class="category-header">
                            <h5 class="mb-0">
                                <i class="fas fa-th-large me-2"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h5>
                        </div>
                        <table class="table table-hover align-middle menu-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php 
                                                $image_path = $item['image_path'] 
                                                    ? $item['image_path'] 
                                                    : 'assets/images/no_image placeholder.png';
                                                if (strpos($image_path, '/') !== 0) {
                                                    $image_path = '/' . $image_path;
                                                }
                                                echo '/ERC-POS' . $image_path;
                                            ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                 onerror="this.src='/ERC-POS/assets/images/no_image placeholder.png'">
                                        </td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <?php if ($item['is_inventory_item']): ?>
                                                <span class="badge bg-info">
                                                    <?php echo number_format($item['current_stock']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
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
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <button type="button" 
                                                       class="btn btn-sm btn-primary edit-btn"
                                                       data-item-id="<?php echo $item['id']; ?>"
                                                       data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                       data-item-category="<?php echo $item['category_id']; ?>"
                                                       data-item-price="<?php echo $item['price']; ?>"
                                                       data-item-inventory="<?php echo $item['is_inventory_item']; ?>"
                                                       data-item-active="<?php echo $item['is_active']; ?>"
                                                       data-item-stock="<?php echo $item['current_stock']; ?>"
                                                       data-item-image="<?php echo $item['image_path'] ? '/ERC-POS/' . $item['image_path'] : '/ERC-POS/assets/images/no_image placeholder.png'; ?>"
                                                       onclick="openEditModal(this)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" action="/ERC-POS/handlers/menu/edit.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" id="edit_category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="edit_price" name="price" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_inventory_item" 
                                           name="is_inventory_item" value="1" disabled>
                                    <label class="form-check-label" for="edit_is_inventory_item">Track Inventory</label>
                                    <div id="current_stock_info" class="text-muted small"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_active" 
                                           name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">Active</label>
                                    <small class="text-muted d-block">
                                        Inactive items will not appear in the menu or POS system
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label for="edit_image" class="form-label">Menu Item Image</label>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <img id="edit_imagePreview" src="" class="img-fluid mb-3" style="max-height: 200px;">
                                        <input type="file" class="form-control" id="edit_image" name="image" 
                                               accept="image/jpeg,image/png,image/webp" onchange="previewEditImage(this);">
                                        <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, WebP</small>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="edit_remove_image" name="remove_image" value="1">
                                            <label class="form-check-label" for="edit_remove_image">Remove current image</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditForm()">Save Changes</button>
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

// Initialize edit modal
let editModal = null;
document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
});

function openEditModal(button) {
    // Get item data from button attributes
    const itemId = button.dataset.itemId;
    const itemName = button.dataset.itemName;
    const itemCategory = button.dataset.itemCategory;
    const itemPrice = button.dataset.itemPrice;
    const itemInventory = button.dataset.itemInventory === "1";
    const itemActive = button.dataset.itemActive === "1";
    const itemStock = button.dataset.itemStock;
    const itemImage = button.dataset.itemImage;

    // Set form values
    document.getElementById('edit_id').value = itemId;
    document.getElementById('edit_name').value = itemName;
    document.getElementById('edit_category_id').value = itemCategory;
    document.getElementById('edit_price').value = itemPrice;
    document.getElementById('edit_is_inventory_item').checked = itemInventory;
    document.getElementById('edit_is_active').checked = itemActive;
    document.getElementById('edit_imagePreview').src = itemImage;

    // Update inventory info
    if (itemInventory) {
        document.getElementById('current_stock_info').textContent = `Current stock: ${itemStock}`;
    } else {
        document.getElementById('current_stock_info').textContent = '';
    }

    // Show modal
    editModal.show();
}

function previewEditImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit_imagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        document.getElementById('edit_remove_image').checked = false;
    }
}

function submitEditForm() {
    document.getElementById('editForm').submit();
}

// Handle remove image checkbox
document.getElementById('edit_remove_image').addEventListener('change', function() {
    const imageInput = document.getElementById('edit_image');
    if (this.checked) {
        imageInput.value = '';
        imageInput.disabled = true;
        document.getElementById('edit_imagePreview').src = '/ERC-POS/assets/images/no_image placeholder.png';
    } else {
        imageInput.disabled = false;
        const editButton = document.querySelector(`[data-item-id="${document.getElementById('edit_id').value}"]`);
        document.getElementById('edit_imagePreview').src = editButton.dataset.itemImage;
    }
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 