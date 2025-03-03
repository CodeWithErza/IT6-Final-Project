# ERC POS System

A modern Point of Sale (POS) system designed for small eateries and carinderia businesses. This system helps manage orders, inventory, and generate reports efficiently.

## Features

- **Dashboard**: Real-time overview of daily sales, orders, and low stock alerts
- **Menu Management**: Add, edit, and manage menu items with active/inactive status
- **Order Processing**: Quick and easy order entry for walk-in customers
- **Inventory Management**: 
  - Track stock levels for beverages and other items
  - Stock in/out functionality
  - Stock adjustments
  - Audit log for all inventory changes
- **Reports**:
  - Sales reports (daily, monthly)
  - Inventory reports
  - Expense tracking
  - Order history
- **User Management**: 
  - Role-based access control (admin/employee)
  - User activation/deactivation

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone or download this repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/erc-pos.git
   ```

2. Create a MySQL database named 'erc_pos'

3. Import the database schema:
   ```bash
   mysql -u root -p erc_pos < database/erc_pos.sql
   ```

4. Configure the database connection:
   - Open `helpers/database.php`
   - Update the database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'erc_pos');
     ```

5. Set up the web server:
   - For Apache, ensure mod_rewrite is enabled
   - Point the document root to the project directory
   - Ensure the web server has write permissions for the uploads directory

6. Access the system:
   - Open your web browser and navigate to the project URL
   - Default admin credentials:
     - Username: admin
     - Password: admin123

## Directory Structure

```
erc-pos/
├── assets/           # Static assets (CSS, JS, images)
├── database/         # Database schema and migrations
├── handlers/         # Request handlers/controllers
├── helpers/          # Helper functions and utilities
├── static/          # Static templates and components
├── views/           # View files for each module
└── index.php        # Application entry point
```

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- SQL injection prevention using prepared statements
- XSS protection
- CSRF protection
- User activity logging

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions, please create an issue in the GitHub repository or contact the development team. 