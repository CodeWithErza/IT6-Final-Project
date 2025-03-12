# Inventory Management Views

This document explains the organization of inventory management in the ERC-POS system.

## Overview

The inventory management system includes the following components:

1. **Stock Levels** - Overview of current inventory levels
2. **Stock In** - Managing incoming inventory (purchases)
3. **Stock Adjustment** - Making corrections to inventory counts
4. **All Transactions** - Complete history of all inventory transactions

## Features by View

### Stock Levels (index.php)
- Shows current stock levels for all inventory items
- Provides quick overview of inventory status
- Highlights low stock items
- Links to other inventory management functions

### Stock In (stock_in.php)
- Focused on adding new inventory
- Records purchases with supplier information
- Tracks costs for expense reporting
- Shows summary of items added and total costs

### Stock Adjustment (stock_adjustment.php)
- Dedicated interface for inventory corrections
- Categorizes adjustments (physical count, damage, etc.)
- Shows whether adjustments increased or decreased stock
- Requires notes explaining the reason for adjustment

### All Transactions (history.php)
- Comprehensive view of all inventory movements
- Includes all transaction types in one view
- Provides advanced filtering options
- Tracks inventory history for auditing purposes

## Integration with Expenses System

While inventory management has its own dedicated screens, it also integrates with the Expenses system:

1. **Expenses Management** - Record all expenses including inventory-related costs
2. **Expense Types** - Categorize expenses (ingredients, utilities, etc.)
3. **Expense Reporting** - Comprehensive reporting of all business expenses

## Benefits of This Approach

1. **Specialized Inventory Management** - Dedicated tools for inventory-specific operations
2. **Unified Financial Tracking** - All expenses in one place for financial reporting
3. **Clear Separation of Concerns** - Inventory management vs. financial tracking
4. **Comprehensive Reporting** - Integrated reports that show both inventory and expense data

## Implementation Notes

- All views use the same underlying database tables
- Transaction types are standardized as 'stock_in', 'stock_out', and 'adjustment'
- The sidebar menu provides easy navigation between views
- Each view includes appropriate filtering options 