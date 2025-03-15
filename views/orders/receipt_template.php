<div id="receipt" class="receipt-container">
    <div class="receipt-header text-center">
        <?php if (isset($settings['show_receipt_logo']) && $settings['show_receipt_logo']): ?>
        <img src="/ERC-POS/assets/images/ERC Logo.png" alt="Business Logo" class="receipt-logo">
        <?php endif; ?>
        <h4 class="business-name"><?php echo htmlspecialchars($settings['business_name'] ?? 'ERC Carinderia'); ?></h4>
        <?php if (!empty($settings['business_address'])): ?>
        <p class="business-address"><?php echo nl2br(htmlspecialchars($settings['business_address'])); ?></p>
        <?php endif; ?>
        <?php if (!empty($settings['business_phone'])): ?>
        <p class="business-phone"><?php echo htmlspecialchars($settings['business_phone']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="receipt-info">
        <div class="receipt-row">
            <span class="receipt-label">Order #:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($order['order_number']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Date:</span>
            <span class="receipt-value"><?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Cashier:</span>
            <span class="receipt-value"><?php echo htmlspecialchars($order['created_by_name']); ?></span>
        </div>
    </div>
    
    <div class="receipt-divider"></div>
    
    <div class="receipt-items">
        <table class="receipt-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Price</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td class="item-name"><?php echo htmlspecialchars($item['menu_item_name']); ?></td>
                    <td class="item-qty"><?php echo $item['quantity']; ?></td>
                    <td class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="item-total">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="receipt-divider"></div>
    
    <div class="receipt-totals">
        <div class="receipt-row">
            <span class="receipt-label">Subtotal:</span>
            <span class="receipt-value">₱<?php echo number_format($order['subtotal_amount'], 2); ?></span>
        </div>
        
        <?php if ($order['discount_amount'] > 0): ?>
        <div class="receipt-row">
            <span class="receipt-label">Discount <?php echo $order['discount_type'] ? '(' . ucfirst($order['discount_type']) . ')' : ''; ?>:</span>
            <span class="receipt-value">₱<?php echo number_format($order['discount_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="receipt-row total-row">
            <span class="receipt-label">Total:</span>
            <span class="receipt-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        
        <div class="receipt-row">
            <span class="receipt-label">Cash:</span>
            <span class="receipt-value">₱<?php echo number_format($order['cash_received'], 2); ?></span>
        </div>
        
        <div class="receipt-row">
            <span class="receipt-label">Change:</span>
            <span class="receipt-value">₱<?php echo number_format($order['cash_change'], 2); ?></span>
        </div>
    </div>
    
    <div class="receipt-footer text-center">
        <p><?php echo htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for your business!'); ?></p>
    </div>
</div> 