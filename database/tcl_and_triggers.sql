/*
 * TCL (Transaction Control Language) and Triggers for ERC-POS System
 * This file contains:
 * 1. Transaction Management Procedures
 * 2. Automatic Triggers for Data Integrity
 * 3. Performance Optimization Indexes
 */

-- =============================================
-- TCL Procedures for Transaction Management
-- These procedures help manage database transactions
-- with support for savepoints for fine-grained control
-- =============================================

DELIMITER //

-- Procedure: sp_begin_transaction
-- Purpose: Starts a new transaction with optional savepoint
-- Parameters:
--   savepoint_name: Optional name for a savepoint within the transaction
-- Usage: CALL sp_begin_transaction('my_savepoint');
CREATE PROCEDURE sp_begin_transaction(IN savepoint_name VARCHAR(50))
BEGIN
    START TRANSACTION;
    IF savepoint_name IS NOT NULL THEN
        SET @savepoint_sql = CONCAT('SAVEPOINT ', savepoint_name);
        PREPARE stmt FROM @savepoint_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

-- Procedure: sp_rollback_to_savepoint
-- Purpose: Rolls back a transaction to a specific savepoint or completely
-- Parameters:
--   savepoint_name: Optional savepoint name to roll back to
-- Usage: CALL sp_rollback_to_savepoint('my_savepoint');
CREATE PROCEDURE sp_rollback_to_savepoint(IN savepoint_name VARCHAR(50))
BEGIN
    IF savepoint_name IS NOT NULL THEN
        SET @rollback_sql = CONCAT('ROLLBACK TO ', savepoint_name);
        PREPARE stmt FROM @rollback_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    ELSE
        ROLLBACK;
    END IF;
END //

-- Procedure: sp_commit_transaction
-- Purpose: Commits the current transaction
-- Usage: CALL sp_commit_transaction();
CREATE PROCEDURE sp_commit_transaction()
BEGIN
    COMMIT;
END //

-- =============================================
-- Triggers for Menu Items Table
-- These triggers maintain data integrity and audit logging
-- for menu item changes
-- =============================================

-- Trigger: before_menu_item_update
-- Purpose: Logs price and status changes for menu items
-- When: BEFORE UPDATE on menu_items table
-- Actions: 
--   1. Logs price changes to audit_log
--   2. Logs status changes to audit_log
CREATE TRIGGER before_menu_item_update
BEFORE UPDATE ON menu_items
FOR EACH ROW
BEGIN
    -- Log price changes
    IF OLD.price != NEW.price THEN
        INSERT INTO audit_log (
            user_id,
            action,
            table_name,
            record_id,
            old_values,
            new_values,
            created_at
        ) VALUES (
            NEW.updated_by,
            'price_change',
            'menu_items',
            NEW.id,
            JSON_OBJECT('price', OLD.price),
            JSON_OBJECT('price', NEW.price),
            NOW()
        );
    END IF;
    
    -- Log status changes
    IF OLD.is_active != NEW.is_active THEN
        INSERT INTO audit_log (
            user_id,
            action,
            table_name,
            record_id,
            old_values,
            new_values,
            created_at
        ) VALUES (
            NEW.updated_by,
            'status_change',
            'menu_items',
            NEW.id,
            JSON_OBJECT('is_active', OLD.is_active),
            JSON_OBJECT('is_active', NEW.is_active),
            NOW()
        );
    END IF;
END //

-- =============================================
-- Trigger for Inventory Transactions
-- Maintains stock levels and generates alerts
-- =============================================

-- Trigger: after_inventory_transaction
-- Purpose: Updates stock levels and creates low stock alerts
-- When: AFTER INSERT on inventory_transactions table
-- Actions:
--   1. Updates menu item current stock
--   2. Generates low stock alerts if needed
CREATE TRIGGER after_inventory_transaction
AFTER INSERT ON inventory_transactions
FOR EACH ROW
BEGIN
    -- Update menu item stock based on transaction type
    UPDATE menu_items 
    SET current_stock = (
        SELECT COALESCE(SUM(
            CASE 
                WHEN transaction_type = 'stock_in' THEN quantity
                WHEN transaction_type = 'stock_out' THEN -quantity
                WHEN transaction_type = 'adjustment' AND notes LIKE '%Increase%' THEN quantity
                WHEN transaction_type = 'adjustment' AND notes LIKE '%Decrease%' THEN -quantity
                ELSE quantity
            END
        ), 0)
        FROM inventory_transactions
        WHERE menu_item_id = NEW.menu_item_id
    )
    WHERE id = NEW.menu_item_id;
    
    -- Generate low stock alert if needed
    IF (SELECT current_stock FROM menu_items WHERE id = NEW.menu_item_id) < 
       (SELECT CAST(setting_value AS SIGNED) FROM settings WHERE setting_name = 'low_stock_threshold') THEN
        INSERT INTO audit_log (
            user_id,
            action,
            table_name,
            record_id,
            old_values,
            new_values,
            created_at
        ) VALUES (
            NEW.created_by,
            'low_stock_alert',
            'menu_items',
            NEW.menu_item_id,
            NULL,
            JSON_OBJECT('current_stock', (SELECT current_stock FROM menu_items WHERE id = NEW.menu_item_id)),
            NOW()
        );
    END IF;
END //

-- =============================================
-- Triggers for Order Management
-- Handles order number generation and stock updates
-- =============================================

-- Trigger: before_order_insert
-- Purpose: Automatically generates unique order numbers
-- When: BEFORE INSERT on orders table
-- Format: YYYYMMDD-XXXX (e.g., 20240220-0001)
CREATE TRIGGER before_order_insert
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    -- Generate unique order number
    SET NEW.order_number = CONCAT(
        DATE_FORMAT(NOW(), '%Y%m%d'),
        '-',
        LPAD((SELECT COUNT(*) + 1 FROM orders WHERE DATE(created_at) = CURDATE()), 4, '0')
    );
END //

-- Trigger: after_order_item_insert
-- Purpose: Creates inventory transactions for stock reduction
-- When: AFTER INSERT on order_items table
-- Actions: Reduces stock levels through inventory transaction
CREATE TRIGGER after_order_item_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    -- Create inventory transaction for stock reduction
    INSERT INTO inventory_transactions (
        menu_item_id,
        transaction_type,
        quantity,
        notes,
        created_by,
        created_at
    ) VALUES (
        NEW.menu_item_id,
        'stock_out',
        NEW.quantity,
        CONCAT('Order #', (SELECT order_number FROM orders WHERE id = NEW.order_id)),
        NEW.created_by,
        NOW()
    );
END //

-- =============================================
-- Trigger for Expense Validation
-- Ensures expense amounts are valid
-- =============================================

-- Trigger: before_expense_insert
-- Purpose: Validates expense amounts before insertion
-- When: BEFORE INSERT on expenses table
-- Validation: Ensures amount is greater than zero
CREATE TRIGGER before_expense_insert
BEFORE INSERT ON expenses
FOR EACH ROW
BEGIN
    -- Validate expense amount is positive
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Expense amount must be greater than zero';
    END IF;
END //

DELIMITER ;

-- =============================================
-- Performance Optimization Indexes
-- These indexes improve query performance for common operations
-- =============================================

-- Menu Items Indexes
CREATE INDEX idx_menu_items_category ON menu_items(category_id);  -- Improves category filtering
CREATE INDEX idx_menu_items_active ON menu_items(is_active);      -- Improves active/inactive filtering

-- Inventory Transaction Indexes
CREATE INDEX idx_inventory_transactions_menu_item ON inventory_transactions(menu_item_id);  -- Improves stock calculations
CREATE INDEX idx_inventory_transactions_type ON inventory_transactions(transaction_type);    -- Improves transaction filtering

-- Order Items Indexes
CREATE INDEX idx_order_items_order ON order_items(order_id);        -- Improves order lookups
CREATE INDEX idx_order_items_menu_item ON order_items(menu_item_id); -- Improves menu item sales analysis

-- Expenses Indexes
CREATE INDEX idx_expenses_type ON expenses(expense_type);  -- Improves expense type filtering
CREATE INDEX idx_expenses_date ON expenses(expense_date);  -- Improves date-based reporting 