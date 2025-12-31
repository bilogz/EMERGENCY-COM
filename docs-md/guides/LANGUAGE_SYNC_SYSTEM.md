# Language Sync System - Admin to User/Guest

## Overview

This system ensures that languages managed by admins in the admin panel are automatically reflected to all users (both logged-in and guests) in real-time.

## How It Works

### 1. Admin Side (Language Management)

**Location:** `EMERGENCY-COM/ADMIN/sidebar/language-management.php`

- Admins can add, edit, activate, or deactivate languages
- All changes are saved to the `supported_languages` table
- Changes are immediately available to users

**Database Table:** `supported_languages`
- `id` - Primary key
- `language_code` - ISO language code (e.g., 'en', 'fil', 'es')
- `language_name` - Display name (e.g., 'English', 'Filipino')
- `native_name` - Native name (e.g., 'English', 'Filipino')
- `flag_emoji` - Flag emoji for display
- `is_active` - Whether language is available to users (1 = active, 0 = inactive)
- `is_ai_supported` - Whether AI translation is available
- `priority` - Display order (higher = shown first)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### 2. User/Guest Side (Language Selection)

**API Endpoint:** `EMERGENCY-COM/USERS/api/languages.php`

- **Public Access:** No authentication required (guests can access)
- **Fetches:** Only languages where `is_active = 1`
- **Real-time:** Checks for updates every 30 seconds
- **Cache:** Disabled to ensure fresh data

**Actions:**
- `action=list` - Get all active languages
- `action=check-updates` - Check if languages were updated
- `action=detect` - Detect browser/device language

### 3. Real-time Synchronization

**Script:** `EMERGENCY-COM/USERS/js/language-sync.js`

- Checks for language updates every 30 seconds
- Compares `last_update` timestamp with database
- Automatically refreshes language list when changes detected
- Shows notification to users when new languages are available
- Works for both logged-in users and guests

### 4. Language Selector Modal

**Script:** `EMERGENCY-COM/USERS/js/language-selector-modal.js`

- Always fetches fresh languages from database when opened
- Displays all active languages managed by admins
- Searchable list with filters
- Shows AI translation badges
- Updates automatically when languages change

## Data Flow

```
Admin Panel
    ↓
Add/Update Language
    ↓
supported_languages table (is_active = 1)
    ↓
USERS/api/languages.php (fetches active languages)
    ↓
Language Manager (loads languages)
    ↓
Language Selector Modal (displays to user)
    ↓
User selects language
    ↓
Saved to user_preferences (if logged in) or localStorage (guest)
```

## Features

### For Admins

1. **Add Languages:** Go to Admin → Language Management → Add Language
2. **Manage Languages:** Edit, activate, or deactivate languages
3. **Set Priority:** Control display order
4. **AI Support:** Mark languages as AI-translatable

### For Users/Guests

1. **Automatic Detection:** Device language detected on first visit
2. **Language Selector:** Click globe icon (top-right) to change language
3. **Real-time Updates:** New languages appear automatically (within 30 seconds)
4. **Search:** Search languages by name, native name, or code
5. **AI Badges:** See which languages support AI translation

## Real-time Update Mechanism

1. **Language Sync Script** (`language-sync.js`)
   - Runs every 30 seconds
   - Checks `updated_at` timestamp in database
   - Compares with last known update time
   - Refreshes language list if changes detected

2. **Language Manager** (`language-manager.js`)
   - Checks for updates every 30 seconds
   - Also checks when page becomes visible
   - Triggers `languagesUpdated` event when changes found

3. **Language Selector Modal** (`language-selector-modal.js`)
   - Always fetches fresh data when opened
   - Refreshes automatically when languages updated
   - Updates display immediately

## API Response Format

### List Languages
```json
{
    "success": true,
    "languages": [
        {
            "language_code": "en",
            "language_name": "English",
            "native_name": "English",
            "flag_emoji": "🇺🇸",
            "is_active": 1,
            "is_ai_supported": 1,
            "priority": 100,
            "updated_at": "2024-01-15 10:30:00"
        }
    ],
    "last_update": "2024-01-15 10:30:00",
    "count": 80
}
```

### Check Updates
```json
{
    "success": true,
    "updated": true,
    "last_update": "2024-01-15 10:35:00"
}
```

## Testing

### Test Admin Changes Reflecting to Users

1. **As Admin:**
   - Go to Admin → Language Management
   - Add a new language (e.g., "Swahili")
   - Set `is_active = 1`
   - Save

2. **As User/Guest:**
   - Open user portal
   - Wait up to 30 seconds (or refresh page)
   - Click globe icon
   - New language should appear in list

### Test Real-time Sync

1. Open user portal in browser
2. As admin, add a new language
3. Within 30 seconds, user should see notification: "New languages available!"
4. Click globe icon to see new language

## Files Involved

### Admin Side
- `EMERGENCY-COM/ADMIN/sidebar/language-management.php` - Admin UI
- `EMERGENCY-COM/ADMIN/api/language-management.php` - Admin API

### User Side
- `EMERGENCY-COM/USERS/api/languages.php` - Public API (no auth required)
- `EMERGENCY-COM/USERS/js/language-manager.js` - Language management
- `EMERGENCY-COM/USERS/js/language-selector-modal.js` - Language selector UI
- `EMERGENCY-COM/USERS/js/language-sync.js` - Real-time sync

### Database
- `supported_languages` table - Managed by admins, read by users
- `user_preferences` table - Stores user language preferences

## Security Notes

- **Public API:** `USERS/api/languages.php` is public (no authentication)
- **Read-only:** Users can only read languages, not modify them
- **Active Only:** Only languages with `is_active = 1` are shown to users
- **Admin Only:** Language management requires admin authentication

## Performance

- **Caching:** Disabled to ensure fresh data
- **Update Frequency:** Checks every 30 seconds (configurable)
- **Efficient:** Only checks timestamp, not full list
- **Lazy Loading:** Languages loaded when needed

## Troubleshooting

### Languages Not Appearing

1. Check `is_active = 1` in database
2. Check API endpoint is accessible
3. Check browser console for errors
4. Verify database connection

### Updates Not Reflecting

1. Check `updated_at` timestamp is updating
2. Verify sync script is running
3. Check browser console for errors
4. Try manual refresh

### API Errors

1. Check database connection
2. Verify `supported_languages` table exists
3. Check PHP error logs
4. Verify file permissions

