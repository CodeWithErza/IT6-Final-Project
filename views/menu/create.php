<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

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
            <h1 class="h2">Add Menu Item</h1>
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
            <form action="/ERC-POS/handlers/menu/create.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_inventory_item" name="is_inventory_item" value="1">
                                <label class="form-check-label" for="is_inventory_item">Track Inventory</label>
                            </div>
                        </div>

                        <div class="mb-3" id="initial_stock_div" style="display: none;">
                            <label for="initial_stock" class="form-label">Initial Stock</label>
                            <input type="number" class="form-control" id="initial_stock" name="initial_stock" min="0">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Menu Item Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <img id="imagePreview" src="/ERC-POS/assets/images/no_image placeholder.png" class="img-fluid mb-3" style="max-height: 200px;">
                                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this);">
                                    <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, WebP</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Menu Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize inventory checkbox handler
    document.getElementById('is_inventory_item').addEventListener('change', function() {
        document.getElementById('initial_stock_div').style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            document.getElementById('initial_stock').setAttribute('required', 'required');
        } else {
            document.getElementById('initial_stock').removeAttribute('required');
        }
    });

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
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 