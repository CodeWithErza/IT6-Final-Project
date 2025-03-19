<?php
require_once __DIR__ . '/../../helpers/functions.php';
include __DIR__ . '/../../static/templates/header.php';

// Get all categories
$stmt = $conn->prepare("
    SELECT * FROM categories 
    WHERE is_active = 1
    ORDER BY name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get menu items with stock info
$stmt = $conn->prepare("CALL sp_get_menu_items_for_sale()");
$stmt->execute();
$menu_items = $stmt->fetchAll();

// Get business settings for receipt
$stmt = $conn->prepare("
    SELECT setting_name, setting_value 
    FROM settings 
    WHERE setting_group IN ('business', 'system', 'receipt')
");
$stmt->execute();
$settings_result = $stmt->fetchAll();

// Convert settings to associative array
$settings = [];
foreach ($settings_result as $setting) {
    $settings[$setting['setting_name']] = $setting['setting_value'];
}

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
            <div class="category-tabs mt-2">
                <ul class="nav nav-pills mb-3" id="menuTabs" role="tablist">
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
                                        <img src="<?php 
                                            $image_path = !empty($item['image_path']) ? $item['image_path'] : 'assets/images/default-food.jpg';
                                            // Add leading slash if not present
                                            if (strpos($image_path, '/') !== 0) {
                                                $image_path = '/' . $image_path;
                                            }
                                            echo '/ERC-POS' . $image_path;
                                        ?>" 
                                             class="card-img-top menu-item-image" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             onerror="this.src='/ERC-POS/assets/images/default-food.jpg'">
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
                                                <img src="<?php 
                                                    $image_path = !empty($item['image_path']) ? $item['image_path'] : 'assets/images/default-food.jpg';
                                                    // Add leading slash if not present
                                                    if (strpos($image_path, '/') !== 0) {
                                                        $image_path = '/' . $image_path;
                                                    }
                                                    echo '/ERC-POS' . $image_path;
                                                ?>" 
                                                     class="card-img-top menu-item-image" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='/ERC-POS/assets/images/default-food.jpg'">
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
            <div class="card mt-2 order-form">
                <div class="card-header">
                    <h5 class="mb-0">Current Order</h5>
                </div>
                <div class="card-body">
                    <div class="order-items-list mb-2">
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
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong class="total">₱0.00</strong>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1">Amount Received:</label>
                            <input type="number" class="form-control amount-received" min="0" step="0.01">
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                            <strong>Change:</strong>
                            <strong class="change">₱0.00</strong>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1">Payment Method:</label>
                            <select class="form-control payment-method">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="card">Card</option>
                            </select>
                    </div>
                        <div class="mb-2">
                            <label class="form-label mb-1">Notes:</label>
                            <textarea class="form-control order-notes" rows="1" placeholder="Optional order notes"></textarea>
                    </div>
                        <button class="btn btn-primary w-100 complete-order-btn mt-2" disabled>
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
    padding: 0.4rem 1.2rem;
    transition: all 0.3s ease;
}

.category-tabs .nav-pills .nav-link:hover {
    color: var(--primary-color);
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.category-tabs .nav-pills .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    border-color: var(--accent-color);
    box-shadow: 0 4px 10px rgba(155, 29, 166, 0.3);
}

.menu-item-card {
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    border: none;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
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
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.stock-badge.bg-danger {
    background-color: var(--primary-color) !important;
}

.stock-badge.bg-success {
    background-color: #28a745 !important;
}

.menu-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
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
    color: #333;
}

.menu-item-card .price {
    color: var(--accent-color);
    font-weight: bold;
    font-size: 0.95rem;
}

.order-form {
    position: sticky;
    top: 1rem;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: none;
    margin-top: 0.5rem;
}

.order-form .card-header {
    background-color: var(--primary-color);
    color: white;
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.order-form .card-header h5 {
    margin: 0;
    font-weight: 600;
}

.order-items-list {
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--secondary-color) rgba(0, 0, 0, 0.05);
    background-color: #fff;
    border-radius: 0.25rem;
    padding: 0.5rem;
}

.order-items-list::-webkit-scrollbar {
    width: 6px;
}

.order-items-list::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

.order-items-list::-webkit-scrollbar-thumb {
    background-color: var(--secondary-color);
    border-radius: 10px;
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

.order-item-card {
    transition: all 0.3s ease;
    border-left: 3px solid var(--secondary-color);
    margin-bottom: 0.5rem;
    padding: 0.5rem !important;
}

.order-item-card:last-child {
    margin-bottom: 0;
}

.order-item-card:hover {
    background-color: rgba(242, 174, 174, 0.1) !important;
    transform: translateX(5px);
}

.order-item-card .d-flex {
    margin-bottom: 0.25rem !important;
}

.order-item-card .d-flex:last-child {
    margin-bottom: 0 !important;
}

.order-item-name {
    font-size: 0.9rem;
    color: #333;
    font-weight: 600;
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
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.input-group .btn:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.input-group input[type="number"] {
    border-left: 0;
    border-right: 0;
    border-color: var(--primary-color);
}

.input-group input[type="number"]:focus {
    box-shadow: none;
    border-color: var(--accent-color);
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

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(210, 102, 95, 0.25);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.6rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover, .btn-primary:focus {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(155, 29, 166, 0.3);
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
    opacity: 0.65;
}

.calculations {
    padding: 0.5rem;
}

.calculations .total, .calculations .change {
    color: var(--accent-color);
    font-size: 1.1rem;
}

.form-label {
    font-weight: 600;
    color: #555;
    margin-bottom: 0.5rem;
}

.order-form .card-body {
    padding: 1rem;
}

.order-form .form-label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.order-form .form-control, 
.order-form .form-select {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
}

.order-form textarea.form-control {
    min-height: 38px;
    resize: none;
}

.order-form .mb-3 {
    margin-bottom: 0.75rem !important;
}

.order-form .mb-2 {
    margin-bottom: 0.5rem !important;
}

.order-form hr {
    margin: 0.5rem 0;
}

/* Alert Styles */
.alert {
    min-width: 300px;
    max-width: 80%;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    margin-bottom: 0.5rem;
    margin-top: 0.5rem;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.15);
    color: #28a745;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: rgba(210, 102, 95, 0.15);
    color: var(--primary-color);
    border-left: 4px solid var(--primary-color);
}

.alert.position-fixed {
    margin-left: auto;
    margin-right: auto;
    top: 70px;
}
</style>

<script>
    // Business settings for receipt
    const businessSettings = <?php echo json_encode($settings); ?>;

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
                        <div class="text-primary small">₱${item.price.toFixed(2)}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeItem(${id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="input-group input-group-sm" style="width: 100px;">
                        <button type="button" class="btn btn-outline-secondary py-0" onclick="updateQuantity(${id}, ${item.quantity - 1})">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center px-0" value="${item.quantity}" min="1" 
                            onchange="updateQuantity(${id}, this.value)" style="width: 40px">
                        <button type="button" class="btn btn-outline-secondary py-0" onclick="updateQuantity(${id}, ${item.quantity + 1})">
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
        
        // If no items, clear amount received and reset change
        if (orderItems.size === 0) {
            document.querySelector('.amount-received').value = '';
            document.querySelector('.change').textContent = '₱0.00';
            document.querySelector('.change').classList.remove('text-danger');
            document.querySelector('.amount-received').classList.remove('border-danger');
            document.querySelector('.complete-order-btn').disabled = true;
        }
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
        
        // Show negative change if amount received is less than total
        document.querySelector('.change').textContent = `₱${change.toFixed(2)}`;
        
        // Update complete order button state
        const completeOrderBtn = document.querySelector('.complete-order-btn');
        completeOrderBtn.disabled = orderItems.size === 0 || amountReceived < total;
        
        // Add visual feedback for insufficient amount
        if (amountReceived > 0 && amountReceived < total) {
            document.querySelector('.change').classList.add('text-danger');
            document.querySelector('.amount-received').classList.add('border-danger');
        } else {
            document.querySelector('.change').classList.remove('text-danger');
            document.querySelector('.amount-received').classList.remove('border-danger');
        }
        
        return {
            subtotal: subtotal,
            discount: discount,
            total: total,
            change: change
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
        // If button is disabled, don't proceed
        if (this.disabled) {
            return;
        }
        
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

        // Check session status before proceeding
        try {
            const sessionCheck = await fetch('/ERC-POS/handlers/auth/check_session.php');
            const sessionStatus = await sessionCheck.json();
            
            if (!sessionStatus.logged_in) {
                alert('Your session has expired. Please log in again.');
                window.location.href = '/ERC-POS/views/auth/login.php';
                return;
            }
        } catch (error) {
            console.error('Session check error:', error);
            // Continue anyway, the server will handle authentication
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
                // Get response text to see if there's any error message
                const responseText = await response.text();
                console.error('Server response:', responseText);
                
                // Try to parse as JSON if possible
                try {
                    const errorData = JSON.parse(responseText);
                    throw new Error(errorData.error || `Server error: ${response.status} ${response.statusText}`);
                } catch (parseError) {
                    // If not valid JSON, use the response text or status
                    throw new Error(`Server error: ${response.status} ${response.statusText}. Details: ${responseText.substring(0, 100)}...`);
                }
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
            console.error('Error details:', error);
            
            // Create a more detailed error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <strong>Error:</strong> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto-dismiss the alert after 10 seconds
            setTimeout(() => {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 10000);
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
        // Get business settings from a global variable or fetch them
        // For now, we'll use default values
        const settings = {
            business_name: 'ERC Carinderia',
            business_address: '',
            business_phone: '',
            receipt_footer: 'Thank you for dining with us!'
        };

        // Try to get settings from PHP if available
        if (typeof businessSettings !== 'undefined') {
            Object.assign(settings, businessSettings);
        }

        const receiptModal = document.createElement('div');
        receiptModal.className = 'modal fade';
        receiptModal.id = 'dynamicReceiptModal';
        
        // Format date
        const orderDate = new Date();
        const formattedDate = orderDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        // Create receipt HTML
        receiptModal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Receipt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="receipt-content">
                            <div class="text-center mb-3">
                                <img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" style="max-width: 80px; margin-bottom: 10px;">
                                <h4>${settings.business_name}</h4>
                                ${settings.business_address ? `<p class="mb-1">${settings.business_address}</p>` : ''}
                                ${settings.business_phone ? `<p class="mb-1">${settings.business_phone}</p>` : ''}
                                <p class="mb-1">Order #${orderNumber}</p>
                                <p class="mb-1">${formattedDate}</p>
                                <p class="mb-1">Cashier: ${document.querySelector('.user-profile .dropdown-toggle span')?.textContent || 'Staff'}</p>
                            </div>
                            <div class="border-top border-bottom py-3 mb-3">
                                ${Array.from(orderItems.entries()).map(([id, item]) => `
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <div>${item.name}</div>
                                            <div class="text-muted small">₱${parseFloat(item.price).toFixed(2)} × ${item.quantity}</div>
                                        </div>
                                        <div>₱${(parseFloat(item.price) * item.quantity).toFixed(2)}</div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₱${parseFloat(orderData.subtotal).toFixed(2)}</span>
                                </div>
                                ${parseFloat(orderData.discount_amount) > 0 ? `
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Discount ${orderData.discount_type ? `(${orderData.discount_type})` : ''}:</span>
                                        <span>-₱${parseFloat(orderData.discount_amount).toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Total:</strong>
                                    <strong>₱${parseFloat(orderData.total).toFixed(2)}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Amount Received:</span>
                                    <span>₱${parseFloat(orderData.amount_received).toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Change:</span>
                                    <span>₱${parseFloat(orderData.change).toFixed(2)}</span>
                                </div>
                                ${orderData.payment_method !== 'cash' ? `
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>Payment Method:</span>
                                        <span>${orderData.payment_method.toUpperCase()}</span>
                                    </div>
                                ` : ''}
                                ${orderData.notes ? `
                                    <div class="mt-2">
                                        <span>Notes:</span>
                                        <p class="small mt-1">${orderData.notes}</p>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="text-center">
                                <p class="mb-1">${settings.receipt_footer}</p>
                                <p class="small text-muted mb-0">Please come again</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="printReceiptBtn">
                            <i class="fas fa-print me-2"></i>Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(receiptModal);
        
        const modal = new bootstrap.Modal(receiptModal);
        modal.show();
        
        // Print receipt
        document.getElementById('printReceiptBtn').addEventListener('click', function() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Order Receipt</title>
                        <style>
                            body {
                                font-family: 'Courier New', monospace;
                                font-size: 12px;
                                line-height: 1.4;
                                margin: 0;
                                padding: 20px;
                            }
                            .receipt-container {
                                width: 80mm;
                                margin: 0 auto;
                            }
                            .text-center {
                                text-align: center;
                            }
                            .mb-1 {
                                margin-bottom: 5px;
                            }
                            .mb-2 {
                                margin-bottom: 10px;
                            }
                            .mb-3 {
                                margin-bottom: 15px;
                            }
                            .mt-1 {
                                margin-top: 5px;
                            }
                            .mt-2 {
                                margin-top: 10px;
                            }
                            .py-3 {
                                padding-top: 15px;
                                padding-bottom: 15px;
                            }
                            .border-top {
                                border-top: 1px dashed #ccc;
                            }
                            .border-bottom {
                                border-bottom: 1px dashed #ccc;
                            }
                            .d-flex {
                                display: flex;
                            }
                            .justify-content-between {
                                justify-content: space-between;
                            }
                            .text-muted {
                                color: #6c757d;
                            }
                            .small {
                                font-size: 10px;
                            }
                            img {
                                max-width: 80px;
                                height: auto;
                                margin-bottom: 10px;
                            }
                            h4 {
                                font-size: 16px;
                                margin: 5px 0;
                            }
                            p {
                                margin: 3px 0;
                            }
                            strong {
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="receipt-container">
                            ${document.getElementById('receipt-content').innerHTML}
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        });
        
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
            #dynamicReceiptModal .modal-content * {
                visibility: visible;
            }
            #dynamicReceiptModal .modal-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            #dynamicReceiptModal .modal-footer {
                display: none;
            }
        }
    `;

    document.head.appendChild(printStyles);
</script>

<?php include __DIR__ . '/../../static/templates/footer.php'; ?> 