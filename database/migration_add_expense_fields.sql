-- Add missing fields to expenses table if they don't exist
SET @exist_supplier := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'expenses' AND COLUMN_NAME = 'supplier' AND TABLE_SCHEMA = DATABASE());
SET @exist_invoice := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'expenses' AND COLUMN_NAME = 'invoice_number' AND TABLE_SCHEMA = DATABASE());
SET @exist_notes := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'expenses' AND COLUMN_NAME = 'notes' AND TABLE_SCHEMA = DATABASE());

SET @add_supplier = IF(@exist_supplier = 0, 'ALTER TABLE expenses ADD COLUMN supplier VARCHAR(100)', 'SELECT "supplier column already exists"');
SET @add_invoice = IF(@exist_invoice = 0, 'ALTER TABLE expenses ADD COLUMN invoice_number VARCHAR(50)', 'SELECT "invoice_number column already exists"');
SET @add_notes = IF(@exist_notes = 0, 'ALTER TABLE expenses ADD COLUMN notes TEXT', 'SELECT "notes column already exists"');

PREPARE stmt1 FROM @add_supplier;
PREPARE stmt2 FROM @add_invoice;
PREPARE stmt3 FROM @add_notes;

EXECUTE stmt1;
EXECUTE stmt2;
EXECUTE stmt3;

DEALLOCATE PREPARE stmt1;
DEALLOCATE PREPARE stmt2;
DEALLOCATE PREPARE stmt3; 