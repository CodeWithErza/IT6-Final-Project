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

## Installation Guide

### Prerequisites

1. Install XAMPP (version 7.4 or higher)
   - Download from: https://www.apachefriends.org/download.html
   - Install with default settings

### Installation Steps

1. Extract the compressed folder
2. Copy the `ERC-POS` folder to `C:\xampp\htdocs\`
3. Start XAMPP Control Panel

   - Start Apache
   - Start MySQL

4. Database Setup

   - Open your browser and go to: http://localhost/phpmyadmin
   - Create a new database named `erc_pos`
   - Select the `erc_pos` database
   - Click on "Import" in the top menu
   - Import the following SQL files in this order:
     1. `database/erc_pos.sql`
     2. `database/stored_procedures.sql`
     3. `database/tcl_and_triggers.sql`
     4. `database/update_procedures.sql`

5. Access the System
   - Open your browser
   - Go to: http://localhost/ERC-POS
   - Login using these credentials:
     - Email: admin@gmail.com
     - Password: admin123

### Troubleshooting

- If you get a database connection error:

  1. Check if MySQL is running in XAMPP Control Panel
  2. Verify database name is `erc_pos`
  3. Verify database user is `root` with no password

- If images don't appear:
  1. Make sure the `uploads` folder has write permissions
  2. Check if all image files are properly copied

### File Structure

- `database/` - Contains all SQL files for database setup
- `uploads/` - Contains uploaded images and files
- `views/` - Contains all page templates
- `handlers/` - Contains PHP processing scripts
- `assets/` - Contains static files (CSS, JS, images)

### Notes

- The system uses a local MySQL database with default XAMPP settings
- Default database configuration:
  - Host: localhost
  - User: root
  - Password: (empty)
  - Database: erc_pos

For any issues, please contact the development team.

## License

This project is proprietary software developed for ERC Carinderia.
