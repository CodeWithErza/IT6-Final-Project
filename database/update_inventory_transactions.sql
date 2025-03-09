-- SQL script to ensure the supplier and invoice_number columns exist in the inventory_transactions table
USE erc_pos;

-- Check if supplier column exists, add it if it doesn't
SET @supplierExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erc_pos' 
    AND TABLE_NAME = 'inventory_transactions' 
    AND COLUMN_NAME = 'supplier'
);

SET @invoiceExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erc_pos' 
    AND TABLE_NAME = 'inventory_transactions' 
    AND COLUMN_NAME = 'invoice_number'
);

SET @sql = '';

-- Add supplier column if it doesn't exist
IF @supplierExists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE inventory_transactions ADD COLUMN supplier VARCHAR(100) DEFAULT NULL AFTER unit_price; ');
END IF;

-- Add invoice_number column if it doesn't exist
IF @invoiceExists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE inventory_transactions ADD COLUMN invoice_number VARCHAR(50) DEFAULT NULL AFTER supplier; ');
END IF;

-- Execute the SQL if any columns need to be added
IF LENGTH(@sql) > 0 THEN
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

-- Update existing records to extract supplier and invoice_number from notes
-- Extract supplier information
UPDATE inventory_transactions
SET 
    supplier = SUBSTRING_INDEX(SUBSTRING_INDEX(notes, 'Supplier: ', -1), ' | ', 1)
WHERE 
    notes LIKE '%Supplier:%' AND 
    (supplier IS NULL OR supplier = '');

-- Extract invoice number information
UPDATE inventory_transactions
SET 
    invoice_number = SUBSTRING_INDEX(SUBSTRING_INDEX(notes, 'OR/Invoice #: ', -1), ' | ', 1)
WHERE 
    notes LIKE '%OR/Invoice #:%' AND 
    (invoice_number IS NULL OR invoice_number = '');

-- Clean up notes by removing extracted information
UPDATE inventory_transactions
SET 
    notes = REPLACE(notes, CONCAT('Supplier: ', supplier, ' | '), '')
WHERE 
    notes LIKE '%Supplier:%' AND 
    supplier IS NOT NULL AND 
    supplier != '';

UPDATE inventory_transactions
SET 
    notes = REPLACE(notes, CONCAT('OR/Invoice #: ', invoice_number, ' | '), '')
WHERE 
    notes LIKE '%OR/Invoice #:%' AND 
    invoice_number IS NOT NULL AND 
    invoice_number != '';

-- Remove "Stock Adjustment" from notes
UPDATE inventory_transactions
SET notes = REPLACE(notes, '(Stock Adjustment)', '')
WHERE notes LIKE '%(Stock Adjustment)%';

UPDATE inventory_transactions
SET notes = REPLACE(notes, 'Stock Adjustment', '')
WHERE notes LIKE '%Stock Adjustment%';

-- Clean up any trailing or leading separators in notes
UPDATE inventory_transactions
SET notes = TRIM(BOTH ' | ' FROM notes)
WHERE notes LIKE '% | %' OR notes LIKE ' | %' OR notes LIKE '% | ';

-- Final cleanup of any extra spaces
UPDATE inventory_transactions
SET notes = TRIM(notes)
WHERE notes LIKE '% %'; 