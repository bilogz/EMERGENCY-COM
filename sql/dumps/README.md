# Database Dumps

This folder contains complete database schema dumps and backups.

## Files

### `emer_comm_test_schema.sql`
- **Type**: Complete database schema (structure only, no data)
- **Database**: `emer_comm_test`
- **Description**: Clean schema dump containing all table definitions, indexes, foreign keys, and constraints
- **Generated**: January 7, 2026
- **Note**: This file contains only the database structure (CREATE TABLE, ALTER TABLE statements) without any INSERT data

## Usage

To restore the database schema:
```sql
mysql -u username -p emer_comm_test < emer_comm_test_schema.sql
```

Or import via phpMyAdmin:
1. Select the database
2. Go to Import tab
3. Choose this file and import

