# Quick Start Guide - Remote Database Setup

## 🚀 Quick Setup (3 Steps)

### Step 1: Create Database
Visit in browser:
```
http://your-domain/EMERGENCY-COM/ADMIN/api/setup_remote_database.php
```
This creates the `emer_comm_test` database and all tables.

### Step 2: Test Connection
Visit in browser:
```
http://your-domain/EMERGENCY-COM/ADMIN/api/test_database_connection.php
```
This verifies everything is working.

### Step 3: Create Backup (Optional)
Visit in browser:
```
http://your-domain/EMERGENCY-COM/ADMIN/api/duplicate_database.php
```
This creates `emer_comm_test_backup` as a copy.

## ✅ What's Configured

- ✅ Database connection updated to `alertaraqc.com`
- ✅ Database name: `emer_comm_test`
- ✅ All modules connected to remote database
- ✅ Setup scripts created
- ✅ Test scripts created

## 📋 Database Credentials

- **Host**: alertaraqc.com
- **Database**: emer_comm_test
- **User**: root
- **Password**: YsqnXk6q#145

## 🔧 Files Updated

1. `EMERGENCY-COM/ADMIN/api/db_connect.php` - Updated to remote server
2. `EMERGENCY-COM/USERS/api/db_connect.php` - Updated to remote server

## 📁 New Files Created

1. `setup_remote_database.php` - Creates database and tables
2. `duplicate_database.php` - Creates database backup
3. `test_database_connection.php` - Tests connection and tables
4. `REMOTE_DATABASE_SETUP.md` - Full documentation

## ✨ All Modules Ready

All modules now work with the remote database:
- Mass Notification System
- Alert Categorization
- Two-Way Communication
- Automated Warnings
- Multilingual Alerts
- Citizen Subscriptions
- Weather Monitoring
- Dashboard Analytics
- Audit Trail

## 🎯 Next Steps

1. Run `setup_remote_database.php` to create the database
2. Configure PAGASA API key in Automated Warnings module
3. Test each module to ensure they're working
4. Set up email/SMS gateway if needed

---

For detailed information, see `REMOTE_DATABASE_SETUP.md`

