<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get menu item ID
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = "Menu item ID is required";
    header("Location: /ERC-POS/views/menu/index.php");
    exit;
}

// Get menu item data
$stmt = $conn->prepare("
    SELECT m.*, 
           COALESCE(SUM(CASE 
               WHEN it.transaction_type = 'initial' THEN it.quantity
               WHEN it.transaction_type = 'stock_in' THEN it.quantity
               WHEN it.transaction_type = 'stock_out' THEN -it.quantity
               ELSE 0
           END), 0) as current_stock
    FROM menu_items m
    LEFT JOIN inventory_transactions it ON m.id = it.menu_item_id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$id]);
$menu_item = $stmt->fetch();

if (!$menu_item) {
    $_SESSION['error'] = "Menu item not found";
    header("Location: /ERC-POS/views/menu/index.php");
    exit;
}

// Get all categories for dropdown
$categories = get_categories();

// Get error/success messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">Edit Menu Item</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="/ERC-POS/views/menu/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Menu Items
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="/ERC-POS/handlers/menu/edit.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $menu_item['id']; ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($menu_item['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $menu_item['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="<?php echo $menu_item['price']; ?>"
                                       step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_inventory_item" 
                                       name="is_inventory_item" value="1" 
                                       <?php echo $menu_item['is_inventory_item'] ? 'checked' : ''; ?>
                                       <?php echo $menu_item['is_inventory_item'] ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="is_inventory_item">Track Inventory</label>
                                <?php if ($menu_item['is_inventory_item']): ?>
                                    <small class="text-muted d-block">
                                        Current stock: <?php echo number_format($menu_item['current_stock']); ?> units
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" 
                                       name="is_active" value="1" 
                                       <?php echo $menu_item['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                                <small class="text-muted d-block">
                                    Inactive items will not appear in the menu or POS system
                                </small>
                            </div>
                        </div>

                        <?php if ($menu_item['is_inventory_item']): ?>
                        <div class="mb-3" id="stock_adjustment_div">
                            <label for="stock_adjustment" class="form-label">Stock Adjustment</label>
                            <div class="input-group">
                                <select class="form-select" id="adjustment_type" name="adjustment_type" style="max-width: 150px;">
                                    <option value="add">Add Stock</option>
                                    <option value="subtract">Subtract Stock</option>
                                </select>
                                <input type="number" class="form-control" id="stock_adjustment" 
                                       name="stock_adjustment" min="0" placeholder="Enter quantity">
                                <button type="button" class="btn btn-secondary" onclick="adjustStock()">
                                    Apply Adjustment
                                </button>
                            </div>
                            <small class="text-muted">Current Stock: <?php echo $menu_item['current_stock']; ?></small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Menu Item Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <img id="imagePreview" 
                                         src="<?php echo $menu_item['image_path'] 
                                            ? '/ERC-POS/' . $menu_item['image_path'] 
                                            : '/ERC-POS/assets/images/no_image placeholder.png'; ?>" 
                                         class="img-fluid mb-3" 
                                         style="max-height: 200px;">
                                    <input type="file" class="form-control" id="image" name="image" 
                                           accept="image/jpeg,image/png,image/webp" onchange="previewImage(this);">
                                    <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, WebP</small>
                                    <?php if ($menu_item['image_path']): ?>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image" value="1">
                                            <label class="form-check-label" for="remove_image">Remove current image</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview handler
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Set up image preview handler
    document.getElementById('image').addEventListener('change', function() {
        previewImage(this);
    });

    // Handle remove image checkbox
    document.getElementById('remove_image')?.addEventListener('change', function() {
        const imageInput = document.getElementById('image');
        if (this.checked) {
            imageInput.value = '';
            imageInput.disabled = true;
            document.getElementById('imagePreview').src = '/ERC-POS/assets/images/no_image placeholder.png';
        } else {
            imageInput.disabled = false;
            document.getElementById('imagePreview').src = '<?php echo $menu_item['image_path'] 
                ? '/ERC-POS/' . $menu_item['image_path'] 
                : '/ERC-POS/assets/images/no_image placeholder.png'; ?>';
        }
    });
});

function adjustStock() {
    const type = document.getElementById('adjustment_type').value;
    const quantity = parseInt(document.getElementById('stock_adjustment').value);
    
    if (!quantity || quantity < 0) {
        alert('Please enter a valid quantity');
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('menu_item_id', <?php echo $menu_item['id']; ?>);
    formData.append('type', type);
    formData.append('quantity', quantity);

    // Send request
    fetch('/ERC-POS/handlers/menu/adjust_stock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Failed to adjust stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to adjust stock');
    });
}
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 