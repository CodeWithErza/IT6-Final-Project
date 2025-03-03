<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Fetch all active categories
$stmt = $conn->prepare("
    SELECT * FROM categories 
    WHERE is_active = 1 
    ORDER BY display_order ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get all active menu items with current stock levels
$stmt = $conn->prepare("
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
    WHERE m.is_active = 1
    GROUP BY m.id, m.name, m.price, m.image_path, m.is_active, m.category_id, m.created_at, m.updated_at, m.is_inventory_item, c.name
    ORDER BY m.display_order ASC
");
$stmt->execute();
$menu_items = $stmt->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Left side - Menu Items -->
        <div class="col-lg-8 border-end">
            <!-- Category Tabs -->
            <div class="bg-light border-bottom sticky-top shadow-sm">
                <ul class="nav nav-tabs border-0 px-3">
                    <?php foreach ($categories as $index => $category): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                               href="#category-<?php echo $category['id']; ?>" 
                               data-bs-toggle="tab">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Menu Items Grid -->
            <div class="tab-content p-3">
                <?php foreach ($categories as $index => $category): ?>
                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                         id="category-<?php echo $category['id']; ?>">
                        <div class="row g-3">
                            <?php foreach ($menu_items as $item): ?>
                                <?php if ($item['category_id'] === $category['id']): ?>
                                    <div class="col-md-3">
                                        <div class="card h-100 menu-item shadow-sm hover-shadow">
                                            <img src="<?php echo $item['image_path'] ? 'uploads/menu/' . $item['image_path'] : 'assets/images/default-food.jpg'; ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 style="height: 150px; object-fit: cover;">
                                            <div class="card-body d-flex flex-column">
                                                <h6 class="card-title mb-2"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                                    <p class="card-text text-primary mb-0">₱<?php echo number_format($item['price'], 2); ?></p>
                                                    <?php if ($item['is_inventory_item']): ?>
                                                        <small class="text-muted">Stock: <?php echo (int)$item['current_stock']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($item['is_inventory_item'] && (int)$item['current_stock'] <= 0): ?>
                                                    <button class="btn btn-secondary btn-sm w-100 mt-2" disabled>
                                                        Out of Stock
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary btn-sm w-100 mt-2 add-to-cart"
                                                            data-id="<?php echo (int)$item['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                            data-price="<?php echo number_format($item['price'], 2, '.', ''); ?>"
                                                            data-stock="<?php echo (int)$item['current_stock']; ?>">
                                                        Add to Order
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right side - Order Summary -->
        <div class="col-lg-4">
            <div class="sticky-top h-100 d-flex flex-column shadow-sm bg-white">
                <!-- Add Customer Button -->
                <div class="p-3 border-bottom">
                    <button class="btn btn-outline-primary w-100 shadow-sm" id="add-customer">
                        <i class="bi bi-person-plus"></i> Add Customer
                    </button>
                </div>

                <!-- Order Items -->
                <div class="flex-grow-1 overflow-auto p-3" id="order-items">
                    <!-- Order items will be dynamically added here -->
                </div>

                <!-- Order Summary -->
                <div class="border-top p-3 bg-light shadow-sm">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax (12%)</span>
                        <span id="tax">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Payable Amount</strong>
                        <strong id="total">₱0.00</strong>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning shadow-sm" id="hold-cart">
                            <i class="bi bi-clock"></i> Hold Cart
                        </button>
                        <button class="btn btn-success shadow-sm" id="proceed">
                            <i class="bi bi-check-circle"></i> Proceed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    cursor: pointer;
}

.order-item {
    background: white;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    background: transparent;
}

.nav-tabs .nav-link:hover:not(.active) {
    border-bottom: 2px solid #dee2e6;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.quantity-controls .btn {
    padding: 0.25rem 0.5rem;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderItems = new Map();
    
    // Add menu item to order
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent card click event
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const stock = parseInt(this.dataset.stock);
            
            if (orderItems.has(id)) {
                const orderItem = orderItems.get(id);
                if (stock && orderItem.quantity >= stock) {
                    alert('Cannot add more items. Stock limit reached.');
                    return;
                }
                orderItem.quantity++;
                updateOrderItemDisplay(id, orderItem);
            } else {
                const orderItem = {
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    stock: stock
                };
                orderItems.set(id, orderItem);
                addOrderItemDisplay(orderItem);
            }
            
            updateTotals();
        });
    });

    function addOrderItemDisplay(item) {
        const orderItemsContainer = document.getElementById('order-items');
        const itemElement = document.createElement('div');
        itemElement.className = 'order-item mb-3 d-flex align-items-center shadow-sm';
        itemElement.id = `order-item-${item.id}`;
        itemElement.innerHTML = `
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">${item.name}</h6>
                    <div class="quantity-controls">
                        <button class="btn btn-sm btn-outline-secondary decrease-qty shadow-sm">-</button>
                        <span class="mx-2 quantity">${item.quantity}</span>
                        <button class="btn btn-sm btn-outline-secondary increase-qty shadow-sm">+</button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted">₱${item.price.toFixed(2)} each</small>
                    <span class="item-total">₱${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            </div>
        `;

        orderItemsContainer.appendChild(itemElement);

        // Add event listeners for quantity controls
        itemElement.querySelector('.decrease-qty').addEventListener('click', () => {
            if (item.quantity > 1) {
                item.quantity--;
                updateOrderItemDisplay(item.id, item);
                updateTotals();
            } else {
                orderItems.delete(item.id);
                itemElement.remove();
                updateTotals();
            }
        });

        itemElement.querySelector('.increase-qty').addEventListener('click', () => {
            item.quantity++;
            updateOrderItemDisplay(item.id, item);
            updateTotals();
        });
    }

    function updateOrderItemDisplay(id, item) {
        const itemElement = document.getElementById(`order-item-${id}`);
        if (itemElement) {
            itemElement.querySelector('.quantity').textContent = item.quantity;
            itemElement.querySelector('.item-total').textContent = 
                `₱${(item.price * item.quantity).toFixed(2)}`;
        }
    }

    function updateTotals() {
        let subtotal = 0;
        orderItems.forEach(item => {
            subtotal += item.price * item.quantity;
        });

        const tax = subtotal * 0.12;
        const total = subtotal + tax;

        document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
        document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
    }

    // Hold Cart functionality
    document.getElementById('hold-cart').addEventListener('click', function() {
        if (orderItems.size === 0) {
            alert('Please add items to the cart first.');
            return;
        }
        // Implement hold cart logic here
    });

    // Proceed to payment
    document.getElementById('proceed').addEventListener('click', function() {
        if (orderItems.size === 0) {
            alert('Please add items to the cart first.');
            return;
        }
        // Implement payment logic here
    });

    // Add Customer functionality
    document.getElementById('add-customer').addEventListener('click', function() {
        // Implement add customer logic here
    });
});
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 