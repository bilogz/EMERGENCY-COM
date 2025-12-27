# Remote Database Setup Guide

This guide explains how to set up and use the remote database `emer_comm_test` on `alertaraqc.com`.

## Database Credentials

- **Host**: `alertaraqc.com`
- **Port**: `3306` (default MySQL port)
- **Database**: `emer_comm_test`
- **Username**: `root`
- **Password**: `YsqnXk6q#145`

## Setup Steps

### Step 1: Create the Database and Tables

1. Open your browser and navigate to:
   ```
   http://your-domain/EMERGENCY-COM/ADMIN/api/setup_remote_database.php
   ```

2. The script will:
   - Connect to the MySQL server
   - Create the `emer_comm_test` database if it doesn't exist
   - Run the database schema to create all required tables
   - Insert default data (alert categories, integration settings, etc.)

3. You should see output confirming:
   - ✓ Database connection successful
   - ✓ Database created
   - ✓ Tables created
   - ✓ Default data inserted

### Step 2: Test the Connection

1. Navigate to:
   ```
   http://your-domain/EMERGENCY-COM/ADMIN/api/test_database_connection.php
   ```

2. This script will verify:
   - Database connection
   - All tables exist
   - CRUD operations work
   - Default data is loaded

### Step 3: Create a Backup/Duplicate

1. Navigate to:
   ```
   http://your-domain/EMERGENCY-COM/ADMIN/api/duplicate_database.php
   ```

2. This script will:
   - Create a copy of `emer_comm_test` named `emer_comm_test_backup`
   - Copy all tables and data
   - Verify the backup was created successfully

## Database Configuration

The database connection is configured in:
- `EMERGENCY-COM/ADMIN/api/db_connect.php`
- `EMERGENCY-COM/USERS/api/db_connect.php`

Both files have been updated to use the remote database credentials.

## Database Schema

The database includes the following tables:

### Core Tables
- **users** - User/citizen information
- **alert_categories** - Alert category definitions
- **alerts** - Emergency alerts
- **alert_translations** - Multilingual alert translations

### Notification System
- **notification_logs** - Logs of all notifications sent (SMS, Email, PA)
- **subscriptions** - Citizen subscription preferences

### Communication System
- **conversations** - Two-way communication conversations
- **messages** - Messages within conversations

### Automated Warnings
- **integration_settings** - API keys and settings for PAGASA, PHIVOLCS
- **warning_settings** - Automated warning configuration
- **automated_warnings** - Received automated warnings

## Module Functionality

All modules are now configured to work with the remote database:

### ✅ Mass Notification System
- Sends notifications via SMS, Email, and PA System
- Logs all notifications to `notification_logs` table
- API: `mass-notification.php`

### ✅ Alert Categorization
- Manages alert categories (Weather, Earthquake, Bomb Threat, Fire, General)
- Links alerts to categories
- API: `alert-categories.php`

### ✅ Two-Way Communication
- Manages conversations between admins and citizens
- Stores messages in `messages` table
- API: `two-way-communication.php`

### ✅ Automated Warnings
- Integrates with PAGASA (weather) and PHIVOLCS (earthquake)
- Stores API keys in `integration_settings`
- Automatically syncs warnings
- API: `automated-warnings.php`

### ✅ Multilingual Alerts
- Translates alerts to multiple languages
- Stores translations in `alert_translations` table
- API: `multilingual-alerts.php`

### ✅ Citizen Subscriptions
- Manages citizen subscription preferences
- Category-based subscriptions
- Channel preferences (SMS, Email, Push)
- API: `citizen-subscriptions.php`

### ✅ Weather Monitoring
- Fetches weather data from OpenWeather API
- Uses PAGASA API key from `integration_settings`
- Displays real-time weather on map
- API: `weather-monitoring.php`

### ✅ Dashboard Analytics
- Provides statistics and charts
- Shows notification history
- Displays recent activity
- API: `dashboard.php`

### ✅ Audit Trail
- Logs all system activities
- Tracks notification history
- API: `audit-trail.php`

## Troubleshooting

### Connection Issues

If you encounter connection errors:

1. **Check network connectivity**
   - Ensure `alertaraqc.com` is accessible
   - Check firewall settings

2. **Verify credentials**
   - Confirm username and password are correct
   - Check if the database server allows remote connections

3. **Check MySQL port**
   - Default port is 3306
   - If using a different port, update `db_connect.php`

### Database Errors

If tables are missing:

1. Run `setup_remote_database.php` again
2. Check for SQL errors in the output
3. Verify the schema file exists: `database_schema.sql`

### Module Not Working

If a module isn't working:

1. Check `test_database_connection.php` output
2. Verify the required tables exist
3. Check PHP error logs
4. Ensure `db_connect.php` is included in the API file

## Security Notes

⚠️ **Important**: The database credentials are stored in plain text in `db_connect.php`. 

For production:
- Consider using environment variables
- Restrict file permissions
- Use a dedicated database user with limited privileges
- Enable SSL/TLS for database connections

## Backup Recommendations

1. Run `duplicate_database.php` regularly to create backups
2. Export database via phpMyAdmin periodically
3. Set up automated backups if possible

## Next Steps

After setup:
1. Configure PAGASA API key in Automated Warnings module
2. Set up email/SMS gateway credentials (if needed)
3. Add initial admin users
4. Configure notification channels
5. Test each module functionality

## Support

For issues or questions:
- Check PHP error logs
- Review database connection test output
- Verify all tables exist using phpMyAdmin
- Test individual API endpoints

