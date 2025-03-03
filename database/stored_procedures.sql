DELIMITER //

-- Get inventory transactions by date range
CREATE PROCEDURE sp_get_inventory_transactions(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_menu_item_id INT
)
BEGIN
    SELECT 
        it.*,
        mi.name as item_name,
        u.username
    FROM inventory_transactions it
    JOIN menu_items mi ON it.menu_item_id = mi.id
    JOIN users u ON it.created_by = u.id
    WHERE DATE(it.created_at) BETWEEN p_start_date AND p_end_date
    AND (p_menu_item_id IS NULL OR it.menu_item_id = p_menu_item_id)
    ORDER BY it.created_at DESC;
END //

-- Get sales report by date range
CREATE PROCEDURE sp_get_sales_report(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        DATE(o.created_at) as sale_date,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.total_amount) as total_sales,
        SUM(oi.quantity) as total_items_sold
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN p_start_date AND p_end_date
    AND o.status = 'completed'
    GROUP BY DATE(o.created_at)
    ORDER BY sale_date;
END //

-- Get expenses report by date range
CREATE PROCEDURE sp_get_expenses_report(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        DATE(expense_date) as expense_date,
        SUM(amount) as total_amount,
        COUNT(*) as transaction_count
    FROM expenses
    WHERE expense_date BETWEEN p_start_date AND p_end_date
    GROUP BY DATE(expense_date)
    ORDER BY expense_date;
END //

-- Update inventory stock
CREATE PROCEDURE sp_update_inventory_stock(
    IN p_menu_item_id INT,
    IN p_quantity INT,
    IN p_transaction_type VARCHAR(20),
    IN p_user_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock
    SELECT current_stock INTO current_stock
    FROM menu_items
    WHERE id = p_menu_item_id;
    
    -- Update stock based on transaction type
    IF p_transaction_type = 'stock_in' THEN
        UPDATE menu_items 
        SET current_stock = current_stock + p_quantity
        WHERE id = p_menu_item_id;
    ELSEIF p_transaction_type = 'stock_out' THEN
        UPDATE menu_items 
        SET current_stock = current_stock - p_quantity
        WHERE id = p_menu_item_id;
    ELSE -- adjustment
        UPDATE menu_items 
        SET current_stock = p_quantity
        WHERE id = p_menu_item_id;
    END IF;
    
    -- Record transaction
    INSERT INTO inventory_transactions (
        menu_item_id, 
        transaction_type, 
        quantity, 
        notes, 
        created_by
    ) VALUES (
        p_menu_item_id,
        p_transaction_type,
        p_quantity,
        p_notes,
        p_user_id
    );
END //

DELIMITER ; 