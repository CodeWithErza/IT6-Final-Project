<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get all categories with their menu items
$query = "
    SELECT 
        c.*,
        COUNT(m.id) as total_items,
        COUNT(CASE WHEN m.is_active = 1 THEN 1 END) as active_items
    FROM categories c
    LEFT JOIN menu_items m ON c.id = m.category_id
    GROUP BY c.id
    ORDER BY c.name
";
$categories = $conn->query($query)->fetchAll();

// Get menu items for each category
$items_query = "
    SELECT m.*, 
           COALESCE(SUM(CASE 
               WHEN it.transaction_type = 'initial' THEN it.quantity
               WHEN it.transaction_type = 'stock_in' THEN it.quantity
               WHEN it.transaction_type = 'stock_out' THEN -it.quantity
               ELSE 0
           END), 0) as current_stock
    FROM menu_items m
    LEFT JOIN inventory_transactions it ON m.id = it.menu_item_id
    WHERE m.category_id = ?
    GROUP BY m.id
    ORDER BY m.display_order, m.name
";
$items_stmt = $conn->prepare($items_query);

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 mb-3">Menu Categories</h1>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($categories as $category): ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                    <small class="text-muted">
                        <?php echo $category['active_items']; ?> active / 
                        <?php echo $category['total_items']; ?> total
                    </small>
                </div>
                <?php if ($category['description']): ?>
                    <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="menu-items-list" data-category-id="<?php echo $category['id']; ?>">
                    <?php 
                    $items_stmt->execute([$category['id']]);
                    $items = $items_stmt->fetchAll();
                    
                    if (empty($items)): 
                    ?>
                        <p class="text-muted mb-0">No menu items in this category.</p>
                    <?php else: foreach ($items as $item): ?>
                        <div class="menu-item-card mb-2" data-item-id="<?php echo $item['id']; ?>">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="drag-handle me-2">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </div>
                                        <?php if ($item['image_path']): ?>
                                            <img src="/ERC-POS/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                 class="img-thumbnail me-2" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                                <?php if (!$item['is_active']): ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                ₱<?php echo number_format($item['price'], 2); ?>
                                                <?php if ($item['is_inventory_item']): ?>
                                                    • Stock: <?php echo number_format($item['current_stock']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="ms-2">
                                            <a href="/ERC-POS/views/menu/edit.php?id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sortable for each category
    document.querySelectorAll('.menu-items-list').forEach(function(el) {
        new Sortable(el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const categoryId = evt.target.dataset.categoryId;
                const itemId = evt.item.dataset.itemId;
                const newIndex = evt.newIndex;

                // Update the order in the database
                fetch('/ERC-POS/handlers/menu/update_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `category_id=${categoryId}&item_id=${itemId}&new_index=${newIndex}`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Error updating order:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    });
});
</script>

<style>
.sortable-ghost {
    opacity: 0.5;
}
.drag-handle {
    cursor: move;
}
</style>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 