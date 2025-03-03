<?php
require_once 'helpers/functions.php';
include 'static/templates/header.php';

// Get all categories
$stmt = $conn->prepare("
    SELECT * FROM categories 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get menu items with current stock levels
$stmt = $conn->prepare("
    SELECT 
        m.*,
        c.name as category_name,
        COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' OR transaction_type = 'adjustment' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                END
            )
            FROM inventory_transactions 
            WHERE menu_item_id = m.id
            ), 0
        ) as current_stock
    FROM menu_items m 
    LEFT JOIN categories c ON m.category_id = c.id 
    WHERE m.is_active = 1
    ORDER BY m.category_id, m.name
");
$stmt->execute();
$menu_items = $stmt->fetchAll();

// Get messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid px-4">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Menu Section (Left Side) -->
        <div class="col-lg-8">
            <!-- Category Tabs -->
            <div class="category-tabs mt-4">
                <ul class="nav nav-pills mb-4" id="menuTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#all" type="button">
                            All
                        </button>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#category<?php echo $category['id']; ?>" type="button">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Menu Items Grid -->
                <div class="tab-content" id="menuTabsContent">
                    <!-- All Items Tab -->
                    <div class="tab-pane fade show active" id="all">
                        <div class="row g-3">
                            <?php foreach ($menu_items as $item): ?>
                                <div class="col-md-4 col-lg-3 col-xxl-2">
                                    <div class="card menu-item-card h-100 <?php echo ($item['is_inventory_item'] && $item['current_stock'] <= 0) ? 'out-of-stock' : ''; ?>" 
                                         data-id="<?php echo $item['id']; ?>" 
                                         data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                         data-price="<?php echo $item['price']; ?>"
                                         data-stock="<?php echo $item['current_stock']; ?>"
                                         data-inventory="<?php echo $item['is_inventory_item']; ?>">
                                        <?php if ($item['is_inventory_item']): ?>
                                            <div class="stock-badge <?php echo $item['current_stock'] <= 0 ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo $item['current_stock'] <= 0 ? 'Out of Stock' : 'In Stock'; ?>
                                            </div>
                                        <?php endif; ?>
                                        <img src="<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/default-food.jpg'; ?>" 
                                             class="card-img-top menu-item-image" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='assets/images/default-food.jpg'">
                                        <div class="card-body p-2">
                                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="card-text price mb-0">₱<?php echo number_format($item['price'], 2); ?></p>
                                            <?php if ($item['is_inventory_item']): ?>
                                                <small class="text-muted">Stock: <?php echo $item['current_stock']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Category Tabs -->
                    <?php foreach ($categories as $category): ?>
                        <div class="tab-pane fade" id="category<?php echo $category['id']; ?>">
                            <div class="row g-3">
                                <?php foreach ($menu_items as $item): ?>
                                    <?php if ($item['category_id'] == $category['id']): ?>
                                        <div class="col-md-4 col-lg-3 col-xxl-2">
                                            <div class="card menu-item-card h-100 <?php echo ($item['is_inventory_item'] && $item['current_stock'] <= 0) ? 'out-of-stock' : ''; ?>" 
                                                 data-id="<?php echo $item['id']; ?>" 
                                                 data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 data-price="<?php echo $item['price']; ?>"
                                                 data-stock="<?php echo $item['current_stock']; ?>"
                                                 data-inventory="<?php echo $item['is_inventory_item']; ?>">
                                                <?php if ($item['is_inventory_item']): ?>
                                                    <div class="stock-badge <?php echo $item['current_stock'] <= 0 ? 'bg-danger' : 'bg-success'; ?>">
                                                        <?php echo $item['current_stock'] <= 0 ? 'Out of Stock' : 'In Stock'; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <img src="<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/default-food.jpg'; ?>" 
                                                     class="card-img-top menu-item-image" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='assets/images/default-food.jpg'">
                                                <div class="card-body p-2">
                                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="card-text price mb-0">₱<?php echo number_format($item['price'], 2); ?></p>
                                                    <?php if ($item['is_inventory_item']): ?>
                                                        <small class="text-muted">Stock: <?php echo $item['current_stock']; ?></small>
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
        </div>

        <!-- Order Form (Right Side) -->
        <div class="col-lg-4">
            <div class="card mt-4 order-form">
                <div class="card-header">
                    <h5 class="mb-0">Current Order</h5>
                </div>
                <div class="card-body">
                    <div class="order-items-list mb-3">
                        <!-- Order items will be added here dynamically -->
                    </div>
                    <hr>
                    <div class="calculations">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="subtotal">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <span>Discount:</span>
                                <select class="form-select form-select-sm ms-2 discount-type" style="width: 100px;">
                                    <option value="0">None</option>
                                    <option value="senior">Senior (20%)</option>
                                    <option value="pwd">PWD (20%)</option>
                                </select>
                            </div>
                            <span class="discount-amount">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="total">₱0.00</strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount Received:</label>
                            <input type="number" class="form-control amount-received" min="0" step="0.01">
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Change:</strong>
                            <strong class="change">₱0.00</strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method:</label>
                            <select class="form-control payment-method">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes:</label>
                            <textarea class="form-control order-notes" rows="2"></textarea>
                        </div>
                        <button class="btn btn-primary w-100 complete-order-btn" disabled>
                            Complete Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.category-tabs .nav-pills .nav-link {
    color: #6c757d;
    background: none;
    border: 1px solid #dee2e6;
    margin-right: 0.5rem;
    border-radius: 20px;
    padding: 0.5rem 1.5rem;
}

.category-tabs .nav-pills .nav-link.active {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.menu-item-card {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.menu-item-card.out-of-stock {
    opacity: 0.7;
    filter: grayscale(1);
    cursor: not-allowed;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 20px;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 1;
}

.menu-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.menu-item-image {
    height: 120px;
    object-fit: cover;
}

.menu-item-card .card-body {
    text-align: center;
}

.menu-item-card .card-title {
    font-size: 0.9rem;
    line-height: 1.2;
    margin-bottom: 0.3rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.menu-item-card .price {
    color: #0d6efd;
    font-weight: bold;
    font-size: 0.95rem;
}

.order-form {
    position: sticky;
    top: 1rem;
}

.order-items-list {
    max-height: 400px;
    overflow-y: auto;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-quantity {
    width: 60px;
    text-align: center;
}

/* Alert Styles */
.alert {
    min-width: 300px;
    max-width: 80%;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.alert.position-fixed {
    margin-left: auto;
    margin-right: auto;
}

.order-item-card {
    transition: all 0.2s ease;
}

.order-item-card:hover {
    background-color: #f8f9fa !important;
}

.order-item-name {
    font-size: 0.95rem;
    color: #333;
}

.order-item-details {
    flex: 1;
    min-width: 0;
    padding-right: 10px;
}

.order-item-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.input-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.input-group input[type="number"] {
    border-left: 0;
    border-right: 0;
}

.input-group input[type="number"]::-webkit-inner-spin-button,
.input-group input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.order-items-list {
    background-color: #fff;
    border-radius: 0.25rem;
    padding: 0.5rem;
}
</style>

<script>
    // Initialize order items Map
    const orderItems = new Map();

    // Function to update order table
    function updateOrderTable() {
        const container = document.querySelector('.order-items-list');
        container.innerHTML = '';
        
        let subtotal = 0;
        
        orderItems.forEach((item, id) => {
            const itemCard = document.createElement('div');
            itemCard.className = 'order-item-card mb-2 p-2 border rounded bg-light';
            itemCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="order-item-details">
                        <h6 class="mb-0 order-item-name">${item.name}</h6>
                        <div class="text-primary">₱${item.price.toFixed(2)}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${id}, ${item.quantity - 1})">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center px-0" value="${item.quantity}" min="1" 
                            onchange="updateQuantity(${id}, this.value)" style="width: 40px">
                        <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${id}, ${item.quantity + 1})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="text-end">
                        <strong>₱${(item.price * item.quantity).toFixed(2)}</strong>
                    </div>
                </div>
            `;
            container.appendChild(itemCard);
            
            subtotal += item.price * item.quantity;
        });
        
        // Update totals
        document.querySelector('.subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        calculateTotals();
        
        // Enable/disable complete order button
        document.querySelector('.complete-order-btn').disabled = orderItems.size === 0;
    }

    // Function to add item to order
    function addToOrder(id, name, price) {
        if (orderItems.has(id)) {
            const item = orderItems.get(id);
            item.quantity++;
            orderItems.set(id, item);
        } else {
            orderItems.set(id, {
                name: name,
                price: parseFloat(price),
                quantity: 1
            });
        }
        updateOrderTable();
    }

    // Function to update item quantity
    function updateQuantity(id, newQuantity) {
        newQuantity = parseInt(newQuantity);
        if (newQuantity < 1) {
            orderItems.delete(id);
        } else {
            const item = orderItems.get(id);
            item.quantity = newQuantity;
            orderItems.set(id, item);
        }
        updateOrderTable();
    }

    // Function to remove item
    function removeItem(id) {
        orderItems.delete(id);
        updateOrderTable();
    }

    // Function to calculate totals
    function calculateTotals() {
        const subtotal = calculateSubtotal();
        const discountType = document.querySelector('.discount-type').value;
        const discount = discountType !== '0' ? subtotal * 0.2 : 0;
        const total = subtotal - discount;
        
        document.querySelector('.discount-amount').textContent = `₱${discount.toFixed(2)}`;
        document.querySelector('.total').textContent = `₱${total.toFixed(2)}`;
        
        // Calculate change if amount received is entered
        const amountReceived = parseFloat(document.querySelector('.amount-received').value) || 0;
        const change = amountReceived - total;
        document.querySelector('.change').textContent = `₱${Math.max(0, change).toFixed(2)}`;
        
        return {
            subtotal: subtotal,
            discount: discount,
            total: total,
            change: Math.max(0, change)
        };
    }

    // Function to calculate subtotal
    function calculateSubtotal() {
        let subtotal = 0;
        orderItems.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        return subtotal;
    }

    // Event listener for amount received input
    document.querySelector('.amount-received').addEventListener('input', calculateTotals);

    // Event listener for discount type change
    document.querySelector('.discount-type').addEventListener('change', calculateTotals);

    // Event listener for complete order button
    document.querySelector('.complete-order-btn').addEventListener('click', async function() {
        const totals = calculateTotals();
        const amountReceived = parseFloat(document.querySelector('.amount-received').value) || 0;

        if (!amountReceived) {
            alert('Please enter the amount received');
            document.querySelector('.amount-received').focus();
            return;
        }

        if (amountReceived < totals.total) {
            alert(`Insufficient amount. Please enter at least ₱${totals.total.toFixed(2)}`);
            document.querySelector('.amount-received').focus();
            return;
        }

        if (!confirm('Are you sure you want to complete this order?')) {
            return;
        }

        const orderData = {
            items: Array.from(orderItems.entries()).map(([id, item]) => ({
                menu_item_id: id,
                quantity: item.quantity,
                unit_price: item.price
            })),
            subtotal: totals.subtotal,
            discount_type: document.querySelector('.discount-type').value,
            discount_amount: totals.discount,
            total: totals.total,
            amount_received: amountReceived,
            change: totals.change,
            payment_method: document.querySelector('.payment-method').value,
            notes: document.querySelector('.order-notes').value
        };

        try {
            const response = await fetch('/ERC-POS/handlers/orders/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();

            if (result.success) {
                // Show receipt
                showReceipt(result.order_number, orderData);
                
                // Clear the order form
                orderItems.clear();
                updateOrderTable();
                document.querySelector('.discount-type').value = '0';
                document.querySelector('.amount-received').value = '';
                document.querySelector('.order-notes').value = '';
                document.querySelector('.payment-method').value = 'cash';
                calculateTotals();
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                alertDiv.style.zIndex = '9999';
                alertDiv.innerHTML = `
                    Order #${result.order_number} has been completed successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                // Auto-dismiss the alert after 5 seconds
                setTimeout(() => {
                    if (alertDiv && alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            } else {
                throw new Error(result.error || 'Failed to create order');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            console.error('Error:', error);
        }
    });

    // Add click event listeners to menu item cards
    document.querySelectorAll('.menu-item-card').forEach(card => {
        card.addEventListener('click', function() {
            const isInventoryItem = this.dataset.inventory === '1';
            const stock = parseInt(this.dataset.stock);
            
            if (isInventoryItem && (isNaN(stock) || stock <= 0)) {
                alert('This item is currently out of stock.');
                return;
            }
            
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = this.dataset.price;
            addToOrder(parseInt(id), name, price);
        });
    });

    // Function to show receipt
    function showReceipt(orderNumber, orderData) {
        const receiptModal = document.createElement('div');
        receiptModal.className = 'modal fade';
        receiptModal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Receipt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <h4>ERC Restaurant</h4>
                            <p class="mb-1">Order #${orderNumber}</p>
                            <p class="mb-1">${new Date().toLocaleString()}</p>
                        </div>
                        <div class="border-top border-bottom py-3 mb-3">
                            ${Array.from(orderItems.entries()).map(([id, item]) => `
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <div>${item.name}</div>
                                        <div class="text-muted small">₱${item.price.toFixed(2)} × ${item.quantity}</div>
                                    </div>
                                    <div>₱${(item.price * item.quantity).toFixed(2)}</div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>₱${orderData.subtotal.toFixed(2)}</span>
                            </div>
                            ${orderData.discount_amount > 0 ? `
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount (${orderData.discount_type}):</span>
                                    <span>-₱${orderData.discount_amount.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Total:</strong>
                                <strong>₱${orderData.total.toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Amount Received:</span>
                                <span>₱${orderData.amount_received.toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Change:</span>
                                <span>₱${orderData.change.toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="mb-1">Thank you for dining with us!</p>
                            <p class="small text-muted mb-0">Please come again</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(receiptModal);
        
        const modal = new bootstrap.Modal(receiptModal);
        modal.show();
        
        receiptModal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(receiptModal);
        });
    }

    // Add print styles
    const printStyles = document.createElement('style');
    printStyles.textContent = `
        @media print {
            body * {
                visibility: hidden;
            }
            .modal-content * {
                visibility: visible;
            }
            .modal-content {
                position: absolute;
                left: 0;
                top: 0;
            }
            .modal-footer {
                display: none;
            }
        }
    `;

    document.head.appendChild(printStyles);
</script>

<?php include 'static/templates/footer.php'; ?> 