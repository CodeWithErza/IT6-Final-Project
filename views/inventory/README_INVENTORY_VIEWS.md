# Inventory Management Views

This document explains the separation of inventory management into distinct views for better organization and usability.

## Overview

The inventory management system has been reorganized into separate views for each type of transaction:

1. **Stock Levels** - Overview of current inventory levels
2. **Stock In** - Managing incoming inventory (purchases)
3. **Stock Out** - Tracking outgoing inventory (sales, usage)
4. **Stock Adjustment** - Making corrections to inventory counts
5. **All Transactions** - Complete history of all inventory transactions

## Features by View

### Stock Levels (index.php)
- Shows current stock levels for all inventory items
- Provides quick overview of inventory status
- Highlights low stock items

### Stock In (stock_in.php)
- Focused on adding new inventory
- Records purchases with supplier information
- Tracks costs for expense reporting
- Shows summary of items added and total costs

### Stock Out (stock_out.php)
- Shows inventory removed from stock
- Categorizes by reason (sales, waste, etc.)
- Links to related orders when applicable
- Provides quantity summaries

### Stock Adjustment (stock_adjustment.php)
- Dedicated interface for inventory corrections
- Categorizes adjustments (physical count, damage, etc.)
- Shows whether adjustments increased or decreased stock
- Requires notes explaining the reason for adjustment

### All Transactions (history.php)
- Comprehensive view of all inventory movements
- Includes all transaction types in one view
- Provides advanced filtering options

## Benefits of Separation

1. **Focused Interfaces** - Each view is optimized for its specific purpose
2. **Improved Organization** - Easier to find specific transaction types
3. **Better Data Entry** - Forms tailored to each transaction type
4. **Clearer Reporting** - Separate views make it easier to analyze different aspects of inventory
5. **Reduced Complexity** - Users only see what's relevant to their current task

## Implementation Notes

- All views use the same underlying database tables
- Transaction types are standardized as 'stock_in', 'stock_out', and 'adjustment'
- The sidebar menu provides easy navigation between views
- Each view includes appropriate filtering options 