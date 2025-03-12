# Database Directory

This directory contains all database-related files for the ERC-POS system.

## Main Schema

- **erc_pos.sql** - The main database schema containing all table definitions and initial data. This should be used for fresh installations.

## Migration Files

These files contain incremental changes to the database schema that should be applied to existing installations:

- **migration_create_expenses_table.sql** - Creates the expenses table with all required columns and indexes for the expense management system.
- **migration_add_is_active_to_categories.sql** - Adds the `is_active` column to the categories table to support category activation/deactivation.

## Stored Procedures

- **stored_procedures.sql** - Contains all stored procedures used by the application for inventory management, sales reporting, and other database operations.

## Usage Instructions

### Fresh Installation

For a new installation, use the main schema file:

```bash
mysql -u username -p erc_pos < erc_pos.sql
```

### Applying Migrations

For existing installations, apply the migration files in sequence:

```bash
mysql -u username -p erc_pos < migration_add_is_active_to_categories.sql
mysql -u username -p erc_pos < migration_create_expenses_table.sql
```

### Updating Stored Procedures

To update stored procedures:

```bash
mysql -u username -p erc_pos < stored_procedures.sql
```

## Maintenance Notes

- Always back up your database before applying migrations
- Migration files should be applied in the order they were created
- The main schema file is kept up-to-date with all migrations for fresh installations 