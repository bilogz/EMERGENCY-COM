# 🎛️ User-Controlled AI Auto-Translation Feature

## Overview

Users can now control whether they want AI-powered auto-translation enabled for their account. This gives users the choice between:
- **Enabled (Default)**: Content is automatically translated to their preferred language using AI
- **Disabled**: Content is shown in its original language (English or Filipino only)

---

## 🌟 Key Features

### User Benefits
- ✅ **Full Control**: Users decide if they want AI translations
- ✅ **Privacy-Conscious**: Users who prefer original content can disable AI translation
- ✅ **Flexible**: Can be toggled on/off anytime in profile settings
- ✅ **Persistent**: Preference is saved to user account (or localStorage for guests)
- ✅ **Clear Feedback**: Users are notified when auto-translation is disabled

### System Features
- ✅ **Database-Backed**: Preference stored in `user_preferences` table
- ✅ **API Integration**: All translation APIs respect user preference
- ✅ **Guest Support**: Works for both logged-in and guest users
- ✅ **Backward Compatible**: Existing users default to enabled (current behavior)

---

## 🚀 Setup Instructions

### Step 1: Run Database Migration

Execute the setup script to add the new field to your database:

```bash
# Visit this URL in your browser:
http://your-domain.com/ADMIN/api/setup-auto-translate-preference.php
```

Or run the SQL directly:

```sql
-- Run this SQL script
SOURCE EMERGENCY-COM/ADMIN/api/add-auto-translate-preference.sql;
```

**What it does:**
- Adds `auto_translate_enabled` column to `user_preferences` table
- Sets default value to `1` (enabled) for all existing users
- Creates index for performance

### Step 2: Verify Installation

Check that the setup was successful:

```sql
-- Verify column exists
DESCRIBE user_preferences;

-- Check user preferences
SELECT 
    user_id, 
    preferred_language, 
    auto_translate_enabled 
FROM user_preferences 
LIMIT 10;
```

---

## 📖 How It Works

### Architecture Flow

```
User Opens Page
    ↓
JavaScript checks localStorage: auto_translate_enabled
    ↓
If language needs translation (not EN/FIL):
    ↓
    Is auto_translate_enabled = true?
    ↓
    YES → Fetch AI translation from API
    NO  → Show English content + notification
    ↓
API checks user_preferences table
    ↓
If auto_translate_enabled = 0:
    ↓
    Return English translations
    Add note: "Auto-translation disabled by user"
    ↓
Frontend displays content accordingly
```

### Data Flow

1. **User Profile Settings** (`profile.php`)
   - User toggles "Enable AI Auto-Translation" checkbox
   - JavaScript saves to localStorage + API

2. **API Layer** (`user-language-preference.php`)
   - Receives preference update
   - Saves to `user_preferences.auto_translate_enabled`
   - Returns confirmation

3. **Translation API** (`get-translations.php`)
   - Checks user's `auto_translate_enabled` setting
   - If disabled: Returns English translations
   - If enabled: Performs AI translation as normal

4. **Frontend** (`translations.js`)
   - Checks localStorage before requesting translations
   - Shows notification if auto-translate is disabled
   - Applies appropriate translations to page

---

## 🎨 User Interface

### Profile Settings Page

Location: `profile.php` → Language Settings section

```html
☑️ Enable AI Auto-Translation

When enabled, content will be automatically translated to your 
preferred language using AI. Disable this if you prefer to view 
content in its original language (English/Filipino only).

Note: English and Filipino content is always available without 
AI translation. Other languages use AI for natural, context-aware 
translations.
```

### Visual Notification

When a user has auto-translation disabled and selects a non-English/Filipino language:

```
┌─────────────────────────────────────────────────┐
│ ℹ️  AI Translation Disabled                     │
│                                                  │
│ You've disabled auto-translation. Showing       │
│ content in English. To view in Spanish, enable  │
│ AI translation in your profile settings.        │
│                                              [×] │
└─────────────────────────────────────────────────┘
```

- Appears top-right corner
- Gradient purple background
- Auto-dismisses after 8 seconds
- Shows once per session per language

---

## 🗄️ Database Schema

### Table: `user_preferences`

```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Language Preferences
    preferred_language VARCHAR(10) DEFAULT 'en',
    auto_translate_enabled TINYINT(1) DEFAULT 1,  -- NEW FIELD
    
    -- Other preferences...
    
    UNIQUE KEY unique_user (user_id),
    INDEX idx_auto_translate (auto_translate_enabled),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Field Details:**
- **Column**: `auto_translate_enabled`
- **Type**: `TINYINT(1)` (boolean)
- **Default**: `1` (enabled)
- **Nullable**: No
- **Index**: Yes (for performance)

---

## 🔌 API Endpoints

### 1. Get User Preferences

**Endpoint**: `GET /USERS/api/user-language-preference.php?action=get`

**Response**:
```json
{
    "success": true,
    "language": "es",
    "auto_translate_enabled": true,
    "user_id": 123
}
```

### 2. Set User Preferences

**Endpoint**: `POST /USERS/api/user-language-preference.php?action=set`

**Request Body**:
```json
{
    "language": "es",
    "auto_translate_enabled": true
}
```

**Response**:
```json
{
    "success": true,
    "message": "Preferences saved successfully",
    "language": "es",
    "auto_translate_enabled": true,
    "saved_to_account": true
}
```

### 3. Get Translations (with preference check)

**Endpoint**: `GET /USERS/api/get-translations.php?lang=es`

**Response (when disabled)**:
```json
{
    "success": true,
    "language_code": "es",
    "language_name": "Spanish",
    "translations": { /* English translations */ },
    "auto_translated": false,
    "note": "Auto-translation disabled by user. Showing English content.",
    "user_preference": "auto_translate_disabled"
}
```

**Response (when enabled)**:
```json
{
    "success": true,
    "language_code": "es",
    "language_name": "Spanish",
    "translations": { /* Spanish translations */ },
    "auto_translated": true,
    "ai_provider": "gemini",
    "note": "Automatically translated using GEMINI AI"
}
```

---

## 💻 Code Examples

### JavaScript: Check Auto-Translate Preference

```javascript
// Check if auto-translate is enabled
const autoTranslateEnabled = localStorage.getItem('auto_translate_enabled') !== 'false';

if (!autoTranslateEnabled && lang !== 'en' && lang !== 'fil') {
    console.log('Auto-translation disabled, showing English');
    translation = translations.en;
    showAutoTranslateDisabledNotice(lang);
}
```

### JavaScript: Save Preference

```javascript
// Save auto-translate preference
const autoTranslateEnabled = document.getElementById('autoTranslateEnabled').checked;

await fetch('api/user-language-preference.php?action=set', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        language: 'es',
        auto_translate_enabled: autoTranslateEnabled
    })
});

// Also save to localStorage for immediate effect
localStorage.setItem('auto_translate_enabled', autoTranslateEnabled ? 'true' : 'false');
```

### PHP: Check User Preference

```php
// Get user's auto-translate preference
$autoTranslateEnabled = true; // Default
$userId = $_SESSION['user_id'] ?? null;

if ($userId && $pdo) {
    $stmt = $pdo->prepare("
        SELECT auto_translate_enabled 
        FROM user_preferences 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prefs && isset($prefs['auto_translate_enabled'])) {
        $autoTranslateEnabled = (bool)$prefs['auto_translate_enabled'];
    }
}

// Use preference to decide translation behavior
if (!$autoTranslateEnabled && $languageCode !== 'en' && $languageCode !== 'fil') {
    // Return English translations
    return $baseTranslations;
}
```

---

## 🧪 Testing Guide

### Test Scenario 1: Enable Auto-Translation

1. Go to Profile → Language Settings
2. Check "Enable AI Auto-Translation"
3. Select Spanish from dropdown
4. Click "Save Language Settings"
5. **Expected**: Page translates to Spanish using AI

### Test Scenario 2: Disable Auto-Translation

1. Go to Profile → Language Settings
2. Uncheck "Enable AI Auto-Translation"
3. Select Spanish from dropdown
4. Click "Save Language Settings"
5. **Expected**: 
   - Page shows English content
   - Purple notification appears: "AI Translation Disabled"
   - Notification includes link to profile settings

### Test Scenario 3: Guest User

1. Open site in incognito/private mode
2. Select Spanish language
3. **Expected**: Auto-translation works (default enabled)
4. Disable in profile settings
5. **Expected**: Preference saved to localStorage only

### Test Scenario 4: Database Persistence

1. Login as user
2. Disable auto-translation
3. Logout
4. Login again
5. **Expected**: Auto-translation still disabled (loaded from database)

### Test Scenario 5: English/Filipino (No AI)

1. Disable auto-translation
2. Select English
3. **Expected**: Works normally (no AI needed)
4. Select Filipino
5. **Expected**: Works normally (pre-translated)

---

## 🔍 Troubleshooting

### Problem: Checkbox doesn't save

**Solution:**
1. Check browser console for errors
2. Verify API endpoint is accessible: `user-language-preference.php`
3. Check database connection
4. Verify user is logged in (check session)

### Problem: Still showing translations when disabled

**Solution:**
1. Clear localStorage: `localStorage.clear()`
2. Clear browser cache (Ctrl+Shift+R)
3. Check database value: 
   ```sql
   SELECT auto_translate_enabled FROM user_preferences WHERE user_id = ?;
   ```
4. Verify API is returning correct preference

### Problem: Column doesn't exist error

**Solution:**
1. Run the migration script: `setup-auto-translate-preference.php`
2. Or manually run the SQL: `add-auto-translate-preference.sql`
3. Verify column exists:
   ```sql
   SHOW COLUMNS FROM user_preferences LIKE 'auto_translate_enabled';
   ```

### Problem: Notification doesn't appear

**Solution:**
1. Check if SweetAlert2 is loaded
2. Verify Font Awesome icons are loaded
3. Check browser console for JavaScript errors
4. Clear sessionStorage: `sessionStorage.clear()`

---

## 📊 Analytics & Monitoring

### Track Usage

```sql
-- Count users with auto-translate enabled vs disabled
SELECT 
    auto_translate_enabled,
    COUNT(*) as user_count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM user_preferences), 2) as percentage
FROM user_preferences
GROUP BY auto_translate_enabled;

-- Most popular languages with auto-translate disabled
SELECT 
    preferred_language,
    COUNT(*) as count
FROM user_preferences
WHERE auto_translate_enabled = 0
GROUP BY preferred_language
ORDER BY count DESC;

-- Recent preference changes
SELECT 
    up.user_id,
    u.name,
    up.preferred_language,
    up.auto_translate_enabled,
    up.updated_at
FROM user_preferences up
JOIN users u ON u.id = up.user_id
ORDER BY up.updated_at DESC
LIMIT 20;
```

---

## 🎯 Use Cases

### Use Case 1: Privacy-Conscious User
**Scenario**: User doesn't want their content sent to AI services

**Solution**: 
- User disables auto-translation
- Content stays in original English/Filipino
- No API calls to AI providers

### Use Case 2: Bilingual User
**Scenario**: User understands English but prefers Spanish interface

**Solution**:
- User keeps auto-translation enabled
- Selects Spanish as preferred language
- Gets natural AI translations

### Use Case 3: Quality Control
**Scenario**: User finds AI translations inaccurate for their language

**Solution**:
- User disables auto-translation
- Views original English content
- Can manually translate if needed

### Use Case 4: Testing/Development
**Scenario**: Developer wants to test without triggering AI calls

**Solution**:
- Disable auto-translation in profile
- Test UI with English content
- Re-enable when ready

---

## 🔐 Security & Privacy

### Data Protection
- ✅ Preference stored securely in database
- ✅ No sensitive data in localStorage
- ✅ API requires valid session
- ✅ SQL injection protected (prepared statements)

### Privacy Considerations
- ✅ Users can opt-out of AI translation
- ✅ No translation data sent to AI when disabled
- ✅ Original content always available
- ✅ User choice is respected system-wide

---

## 📈 Future Enhancements

### Phase 1 (Current): ✅
- User toggle in profile settings
- Database persistence
- API integration
- Visual notifications

### Phase 2 (Planned):
- [ ] Per-language preferences (enable AI for some languages, not others)
- [ ] Translation quality feedback system
- [ ] Admin dashboard to view preference statistics
- [ ] Bulk enable/disable for testing

### Phase 3 (Future):
- [ ] AI provider selection per user
- [ ] Translation history/cache viewer
- [ ] Custom translation overrides
- [ ] Community translation contributions

---

## 📞 Support

### For Users
- Go to Profile → Language Settings
- Toggle "Enable AI Auto-Translation"
- Click "Save Language Settings"
- Contact support if issues persist

### For Developers
- Check API responses in Network tab
- Verify database schema is up to date
- Review console logs for errors
- Test with different user accounts

### Common Questions

**Q: What happens to existing users?**
A: Default is enabled (current behavior maintained)

**Q: Does this affect English/Filipino?**
A: No, those languages don't use AI translation

**Q: Can guest users use this feature?**
A: Yes, preference saved to localStorage

**Q: Is the preference synced across devices?**
A: Yes, for logged-in users (stored in database)

**Q: What if I change my mind?**
A: Toggle anytime in profile settings

---

## ✅ Success Checklist

Your implementation is complete when:

- ✅ Database migration ran successfully
- ✅ Column `auto_translate_enabled` exists in `user_preferences`
- ✅ Checkbox appears in profile.php
- ✅ Checkbox state loads from database
- ✅ Saving preference updates database
- ✅ API respects user preference
- ✅ Notification appears when disabled
- ✅ English content shows when disabled
- ✅ AI translation works when enabled
- ✅ Preference persists after logout/login

---

## 📝 Files Modified

### Database
- `ADMIN/api/add-auto-translate-preference.sql` (NEW)
- `ADMIN/api/setup-auto-translate-preference.php` (NEW)

### Backend APIs
- `USERS/api/user-language-preference.php` (MODIFIED)
- `USERS/api/get-translations.php` (MODIFIED)

### Frontend
- `USERS/profile.php` (MODIFIED)
- `USERS/js/translations.js` (MODIFIED)

### Documentation
- `DOCS/USER_CONTROLLED_AUTO_TRANSLATION.md` (NEW)

---

**Last Updated**: December 29, 2025  
**Feature Version**: 1.0  
**Status**: ✅ Production Ready  
**Compatibility**: All modern browsers, PHP 7.4+, MySQL 5.7+

---

## 🎉 Summary

This feature gives users **complete control** over AI auto-translation:

- **Default**: Enabled (maintains current behavior)
- **User Choice**: Can disable in profile settings
- **Persistent**: Saved to database for logged-in users
- **Clear Feedback**: Visual notifications when disabled
- **Privacy-Friendly**: No AI calls when disabled
- **Backward Compatible**: Existing users unaffected

Users now have the power to choose how they want to experience your multilingual emergency communication system! 🌍

