<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get all categories
$categories = get_categories();

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get category for editing if ID is provided
$edit_id = $_GET['edit'] ?? null;
$edit_category = null;
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">Categories</h1>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Category Form -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <?php echo $edit_category ? 'Edit Category' : 'Add Category'; ?>
                    </h5>
                    <form action="/ERC-POS/handlers/categories/<?php echo $edit_category ? 'edit.php' : 'create.php'; ?>" method="POST">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_category): ?>
                                <a href="/ERC-POS/views/categories/index.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Items Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
                                            $stmt->execute([$category['id']]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($stmt->fetchColumn() == 0): ?>
                                            <form action="/ERC-POS/handlers/categories/delete.php" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-muted mb-0">No categories found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 