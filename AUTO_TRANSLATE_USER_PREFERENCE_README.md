# 🎛️ AI Auto-Translation User Preference Feature

## Overview

This feature allows users to **control whether AI auto-translation is enabled** for their account. Users can now choose between automatic AI translation or viewing content in its original language (English/Filipino).

---

## 🌟 What This Adds

### Before
- ❌ All users automatically get AI translations
- ❌ No way to disable it
- ❌ Privacy-conscious users have no choice

### After
- ✅ Users can enable/disable AI translation
- ✅ Toggle in profile settings
- ✅ Preference saved to database
- ✅ Clear visual feedback
- ✅ Works for guests and logged-in users

---

## 📦 What's Included

### Database Changes
1. **New Column**: `user_preferences.auto_translate_enabled`
   - Type: `TINYINT(1)` (boolean)
   - Default: `1` (enabled)
   - Indexed for performance

### Backend Files
1. **Migration Script**: `ADMIN/api/add-auto-translate-preference.sql`
2. **Setup Script**: `ADMIN/api/setup-auto-translate-preference.php`
3. **Updated API**: `USERS/api/user-language-preference.php`
4. **Updated Translation API**: `USERS/api/get-translations.php`

### Frontend Files
1. **Updated Profile**: `USERS/profile.php`
   - New checkbox for auto-translate preference
   - Save/load functionality
   
2. **Updated JavaScript**: `USERS/js/translations.js`
   - Checks user preference before translating
   - Shows notification when disabled

### Documentation
1. **Full Guide**: `DOCS/USER_CONTROLLED_AUTO_TRANSLATION.md`
2. **Quick Start**: `USER_AUTO_TRANSLATE_QUICK_START.md`
3. **This README**: `AUTO_TRANSLATE_USER_PREFERENCE_README.md`

---

## 🚀 Installation

### Option 1: Quick Setup (Recommended)

Visit this URL in your browser:

```
http://your-domain.com/ADMIN/api/setup-auto-translate-preference.php
```

### Option 2: Manual SQL

Run the SQL script:

```bash
mysql -u your_user -p your_database < ADMIN/api/add-auto-translate-preference.sql
```

### Verify Installation

```sql
-- Check if column exists
DESCRIBE user_preferences;

-- Should show:
-- auto_translate_enabled | tinyint(1) | YES | | 1 |
```

---

## 🎯 How Users Use It

### Step 1: Go to Profile Settings
Navigate to: **Profile** → **Language Settings**

### Step 2: Find the Toggle
Look for:
```
☑️ Enable AI Auto-Translation

When enabled, content will be automatically translated 
to your preferred language using AI. Disable this if 
you prefer to view content in its original language.
```

### Step 3: Choose Preference
- **Checked** = AI translation enabled (default)
- **Unchecked** = Show original English/Filipino only

### Step 4: Save
Click **"Save Language Settings"**

### Step 5: See Results
- **If enabled**: Content translates to selected language
- **If disabled**: Content shows in English + notification appears

---

## 🔍 How It Works Technically

### Frontend Flow
```javascript
1. User selects language (e.g., Spanish)
2. JavaScript checks: localStorage.getItem('auto_translate_enabled')
3. If disabled → Show English + notification
4. If enabled → Fetch AI translation from API
```

### Backend Flow
```php
1. API receives translation request
2. Check session for user_id
3. Query: SELECT auto_translate_enabled FROM user_preferences
4. If disabled → Return English translations
5. If enabled → Perform AI translation
```

### Database Flow
```sql
-- When user saves preference
INSERT INTO user_preferences (user_id, auto_translate_enabled)
VALUES (123, 1)
ON DUPLICATE KEY UPDATE auto_translate_enabled = 1;

-- When loading preference
SELECT auto_translate_enabled 
FROM user_preferences 
WHERE user_id = 123;
```

---

## 📊 Default Behavior

| Scenario | Auto-Translate Setting | Behavior |
|----------|----------------------|----------|
| New user | ✅ Enabled (default) | AI translation works |
| Existing user | ✅ Enabled (default) | No change from before |
| User disables it | ❌ Disabled | Shows English content |
| Guest user | ✅ Enabled (default) | Saved to localStorage |
| English selected | N/A | Always works (no AI) |
| Filipino selected | N/A | Always works (pre-translated) |

---

## 🎨 Visual Elements

### Profile Settings Checkbox
```
┌─────────────────────────────────────────────────┐
│ Preferred Language                              │
│ [Dropdown: Spanish ▼]                           │
│                                                  │
│ ☑️ Enable AI Auto-Translation                   │
│                                                  │
│ When enabled, content will be automatically     │
│ translated to your preferred language using AI. │
│                                                  │
│ [Save Language Settings]                        │
└─────────────────────────────────────────────────┘
```

### Notification (when disabled)
```
┌─────────────────────────────────────────────────┐
│ ℹ️  AI Translation Disabled                  [×]│
│                                                  │
│ You've disabled auto-translation. Showing       │
│ content in English. To view in Spanish, enable  │
│ AI translation in your profile settings.        │
└─────────────────────────────────────────────────┘
```

---

## 🧪 Testing Scenarios

### Test 1: Enable Auto-Translation
1. Login
2. Go to Profile → Language Settings
3. Check "Enable AI Auto-Translation"
4. Select Spanish
5. Save
6. **Expected**: Page translates to Spanish

### Test 2: Disable Auto-Translation
1. Login
2. Go to Profile → Language Settings
3. Uncheck "Enable AI Auto-Translation"
4. Select Spanish
5. Save
6. **Expected**: 
   - Page shows English
   - Purple notification appears
   - Link to profile settings in notification

### Test 3: Guest User
1. Open in incognito mode
2. Select Spanish
3. **Expected**: Translates (default enabled)
4. Go to profile, disable auto-translate
5. **Expected**: Shows English (saved to localStorage)

### Test 4: Persistence
1. Login, disable auto-translate
2. Logout
3. Login again
4. **Expected**: Still disabled (from database)

---

## 🔧 Configuration

### For Developers

**Enable for all users (default)**:
```sql
UPDATE user_preferences SET auto_translate_enabled = 1;
```

**Disable for all users**:
```sql
UPDATE user_preferences SET auto_translate_enabled = 0;
```

**Check statistics**:
```sql
SELECT 
    auto_translate_enabled,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM user_preferences), 2) as percentage
FROM user_preferences
GROUP BY auto_translate_enabled;
```

---

## 🐛 Troubleshooting

### Problem: Checkbox doesn't save

**Solution**:
1. Check browser console for errors
2. Verify API endpoint: `user-language-preference.php`
3. Check database connection
4. Ensure user is logged in

### Problem: Still translating when disabled

**Solution**:
```javascript
// Clear localStorage
localStorage.clear();

// Clear sessionStorage
sessionStorage.clear();

// Reload page
location.reload();
```

### Problem: Database error

**Solution**:
```sql
-- Check if column exists
SHOW COLUMNS FROM user_preferences LIKE 'auto_translate_enabled';

-- If not, run migration
SOURCE ADMIN/api/add-auto-translate-preference.sql;
```

---

## 📈 Benefits

### For Users
- ✅ **Control**: Choose whether to use AI translation
- ✅ **Privacy**: Can opt-out of AI services
- ✅ **Flexibility**: Toggle on/off anytime
- ✅ **Clarity**: Clear feedback when disabled

### For System
- ✅ **Cost Savings**: Fewer AI API calls if users disable
- ✅ **Performance**: No translation delay for disabled users
- ✅ **Compliance**: Respects user privacy preferences
- ✅ **Flexibility**: Easy to extend with more options

---

## 🔐 Security & Privacy

- ✅ Preference stored securely in database
- ✅ No sensitive data in localStorage
- ✅ API requires valid session
- ✅ SQL injection protected (prepared statements)
- ✅ Users can opt-out of AI translation
- ✅ Original content always available

---

## 📚 Documentation

### Quick Start
Read: `USER_AUTO_TRANSLATE_QUICK_START.md`
- 5-minute setup guide
- Step-by-step instructions
- Common issues & fixes

### Full Documentation
Read: `DOCS/USER_CONTROLLED_AUTO_TRANSLATION.md`
- Complete technical details
- API documentation
- Code examples
- Testing guide
- Future enhancements

---

## 🎯 Use Cases

### Use Case 1: Privacy-Conscious User
**Problem**: User doesn't want content sent to AI services  
**Solution**: Disable auto-translation, view original content

### Use Case 2: Quality Control
**Problem**: AI translations are inaccurate  
**Solution**: Disable auto-translation, read English original

### Use Case 3: Bilingual User
**Problem**: User understands English but prefers Spanish UI  
**Solution**: Keep auto-translation enabled, enjoy natural translations

### Use Case 4: Testing
**Problem**: Developer needs to test without AI calls  
**Solution**: Disable auto-translation during development

---

## 📞 Support

### For End Users
- Go to Profile → Language Settings
- Toggle "Enable AI Auto-Translation"
- Save settings
- Contact support if issues persist

### For Developers
- Read full documentation
- Check API responses in Network tab
- Review console logs
- Test with different user accounts

---

## ✅ Success Criteria

Your implementation is complete when:

- ✅ Database migration successful
- ✅ Checkbox appears in profile.php
- ✅ Checkbox state loads from database
- ✅ Saving updates database
- ✅ API respects preference
- ✅ Notification appears when disabled
- ✅ English shows when disabled
- ✅ AI translation works when enabled
- ✅ Preference persists after logout/login
- ✅ Works for both guests and logged-in users

---

## 📊 Statistics

After deployment, monitor:

```sql
-- Total users with preference set
SELECT COUNT(*) FROM user_preferences;

-- Enabled vs Disabled
SELECT 
    CASE WHEN auto_translate_enabled = 1 THEN 'Enabled' ELSE 'Disabled' END as status,
    COUNT(*) as count
FROM user_preferences
GROUP BY auto_translate_enabled;

-- Most popular languages with auto-translate disabled
SELECT 
    preferred_language,
    COUNT(*) as count
FROM user_preferences
WHERE auto_translate_enabled = 0
GROUP BY preferred_language
ORDER BY count DESC
LIMIT 10;
```

---

## 🚀 Next Steps

1. **Run the migration**: `setup-auto-translate-preference.php`
2. **Test the feature**: Try enabling/disabling in profile
3. **Inform users**: Let them know about the new option
4. **Monitor usage**: Check statistics to see adoption
5. **Gather feedback**: Ask users about their experience

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Dec 29, 2025 | Initial release |
|  |  | - Database migration |
|  |  | - Profile UI toggle |
|  |  | - API integration |
|  |  | - Visual notifications |
|  |  | - Full documentation |

---

## 🎉 Summary

This feature empowers users with **choice and control** over AI auto-translation:

- **Default**: Enabled (maintains current behavior)
- **User Control**: Can disable in profile settings
- **Persistent**: Saved to database
- **Clear Feedback**: Visual notifications
- **Privacy-Friendly**: No AI calls when disabled
- **Backward Compatible**: Existing users unaffected

Users now have the power to choose how they experience your multilingual emergency communication system! 🌍

---

**Status**: ✅ Production Ready  
**Compatibility**: PHP 7.4+, MySQL 5.7+, All modern browsers  
**Setup Time**: ~5 minutes  
**Complexity**: Low  
**Impact**: High  

---

**Need Help?**
- Quick Start: `USER_AUTO_TRANSLATE_QUICK_START.md`
- Full Docs: `DOCS/USER_CONTROLLED_AUTO_TRANSLATION.md`
- Support: Check console logs and API responses

