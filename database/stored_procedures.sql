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
-- This procedure has been removed as we now track inventory expenses through inventory_transactions
-- CREATE PROCEDURE sp_get_expenses_report(
--     IN p_start_date DATE,
--     IN p_end_date DATE
-- )
-- BEGIN
--     SELECT 
--         DATE(expense_date) as expense_date,
--         SUM(amount) as total_amount,
--         COUNT(*) as transaction_count
--     FROM expenses
--     WHERE expense_date BETWEEN p_start_date AND p_end_date
--     GROUP BY DATE(expense_date)
--     ORDER BY expense_date;
-- END //

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
    ELSEIF p_transaction_type = 'adjustment' THEN
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

-- Get menu items by category with stock info
CREATE PROCEDURE sp_get_menu_items_by_category(
    IN p_category_id INT
)
BEGIN
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
    AND (p_category_id IS NULL OR m.category_id = p_category_id)
    AND (m.category_id IS NULL OR c.is_active = 1)
    ORDER BY m.name;
END //

-- Get detailed sales report by date range
CREATE PROCEDURE sp_get_detailed_sales_report(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_category_id INT
)
BEGIN
    SELECT 
        DATE(o.created_at) as sale_date,
        m.name as item_name,
        c.name as category_name,
        SUM(oi.quantity) as quantity_sold,
        SUM(oi.subtotal) as total_sales,
        COUNT(DISTINCT o.id) as order_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items m ON oi.menu_item_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE DATE(o.created_at) BETWEEN p_start_date AND p_end_date
    AND o.status = 'completed'
    AND (p_category_id IS NULL OR m.category_id = p_category_id)
    GROUP BY DATE(o.created_at), m.id
    ORDER BY sale_date, total_sales DESC;
END //

-- Get low stock items
CREATE PROCEDURE sp_get_low_stock_items(
    IN p_threshold INT
)
BEGIN
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
    AND m.is_inventory_item = 1
    AND (
        SELECT COALESCE(SUM(
            CASE 
                WHEN transaction_type = 'stock_in' OR transaction_type = 'adjustment' THEN quantity
                WHEN transaction_type = 'stock_out' THEN -quantity
            END
        ), 0)
        FROM inventory_transactions 
        WHERE menu_item_id = m.id
    ) <= p_threshold
    ORDER BY current_stock;
END //

-- Get top selling items
CREATE PROCEDURE sp_get_top_selling_items(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_limit INT
)
BEGIN
    SELECT 
        m.id,
        m.name,
        c.name as category_name,
        SUM(oi.quantity) as total_quantity_sold,
        SUM(oi.subtotal) as total_sales,
        COUNT(DISTINCT o.id) as order_count
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    JOIN order_items oi ON m.id = oi.menu_item_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN p_start_date AND p_end_date
    GROUP BY m.id
    ORDER BY total_quantity_sold DESC
    LIMIT p_limit;
END //

-- Get order details
CREATE PROCEDURE sp_get_order_details(
    IN p_order_id INT
)
BEGIN
    -- Get order header
    SELECT 
        o.*,
        u.username as created_by_name
    FROM orders o
    LEFT JOIN users u ON o.created_by = u.id
    WHERE o.id = p_order_id;
    
    -- Get order items
    SELECT 
        oi.*,
        m.name as menu_item_name,
        c.name as category_name
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE oi.order_id = p_order_id;
END //

-- Get user activity log
CREATE PROCEDURE sp_get_user_activity(
    IN p_user_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        al.*,
        u.username,
        u.full_name
    FROM audit_log al
    JOIN users u ON al.user_id = u.id
    WHERE (p_user_id IS NULL OR al.user_id = p_user_id)
    AND DATE(al.created_at) BETWEEN p_start_date AND p_end_date
    ORDER BY al.created_at DESC;
END //

-- Get menu items for sales order with stock info and active categories
CREATE PROCEDURE sp_get_menu_items_for_sale()
BEGIN
    -- Add index hint for better performance
    SELECT 
        m.*,
        c.name as category_name,
        c.is_active as category_active,
        COALESCE(
            (SELECT SUM(
                CASE 
                    WHEN transaction_type = 'stock_in' OR transaction_type = 'adjustment' THEN quantity
                    WHEN transaction_type = 'stock_out' THEN -quantity
                END
            )
            FROM inventory_transactions USE INDEX (menu_item_id_idx)
            WHERE menu_item_id = m.id
            ), 0
        ) as current_stock,
        (SELECT setting_value FROM settings WHERE setting_name = 'low_stock_threshold') as low_stock_threshold
    FROM menu_items m USE INDEX (category_id_idx)
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.is_active = 1 
    AND (m.category_id IS NULL OR c.is_active = 1)
    ORDER BY c.name, m.name;
END //

-- Get active categories with item counts
CREATE PROCEDURE sp_get_active_categories()
BEGIN
    SELECT 
        c.*,
        COUNT(m.id) as item_count,
        SUM(CASE WHEN m.is_inventory_item = 1 THEN 1 ELSE 0 END) as inventory_item_count
    FROM categories c
    LEFT JOIN menu_items m ON c.id = m.category_id AND m.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.name;
END //

-- Create new order with items (Enhanced with validation)
CREATE PROCEDURE sp_create_order(
    IN p_user_id INT,
    IN p_total_amount DECIMAL(10,2),
    IN p_subtotal_amount DECIMAL(10,2),
    IN p_discount_type VARCHAR(20),
    IN p_discount_amount DECIMAL(10,2),
    IN p_cash_received DECIMAL(10,2),
    IN p_cash_change DECIMAL(10,2),
    IN p_payment_method VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    DECLARE new_order_id INT;
    DECLARE new_order_number VARCHAR(50);
    
    -- Validate inputs
    IF p_total_amount < 0 OR p_subtotal_amount < 0 OR p_cash_received < 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Invalid amount values';
    END IF;
    
    -- Generate order number with better uniqueness
    SET new_order_number = CONCAT(
        DATE_FORMAT(NOW(), '%Y%m%d'),
        '-',
        LPAD((SELECT COALESCE(MAX(SUBSTRING_INDEX(order_number, '-', -1)) + 1, 1)
              FROM orders 
              WHERE DATE(created_at) = CURDATE()), 4, '0')
    );
    
    -- Create order with transaction
    START TRANSACTION;
    
    INSERT INTO orders (
        order_number,
        user_id,
        total_amount,
        subtotal_amount,
        discount_type,
        discount_amount,
        cash_received,
        cash_change,
        payment_method,
        status,
        notes,
        created_by
    ) VALUES (
        new_order_number,
        p_user_id,
        p_total_amount,
        p_subtotal_amount,
        p_discount_type,
        p_discount_amount,
        p_cash_received,
        p_cash_change,
        p_payment_method,
        'completed',
        p_notes,
        p_user_id
    );
    
    SET new_order_id = LAST_INSERT_ID();
    
    -- Log the order creation
    INSERT INTO audit_log (
        user_id,
        action,
        table_name,
        record_id,
        new_values,
        created_by
    ) VALUES (
        p_user_id,
        'create',
        'orders',
        new_order_id,
        JSON_OBJECT(
            'order_number', new_order_number,
            'total_amount', p_total_amount,
            'payment_method', p_payment_method
        ),
        p_user_id
    );
    
    COMMIT;
    
    -- Return the order details
    SELECT new_order_id as order_id, new_order_number as order_number;
END //

-- Add order items and update inventory (Enhanced with stock validation)
CREATE PROCEDURE sp_add_order_items(
    IN p_order_id INT,
    IN p_menu_item_id INT,
    IN p_quantity INT,
    IN p_unit_price DECIMAL(10,2),
    IN p_user_id INT
)
BEGIN
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_is_inventory_item BOOLEAN;
    DECLARE v_current_stock INT;
    DECLARE v_item_name VARCHAR(100);
    
    -- Get item details
    SELECT 
        name,
        is_inventory_item,
        current_stock 
    INTO 
        v_item_name,
        v_is_inventory_item,
        v_current_stock
    FROM menu_items
    WHERE id = p_menu_item_id;
    
    -- Validate stock if it's an inventory item
    IF v_is_inventory_item AND v_current_stock < p_quantity THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insufficient stock';
    END IF;
    
    -- Calculate subtotal
    SET v_subtotal = p_quantity * p_unit_price;
    
    START TRANSACTION;
    
    -- Insert order item
    INSERT INTO order_items (
        order_id,
        menu_item_id,
        quantity,
        unit_price,
        subtotal,
        created_by
    ) VALUES (
        p_order_id,
        p_menu_item_id,
        p_quantity,
        p_unit_price,
        v_subtotal,
        p_user_id
    );
    
    -- Update inventory if it's an inventory item
    IF v_is_inventory_item THEN
        -- Create inventory transaction
        INSERT INTO inventory_transactions (
            menu_item_id,
            transaction_type,
            quantity,
            notes,
            created_by
        ) VALUES (
            p_menu_item_id,
            'stock_out',
            p_quantity,
            CONCAT('Order #', p_order_id),
            p_user_id
        );
        
        -- Update current stock with validation
        UPDATE menu_items 
        SET 
            current_stock = current_stock - p_quantity,
            updated_at = CURRENT_TIMESTAMP,
            updated_by = p_user_id
        WHERE id = p_menu_item_id
        AND current_stock >= p_quantity;
        
        -- Check if update was successful
        IF ROW_COUNT() = 0 THEN
            ROLLBACK;
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Stock update failed';
        END IF;
    END IF;
    
    COMMIT;
END //

-- Get daily sales summary (Enhanced with more metrics)
CREATE PROCEDURE sp_get_daily_sales_summary(
    IN p_date DATE
)
BEGIN
    -- Sales summary by payment method
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        SUM(discount_amount) as total_discounts,
        COUNT(DISTINCT user_id) as total_cashiers,
        payment_method,
        COUNT(*) as payment_count,
        MIN(total_amount) as min_order_amount,
        MAX(total_amount) as max_order_amount,
        AVG(total_amount) as avg_order_amount,
        SUM(CASE WHEN discount_amount > 0 THEN 1 ELSE 0 END) as discounted_orders
    FROM orders
    WHERE DATE(created_at) = p_date
    AND status = 'completed'
    GROUP BY payment_method;
    
    -- Hourly sales breakdown
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders
    WHERE DATE(created_at) = p_date
    AND status = 'completed'
    GROUP BY HOUR(created_at)
    ORDER BY hour;
    
    -- Top selling items with category performance
    SELECT 
        m.name,
        c.name as category,
        SUM(oi.quantity) as quantity_sold,
        SUM(oi.subtotal) as total_sales,
        COUNT(DISTINCT o.id) as order_count,
        SUM(oi.subtotal) / SUM(oi.quantity) as avg_unit_price,
        CASE 
            WHEN m.is_inventory_item = 1 THEN m.current_stock
            ELSE NULL
        END as current_stock
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menu_items m ON oi.menu_item_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE DATE(o.created_at) = p_date
    AND o.status = 'completed'
    GROUP BY m.id
    ORDER BY quantity_sold DESC
    LIMIT 10;
END //

-- Update category status (Enhanced with menu item handling)
CREATE PROCEDURE sp_update_category_status(
    IN p_category_id INT,
    IN p_is_active BOOLEAN,
    IN p_user_id INT
)
BEGIN
    DECLARE v_category_name VARCHAR(50);
    
    START TRANSACTION;
    
    -- Get category name
    SELECT name INTO v_category_name
    FROM categories
    WHERE id = p_category_id;
    
    -- Update category
    UPDATE categories 
    SET 
        is_active = p_is_active,
        updated_by = p_user_id,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_category_id;
    
    -- Log the change
    INSERT INTO audit_log (
        user_id,
        action,
        table_name,
        record_id,
        old_values,
        new_values,
        created_by
    ) VALUES (
        p_user_id,
        CASE WHEN p_is_active = 1 THEN 'activate' ELSE 'deactivate' END,
        'categories',
        p_category_id,
        JSON_OBJECT(
            'name', v_category_name,
            'is_active', !p_is_active
        ),
        JSON_OBJECT(
            'name', v_category_name,
            'is_active', p_is_active
        ),
        p_user_id
    );
    
    COMMIT;
END //

-- Get low stock alerts (Enhanced with trend analysis)
CREATE PROCEDURE sp_get_low_stock_alerts()
BEGIN
    DECLARE v_threshold INT;
    
    -- Get threshold from settings
    SELECT CAST(setting_value AS SIGNED) INTO v_threshold
    FROM settings 
    WHERE setting_name = 'low_stock_threshold';
    
    -- Get low stock items with usage trends
    SELECT 
        m.id,
        m.name,
        c.name as category_name,
        m.current_stock,
        v_threshold as threshold,
        (
            SELECT SUM(quantity)
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.menu_item_id = m.id
            AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        ) as weekly_usage,
        CASE
            WHEN m.current_stock = 0 THEN 'Out of Stock'
            WHEN m.current_stock <= v_threshold * 0.5 THEN 'Critical'
            ELSE 'Low'
        END as status
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.is_active = 1 
    AND m.is_inventory_item = 1
    AND m.current_stock <= v_threshold
    ORDER BY 
        CASE
            WHEN m.current_stock = 0 THEN 1
            WHEN m.current_stock <= v_threshold * 0.5 THEN 2
            ELSE 3
        END,
        m.current_stock;
END //

DELIMITER ; 