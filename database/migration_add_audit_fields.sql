-- Add missing audit fields to tables

-- Categories table
ALTER TABLE categories
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Menu items table
ALTER TABLE menu_items
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Orders table
ALTER TABLE orders
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Order items table
ALTER TABLE order_items
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Inventory transactions table
ALTER TABLE inventory_transactions
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Expenses table
ALTER TABLE expenses
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Settings table
ALTER TABLE settings
ADD COLUMN created_by INT,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (created_by) REFERENCES users(id),
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Audit log table
ALTER TABLE audit_log
ADD COLUMN created_by INT,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN updated_by INT,
ADD FOREIGN KEY (created_by) REFERENCES users(id),
ADD FOREIGN KEY (updated_by) REFERENCES users(id); 