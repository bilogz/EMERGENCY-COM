# Chat System Migration: Firebase to MySQL

## Setup Instructions

### 1. Create Database Tables

Run the SQL script to create the necessary tables:

```bash
mysql -u your_username -p your_database < sql/create_chat_tables.sql
```

Or import it through phpMyAdmin.

### 2. API Endpoints Created

**User Side:**
- `USERS/api/chat-send.php` - Send messages
- `USERS/api/chat-get-messages.php` - Get messages
- `USERS/api/chat-get-conversation.php` - Get/create conversation

**Admin Side:**
- `ADMIN/api/chat-get-conversations.php` - List all conversations
- `ADMIN/api/chat-send.php` - Admin send messages
- `ADMIN/api/chat-get-messages.php` - Get messages for a conversation

### 3. JavaScript Files

- `USERS/js/chat-mysql.js` - MySQL-based chat system (replaces Firebase)

### 4. What Changed

1. Removed Firebase SDK scripts
2. Added `chat-mysql.js` script
3. Replaced `initFirebaseChat()` calls with `initChatMySQL()`
4. Replaced Firebase send functions with MySQL API calls
5. Added polling for real-time message updates (every 2 seconds)

### 5. Next Steps

1. **Remove the old Firebase function** from `sidebar.php` (lines 653-1217)
2. **Update admin side** to use PHP/MySQL instead of Firebase
3. **Test the system** by sending messages

### 6. Testing

1. Open chat modal on user side
2. Fill in user info (if guest)
3. Send a message
4. Check admin side - conversation should appear
5. Admin can reply
6. User should see reply within 2 seconds (polling)





