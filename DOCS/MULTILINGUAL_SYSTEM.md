# Enhanced Multilingual Support System

## Overview

The Enhanced Multilingual Support System provides comprehensive language support for the Emergency Communication Portal, featuring:

- **AI-Powered Translation**: Automatic translation using Google Gemini AI
- **Manual Translation**: Support for manual translations with review
- **Multi-Language Support**: Support for 50+ languages
- **Activity Logging**: Complete audit trail of all translation activities
- **Admin Integration**: Seamless integration with admin activity logs

## Features

### 1. AI Translation (Powered by Gemini)
- Automatic translation of alerts and content
- Supports 50+ languages
- Context-aware translations for emergency communications
- Quality-focused translations with appropriate tone

### 2. Manual Translation
- Support for manual translations when AI is unavailable
- Edit and refine AI-generated translations
- Full control over translation quality

### 3. Language Management
- Manage supported languages
- Enable/disable languages
- Set language priorities
- Track AI support availability per language

### 4. Activity Logging
- Complete audit trail of all translation activities
- Track which admin performed translations
- Log translation method (AI vs Manual)
- Monitor translation success/failure rates
- Integration with admin activity logs

## Setup Instructions

### Step 1: Run Database Setup

Run the setup script to create necessary tables:

```bash
# Via browser
http://your-domain/EMERGENCY-COM/ADMIN/api/setup-multilingual.php

# Or via command line
php EMERGENCY-COM/ADMIN/api/setup-multilingual.php
```

This will:
- Create/update `alert_translations` table with admin tracking
- Create `supported_languages` table
- Create `translation_activity_logs` table
- Insert default language data

### Step 2: Configure Gemini API Key

If you haven't already configured the Gemini API key:

```bash
# Via browser
http://your-domain/EMERGENCY-COM/ADMIN/api/setup-gemini-key.php

# Or edit setup-gemini-key.php and add your API key
```

The API key will be stored in the `integration_settings` table.

### Step 3: Verify Setup

Check that everything is configured correctly:

1. Visit the Multilingual Support page in admin panel
2. Verify languages are loaded
3. Test AI translation with a sample alert

## Usage Guide

### For Administrators

#### Translating an Alert

1. Navigate to **Multilingual Support** in the admin sidebar
2. Select an alert from the dropdown
3. Choose target language
4. Enable "Use AI Translation" checkbox (if available)
5. Click "Translate with AI" or provide manual translation
6. Review and save

#### Viewing Translation History

1. Scroll to "Translation History" section
2. View all translations with:
   - Translation method (AI/Manual)
   - Admin who created translation
   - Status and timestamps

#### Viewing Activity Logs

1. Scroll to "Translation Activity Logs" section
2. Click "View Activity Logs"
3. See complete audit trail including:
   - Date and time
   - Action type
   - Alert information
   - Source and target languages
   - Translation method
   - Success/failure status

### Supported Languages

The system supports 50+ languages including:

**Philippine Languages:**
- English (en)
- Filipino/Tagalog (fil/tl)
- Cebuano (ceb)
- Ilocano (ilo)
- Kapampangan (pam)
- Bicolano (bcl)
- Waray (war)

**International Languages:**
- Spanish (es)
- French (fr)
- German (de)
- Italian (it)
- Portuguese (pt)
- Chinese (zh)
- Japanese (ja)
- Korean (ko)
- Arabic (ar)
- Hindi (hi)
- Thai (th)
- Vietnamese (vi)
- Indonesian (id)
- Malay (ms)
- Russian (ru)
- Turkish (tr)
- And many more...

## API Reference

### Translate Alert (AI)

```javascript
POST /ADMIN/api/multilingual-alerts.php
Content-Type: application/json

{
    "alert_id": 1,
    "target_language": "fil",
    "use_ai": true,
    "source_language": "en"
}
```

### Translate Alert (Manual)

```javascript
POST /ADMIN/api/multilingual-alerts.php
Content-Type: application/json

{
    "alert_id": 1,
    "target_language": "fil",
    "translated_title": "Translated Title",
    "translated_content": "Translated Content",
    "use_ai": false
}
```

### List Translations

```javascript
GET /ADMIN/api/multilingual-alerts.php?action=list
GET /ADMIN/api/multilingual-alerts.php?action=list&alert_id=1
```

### Get Supported Languages

```javascript
GET /ADMIN/api/multilingual-alerts.php?action=languages
```

### Get Activity Logs

```javascript
GET /ADMIN/api/multilingual-alerts.php?action=activity&limit=50&offset=0
```

### Delete Translation

```javascript
DELETE /ADMIN/api/multilingual-alerts.php
Content-Type: application/json

{
    "id": 123
}
```

## Database Schema

### alert_translations

Stores translations for alerts with admin tracking:

- `id` - Primary key
- `alert_id` - Reference to alerts table
- `target_language` - Language code (e.g., 'fil', 'es')
- `translated_title` - Translated title
- `translated_content` - Translated content
- `translated_by_admin_id` - Admin who created translation
- `translation_method` - 'ai', 'manual', or 'hybrid'
- `status` - 'active', 'inactive'
- `translated_at` - Timestamp

### supported_languages

Manages supported languages:

- `id` - Primary key
- `language_code` - ISO language code
- `language_name` - Display name
- `native_name` - Native name
- `flag_emoji` - Flag emoji
- `is_active` - Active status
- `is_ai_supported` - AI translation available
- `priority` - Display priority

### translation_activity_logs

Complete audit trail:

- `id` - Primary key
- `admin_id` - Admin who performed action
- `action_type` - Type of action
- `alert_id` - Related alert
- `translation_id` - Related translation
- `source_language` - Source language
- `target_language` - Target language
- `translation_method` - Method used
- `success` - Success status
- `error_message` - Error if failed
- `metadata` - Additional JSON data
- `created_at` - Timestamp

## Activity Logging

All translation activities are automatically logged:

1. **Admin Activity Logs** (`admin_activity_logs`)
   - High-level activity tracking
   - Integrated with existing admin logs

2. **Translation Activity Logs** (`translation_activity_logs`)
   - Detailed translation-specific logs
   - Includes metadata and error tracking

### Logged Actions

- `ai_translate` - AI translation performed
- `create_translation` - Translation created
- `update_translation` - Translation updated
- `delete_translation` - Translation deleted

## Troubleshooting

### AI Translation Not Available

1. Check Gemini API key is configured:
   ```sql
   SELECT * FROM integration_settings WHERE source = 'gemini';
   ```

2. Verify API key is valid by testing:
   ```bash
   php EMERGENCY-COM/ADMIN/api/ai-translation-service.php
   ```

3. Check error logs for API errors

### Translations Not Saving

1. Verify database tables exist:
   ```sql
   SHOW TABLES LIKE 'alert_translations';
   ```

2. Check admin session is active
3. Verify alert exists
4. Check database permissions

### Activity Logs Not Showing

1. Verify `translation_activity_logs` table exists
2. Check admin is logged in
3. Verify admin_id matches session

## Best Practices

1. **Use AI for Initial Translation**: Let AI handle initial translation, then review and refine manually if needed

2. **Review Critical Alerts**: Always review AI translations for critical emergency alerts

3. **Monitor Activity Logs**: Regularly check activity logs for translation quality and errors

4. **Language Priority**: Set higher priority for commonly used languages

5. **Fallback to Manual**: If AI translation fails, use manual translation as fallback

## Security Considerations

1. **API Key Security**: Store Gemini API key securely in database
2. **Admin Authentication**: All translation actions require admin authentication
3. **Activity Logging**: All actions are logged for audit purposes
4. **Input Validation**: All inputs are validated and sanitized
5. **Error Handling**: Errors are logged but not exposed to users

## Future Enhancements

- Translation quality scoring
- Batch translation for multiple alerts
- Translation memory/cache
- Community-contributed translations
- Real-time translation preview
- Translation workflow approval

## Support

For issues or questions:
1. Check activity logs for errors
2. Review database logs
3. Verify API configuration
4. Contact system administrator

---

**Last Updated**: 2024
**Version**: 2.0
**Author**: Emergency Communication System Team

