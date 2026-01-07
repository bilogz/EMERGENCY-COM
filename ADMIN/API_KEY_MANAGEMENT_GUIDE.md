# API Key Management System - Complete Guide

## 🔐 Overview

This system provides secure, user-friendly management of all Gemini AI API keys with enterprise-grade security features including:

- **OTP Email Verification** for all key changes
- **Automatic Key Rotation** when quota limits are reached
- **Categorized Key Management** for different purposes
- **Usage Tracking & Analytics**
- **User-Side Auto-Warning Control**

## 🎯 Features

### For Administrators

#### 1. **Secure API Key Management**
- Manage multiple API keys for different purposes
- Categories:
  - **General AI Operations** (`AI_API_KEY`)
  - **Translation Services** (`AI_API_KEY_TRANSLATION`)
  - **Disaster Monitoring Analysis** (`AI_API_KEY_ANALYSIS`)
  - **Backup Keys** (`AI_API_KEY_ANALYSIS_BACKUP`)
  - **Earthquake Monitoring** (`AI_API_KEY_EARTHQUAKE`)

#### 2. **OTP Security**
- All key changes require email OTP verification
- 6-digit code sent to admin email
- 10-minute expiration
- Prevents unauthorized key modifications

#### 3. **Auto-Rotation System**
- Automatically switches to backup keys when quota exceeded
- Configurable per-key basis
- Email notifications to admins
- Detailed rotation logs

#### 4. **Usage Analytics**
- Track API call counts per key
- Monitor quota exceeded events
- View last usage timestamps
- Change history audit log

### For Users

#### 1. **Auto-Warning Preferences**
- Enable/disable AI-powered automatic warnings
- Select specific disaster categories
- Choose alert frequency (realtime, hourly, daily)
- Set minimum severity level
- Manage notification channels

## 📝 How to Use

### Admin: Managing API Keys

#### Step 1: Access API Key Management
1. Go to **Automated Warning Integration** page
2. Click on **"API Key Management"** card in Settings section

#### Step 2: Configure Keys
1. Review all API keys organized by category
2. Click on any key to edit
3. Enter or update the API key value
4. Enable **Auto-Rotation** if you have backup keys configured
5. Click **"Test"** button to verify the key works

#### Step 3: Save with OTP Verification
1. Click **"Save Changes (Requires OTP)"**
2. System sends 6-digit OTP to your admin email
3. Enter OTP code in the verification modal
4. Click **"Verify & Save"**
5. Keys are updated and synced to `config.local.php`

### Admin: Setting Up Auto-Rotation

#### For Disaster Analysis Keys:
1. Add both primary and backup keys:
   - `AI_API_KEY_ANALYSIS` (primary)
   - `AI_API_KEY_ANALYSIS_BACKUP` (backup)
2. Enable **Auto-Rotation** on the primary key
3. System will automatically switch when quota exceeded
4. You'll receive email notification when rotation occurs

### User: Configuring Auto-Warnings

#### Step 1: Access Settings
1. Navigate to **Auto-Warning Settings** page
2. URL: `/USERS/auto-warning-settings.php`

#### Step 2: Enable Auto-Warnings
1. Toggle **"Enable AI Auto-Warnings"** switch
2. Select disaster categories you want alerts for:
   - Heavy Rain
   - Flooding
   - Earthquake
   - Strong Winds
   - Tsunami
   - Landslide
   - Thunderstorm
   - Volcanic Ash Fall
   - Fire Incident
   - Typhoon/Storm

#### Step 3: Configure Preferences
1. **Alert Frequency:**
   - Real-time (Immediate) - Get alerts as they happen
   - Hourly Summary - Receive consolidated updates every hour
   - Daily Summary - Once-daily digest

2. **Minimum Severity:**
   - All Alerts - Receive everything
   - High Priority Only - Important warnings only
   - Critical Only - Life-threatening situations only

3. **Notification Channels:**
   - SMS (if enabled in main settings)
   - Email (if enabled in main settings)
   - Push Notifications (if enabled)

#### Step 4: Save Settings
Click **"Save Settings"** - No OTP required for users

## 🔄 Automatic Key Rotation Details

### How It Works

1. **Detection:**
   - System makes API call with primary key
   - Receives quota exceeded error (HTTP 429 or "RESOURCE_EXHAUSTED")

2. **Rotation:**
   - System checks if auto-rotation is enabled for that key
   - Finds corresponding backup key
   - Automatically retries with backup key
   - Updates usage statistics

3. **Notification:**
   - Email sent to all active admins
   - Contains details: original key, backup key, timestamp
   - Action items listed for admin follow-up

4. **Logging:**
   - Event logged in `api_key_change_logs` table
   - Records: rotation time, reason, keys involved
   - Visible in admin dashboard

### Key Rotation Pairs

| Primary Key | Backup Key | Purpose |
|------------|-----------|---------|
| `AI_API_KEY_ANALYSIS` | `AI_API_KEY_ANALYSIS_BACKUP` | Disaster monitoring |
| `AI_API_KEY` | `AI_API_KEY_TRANSLATION` | Fallback general use |

## 📊 Usage Analytics

### View Key Statistics
1. Go to API Key Management modal
2. Each key card shows:
   - **Usage Count:** Total API calls made
   - **Quota Exceeded Count:** How many times limit was hit
   - **Last Used:** Most recent API call timestamp
   - **Last Rotated:** When auto-rotation last occurred

### Change History
- Track all key modifications
- View OTP verification status
- See which admin made changes
- Review old/new key previews

## 🔐 Security Best Practices

### For Administrators:

1. **Protect Your Email:**
   - OTP codes are sent to admin email
   - Use strong email password
   - Enable 2FA on email account

2. **Key Rotation:**
   - Set up backup keys for critical services
   - Enable auto-rotation for production keys
   - Monitor rotation notifications

3. **Regular Audits:**
   - Review change logs monthly
   - Check for unusual activity
   - Verify all active keys

4. **Key Management:**
   - Never share keys in plain text
   - Use different keys for dev/production
   - Rotate keys periodically (every 3-6 months)

### For Users:

1. **Choose Categories Wisely:**
   - Only subscribe to relevant disaster types
   - Avoid alert fatigue from too many notifications

2. **Set Appropriate Severity:**
   - Critical: Immediate life threats
   - High: Significant dangers
   - All: Complete awareness (may be noisy)

3. **Test Your Channels:**
   - Verify SMS/Email work correctly
   - Ensure push notifications are enabled
   - Check spam folders for missed alerts

## 🚨 Troubleshooting

### Issue: OTP Not Received

**Solutions:**
1. Check spam/junk folder
2. Wait 1-2 minutes (email delay)
3. Click "Resend OTP"
4. Contact system admin if persistent

### Issue: API Key Test Fails

**Possible Causes:**
1. **Invalid Key:** Double-check the key value
2. **Quota Exceeded:** Key has hit daily limit
3. **Network Error:** Check internet connection
4. **Key Disabled:** Verify key is active in Google Cloud Console

**Solutions:**
- Try backup key if available
- Wait for quota reset (usually 24 hours)
- Generate new key in Google Cloud Console

### Issue: Auto-Rotation Not Working

**Checklist:**
1. ✓ Auto-rotation enabled for primary key?
2. ✓ Backup key configured and active?
3. ✓ Backup key has available quota?
4. ✓ Database tables created properly?

**Check Logs:**
```sql
SELECT * FROM api_key_change_logs 
WHERE action = 'rotate' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Issue: Users Not Receiving Auto-Warnings

**Checklist:**
1. ✓ AI auto-warnings enabled in user preferences?
2. ✓ User selected disaster categories?
3. ✓ User notification channels enabled?
4. ✓ Admin has configured AI settings with valid API key?
5. ✓ AI warning system is active?

## 📁 Database Schema

### `api_keys_management` Table
```sql
- id: Primary key
- key_name: Unique identifier (e.g., AI_API_KEY_ANALYSIS)
- key_label: Human-readable label
- key_value: Encrypted API key
- key_category: Category (translation, analysis, earthquake, backup, general)
- is_active: Active status
- auto_rotate: Auto-rotation enabled
- usage_count: Total API calls
- quota_exceeded_count: Times quota hit
- last_used: Last usage timestamp
- last_rotated: Last rotation timestamp
```

### `api_key_change_logs` Table
```sql
- id: Primary key
- key_name: Which key was changed
- action: update, delete, rotate, test
- admin_id: Admin who made change
- admin_email: Admin email
- ip_address: Request IP
- otp_verified: OTP verification status
- old_key_preview: First/last chars of old key
- new_key_preview: First/last chars of new key
- notes: Additional information
```

### `user_preferences` Table (New Columns)
```sql
- auto_warning_enabled: Enable AI auto-warnings
- auto_warning_categories: Comma-separated disaster types
- auto_warning_frequency: realtime, hourly, daily
- auto_warning_severity: all, high, critical
```

## 🔌 API Endpoints

### Admin Endpoints

#### Get All Keys
```http
GET /ADMIN/api/api-key-management.php?action=getKeys
```

#### Request OTP
```http
POST /ADMIN/api/api-key-management.php?action=requestOTP
```

#### Save Keys with OTP
```http
POST /ADMIN/api/api-key-management.php?action=verifyAndSaveKeys
Body: {
  "otp": "123456",
  "keys": [
    {
      "key_name": "AI_API_KEY_ANALYSIS",
      "key_value": "AIza...",
      "auto_rotate": 1
    }
  ]
}
```

#### Test Key
```http
POST /ADMIN/api/api-key-management.php?action=testKey
Body: { "key_value": "AIza..." }
```

### User Endpoints

#### Get Preferences
```http
GET /USERS/api/auto-warning-preferences.php?action=get
```

#### Update Preferences
```http
POST /USERS/api/auto-warning-preferences.php?action=update
Body: {
  "enabled": 1,
  "categories": ["heavy_rain", "flooding", "earthquake"],
  "frequency": "realtime",
  "severity": "high"
}
```

#### Get Available Categories
```http
GET /USERS/api/auto-warning-preferences.php?action=getCategories
```

## 🎨 UI Components

### Admin Modal Components:
- **Category Header:** Groups keys by purpose
- **Key Card:** Shows key details, usage stats, controls
- **OTP Modal:** Secure verification interface
- **Test Button:** Validates key instantly
- **Auto-Rotate Toggle:** Enable/disable per key

### User Page Components:
- **Main Toggle:** Enable/disable all auto-warnings
- **Category Grid:** Visual selection of disaster types
- **Frequency Selector:** Choose notification timing
- **Severity Filter:** Set alert threshold
- **Channel Indicators:** Show available notification methods

## 📚 Code Integration

### Using the API Wrapper with Auto-Rotation

```php
require_once 'gemini-api-wrapper.php';

// Make API call with automatic rotation
$result = callGeminiWithAutoRotation(
    $prompt,           // Your prompt
    'analysis',        // Purpose: 'analysis', 'translation', 'earthquake', 'default'
    'gemini-2.0-flash-exp',  // Model
    ['temperature' => 0.3]   // Options
);

if ($result['success']) {
    $aiResponse = $result['data'];
    // Use response
} else {
    error_log('API Error: ' . $result['error']);
    // Handle error
}
```

### Checking User Auto-Warning Preferences

```php
require_once 'USERS/api/auto-warning-preferences.php';

// Get users who should receive alerts
$users = getUsersWithAutoWarningsEnabled(
    ['flooding', 'landslide'],  // Categories
    'high'                       // Severity
);

foreach ($users as $user) {
    if ($user['sms_notifications']) {
        // Send SMS
    }
    if ($user['email_notifications']) {
        // Send email
    }
    // Check frequency: $user['auto_warning_frequency']
}
```

## 🎉 Benefits

### For Administrators:
- ✅ **Peace of Mind:** Auto-rotation prevents service disruption
- ✅ **Security:** OTP prevents unauthorized changes
- ✅ **Visibility:** Track usage and detect issues early
- ✅ **Organization:** Categorized keys are easy to manage
- ✅ **Audit Trail:** Complete change history

### For Users:
- ✅ **Control:** Choose what alerts you receive
- ✅ **Flexibility:** Adjust frequency and severity
- ✅ **Safety:** Never miss critical warnings
- ✅ **Convenience:** Set once, automatic thereafter
- ✅ **Privacy:** Opt-in system respects preferences

## 🔮 Future Enhancements

Potential additions:
- Multi-admin approval for key changes
- Key expiration reminders
- Quota usage dashboards
- SMS OTP as alternative to email
- Mobile app for key management
- Webhook notifications for rotation events
- Geographic filtering for user alerts
- Machine learning for alert relevance

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review error logs: `error_log()` messages
3. Check database: `api_key_change_logs`, `user_activity_logs`
4. Contact system administrator

---

**Version:** 1.0.0  
**Last Updated:** <?php echo date('Y-m-d'); ?>  
**Author:** Emergency Communication System Team

