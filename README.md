# ERC-POS System

A comprehensive Point of Sale (POS) system for ERC Carinderia, featuring inventory management, sales tracking, expense management, and reporting.

## Features

- **Sales Order Management**: Create and manage customer orders with an intuitive interface
- **Menu Management**: Easily add, edit, and organize menu items by category
- **Inventory Tracking**: Monitor stock levels and manage inventory transactions
- **Expense Management**: Track all business expenses with categorization and reporting
- **Comprehensive Reporting**: Generate detailed reports for sales, expenses, and overall business performance
- **User Management**: Control access with role-based permissions (admin and staff)
- **Settings**: Configure system settings to match business requirements

## System Organization

### Core Modules

1. **Sales Order**: The main POS interface for creating customer orders
2. **Dashboard**: Overview of key business metrics and performance indicators
3. **Menu Items**: Management of all products and services offered
4. **Order History**: Complete record of all sales transactions
5. **Inventory**: Stock level monitoring and inventory management
6. **Expenses**: Comprehensive expense tracking and management
7. **Reports**: Detailed business analytics and reporting
8. **Users**: User account management and access control
9. **Settings**: System configuration and preferences

### Key Integrations

- **Inventory-Expense Integration**: Inventory transactions are reflected in financial reports
- **Comprehensive Reporting**: All financial transactions (sales and expenses) are integrated into unified reports

## Recent Updates

### Inventory Management Enhancements

- Maintained dedicated stock adjustment functionality for inventorized items
- Improved inventory transaction history and reporting
- Better integration between inventory operations and financial reporting

### Expense Management Enhancements

- Implemented a flexible expense entry system with support for multiple items
- Added expense categorization (ingredients, utilities, rent, etc.)
- Created comprehensive expense reports with filtering and export options

### Reporting Improvements

- Enhanced summary reports with expense breakdowns by category
- Added comprehensive expense reporting with both inventory and general expenses
- Improved financial analytics with profit margin calculations

## Technical Details

- Built with PHP, MySQL, Bootstrap, and jQuery
- Responsive design for use on various devices
- Structured with MVC-inspired architecture
- Secure authentication and authorization system

## Installation

1. Clone the repository to your web server directory
2. Import the database schema from `database/erc_pos.sql`
3. Configure database connection in `helpers/database.php`
4. Access the system through your web browser
5. Login with default admin credentials (username: admin, password: admin123)

## License

This project is proprietary software developed for ERC Carinderia. 