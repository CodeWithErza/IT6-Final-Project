-- Create database
CREATE DATABASE IF NOT EXISTS erc_pos;
USE erc_pos;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(50) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'textarea') NOT NULL,
    setting_group VARCHAR(50) NOT NULL,
    setting_label VARCHAR(100) NOT NULL,
    description TEXT,
    help_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Menu items table
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_inventory_item BOOLEAN DEFAULT FALSE,
    current_stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal_amount DECIMAL(10,2) NOT NULL,
    discount_type VARCHAR(20) DEFAULT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    cash_received DECIMAL(10,2) NOT NULL,
    cash_change DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'other') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Inventory transactions table
CREATE TABLE inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    menu_item_id INT NOT NULL,
    transaction_type ENUM('stock_in', 'stock_out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) DEFAULT NULL,
    notes TEXT,
    supplier VARCHAR(100),
    invoice_number VARCHAR(50),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expenses table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Audit log table
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default settings
INSERT INTO settings (setting_name, setting_value, setting_type, setting_group, setting_label, description, help_text) VALUES
-- Business Information
('business_name', 'ERC Carinderia', 'text', 'business', 'Business Name', 'Name of your business', 'This will appear on receipts and reports'),
('business_address', '', 'textarea', 'business', 'Business Address', 'Complete address of your business', 'This will appear on receipts and reports'),
('business_phone', '', 'text', 'business', 'Business Phone', 'Contact number of your business', 'This will appear on receipts and reports'),
('business_email', '', 'text', 'business', 'Business Email', 'Email address of your business', 'This will appear on receipts and reports'),

-- System Settings
('low_stock_threshold', '10', 'number', 'system', 'Low Stock Alert Threshold', 'Minimum stock level before showing low stock alert', 'Set to 0 to disable low stock alerts'),
('enable_audit_log', '1', 'boolean', 'system', 'Enable Audit Log', 'Track all changes made in the system', 'Helps in monitoring user activities and changes'),
('default_currency', 'PHP', 'text', 'system', 'Default Currency', 'Currency symbol to use in the system', 'This will be used throughout the system'),
('receipt_footer', 'Thank you for your business!', 'textarea', 'system', 'Receipt Footer Message', 'Message to display at the bottom of receipts', 'You can use this for thank you messages or business policies'),

-- Receipt Settings
('show_receipt_logo', '1', 'boolean', 'receipt', 'Show Logo on Receipt', 'Display business logo on receipts', NULL),
('receipt_printer_type', 'thermal', 'text', 'receipt', 'Receipt Printer Type', 'Type of receipt printer being used', 'Common types: thermal, dot-matrix'),
('receipt_width', '80', 'number', 'receipt', 'Receipt Width (mm)', 'Width of the receipt paper in millimeters', 'Standard sizes: 58mm, 80mm'),

-- Inventory Settings
('enable_stock_alerts', '1', 'boolean', 'inventory', 'Enable Stock Alerts', 'Show notifications for low stock items', NULL),
('track_inventory_history', '1', 'boolean', 'inventory', 'Track Inventory History', 'Keep detailed records of all inventory changes', 'Helps in tracking stock movements and auditing'),
('default_stock_adjustment_notes', 'Regular stock count adjustment', 'text', 'inventory', 'Default Stock Adjustment Notes', 'Default notes for stock adjustments', 'Can be changed during actual stock adjustment');

-- Insert test categories
INSERT INTO categories (name) VALUES
('Rice Meals'),
('Beverages'),
('Snacks'),
('Desserts');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'); 