<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ERC-POS/views/dashboard/index.php");
    exit;
}

// Get current settings
$stmt = $conn->query("SELECT * FROM settings ORDER BY setting_group, setting_name");
$settings = $stmt->fetchAll();

// Get all categories
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Group settings
$grouped_settings = [];
foreach ($settings as $setting) {
    $grouped_settings[$setting['setting_group']][] = $setting;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        if (isset($_POST['settings'])) {
            foreach ($_POST['settings'] as $id => $value) {
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_by = ? WHERE id = ?");
                $stmt->execute([$value, $_SESSION['user_id'], $id]);
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Settings updated successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error updating settings: " . $e->getMessage();
    }
}

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h2">
                <i class="fas fa-cog"></i>
                Settings
            </h1>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <!-- Categories Section -->
            <div class="accordion mb-4" id="categoriesAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingCategories">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapseCategories">
                            <i class="fas fa-tags me-2"></i>Categories Management
                        </button>
                    </h2>
                    <div id="collapseCategories" class="accordion-collapse collapse show" 
                         data-bs-parent="#categoriesAccordion">
                        <div class="accordion-body">
                            <!-- Add Category Form -->
                            <form action="/ERC-POS/handlers/categories/create.php" method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" name="name" class="form-control" placeholder="Category Name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check form-switch mt-2">
                                            <input type="checkbox" class="form-check-input" name="is_active" id="newCategoryActive" checked>
                                            <label class="form-check-label" for="newCategoryActive">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Categories Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Items Count</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): 
                                            // Get items count for this category
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
                                            $stmt->execute([$category['id']]);
                                            $items_count = $stmt->fetchColumn();
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td><?php echo $items_count; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $category['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-category" 
                                                            data-id="<?php echo $category['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                            data-active="<?php echo $category['is_active']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($items_count === 0): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-category"
                                                                data-id="<?php echo $category['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <form method="POST" action="">
                <div class="accordion" id="settingsAccordion">
                    <?php foreach ($grouped_settings as $group => $group_settings): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo str_replace(' ', '', $group); ?>">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo str_replace(' ', '', $group); ?>">
                                    <?php echo ucfirst($group); ?> Settings
                                </button>
                            </h2>
                            <div id="collapse<?php echo str_replace(' ', '', $group); ?>" 
                                 class="accordion-collapse collapse show" 
                                 data-bs-parent="#settingsAccordion">
                                <div class="accordion-body">
                                    <?php foreach ($group_settings as $setting): ?>
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <?php echo htmlspecialchars($setting['setting_label']); ?>
                                                <?php if ($setting['description']): ?>
                                                    <i class="fas fa-info-circle" data-bs-toggle="tooltip" 
                                                       title="<?php echo htmlspecialchars($setting['description']); ?>"></i>
                                                <?php endif; ?>
                                            </label>
                                            
                                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input" 
                                                           name="settings[<?php echo $setting['id']; ?>]" 
                                                           value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                </div>
                                            <?php elseif ($setting['setting_type'] === 'textarea'): ?>
                                                <textarea class="form-control" 
                                                        name="settings[<?php echo $setting['id']; ?>]" 
                                                        rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                            <?php else: ?>
                                                <input type="<?php echo $setting['setting_type']; ?>" 
                                                       class="form-control" 
                                                       name="settings[<?php echo $setting['id']; ?>]" 
                                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                            <?php endif; ?>
                                            
                                            <?php if ($setting['help_text']): ?>
                                                <div class="form-text"><?php echo htmlspecialchars($setting['help_text']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Edit Category Modal
    const editButtons = document.querySelectorAll('.edit-category');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const active = this.dataset.active === '1';
            
            // Create modal element
            const modalElement = document.createElement('div');
            modalElement.className = 'modal fade';
            modalElement.id = 'editCategoryModal';
            modalElement.setAttribute('tabindex', '-1');
            modalElement.setAttribute('aria-hidden', 'true');
            
            modalElement.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/ERC-POS/handlers/categories/edit.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="${id}">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" value="${name}" required>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="is_active" id="categoryActive" ${active ? 'checked' : ''}>
                                        <label class="form-check-label" for="categoryActive">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('editCategoryModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to document
            document.body.appendChild(modalElement);
            
            // Initialize and show modal
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Remove modal from DOM after it's hidden
            modalElement.addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        });
    });

    // Delete Category Confirmation
    const deleteButtons = document.querySelectorAll('.delete-category');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            if (confirm(`Are you sure you want to delete the category "${name}"? This action cannot be undone.`)) {
                window.location.href = `/ERC-POS/handlers/categories/delete.php?id=${id}`;
            }
        });
    });
});
</script>

<style>
.accordion-body {
    background-color: #ffffff;
    padding: 1.5rem;
}

.accordion-button {
    background-color: #f8f9fa;
}

.accordion-button:not(.collapsed) {
    background-color: var(--primary-color);
    color: #ffffff;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0, 0, 0, 0.125);
}
</style>

<?php require_once __DIR__ . '/../../static/templates/footer.php'; ?> 