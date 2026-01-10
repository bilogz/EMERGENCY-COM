# Config File vs Database - How API Keys Work

## 🔄 **How It Works**

### **Priority Order (What Gets Used First):**

```
1. Database (api_keys_management table)  ← HIGHEST PRIORITY
2. Config File (config.local.php)         ← FALLBACK
3. Old Database Table (integration_settings)
4. Environment Variables
```

### **What This Means:**

✅ **If you update keys via the UI:**
- Keys are saved to **database** (immediate effect)
- Keys are also synced to **config.local.php** (backup)
- **Takes effect immediately** - database is used right away

❌ **If you manually edit config.local.php:**
- Changes are **NOT used immediately** if database has the key
- Config file is only used as **fallback** if database is empty
- You need to **sync** to make config changes active

---

## 📝 **Two Ways to Update Keys**

### **Method 1: Via UI (Recommended)** ✅

**Steps:**
1. Open API Key Management modal
2. Enter/update key values
3. Click "Save Changes (Requires OTP)"
4. Enter OTP from email
5. ✅ **Keys active immediately**

**What Happens:**
- ✅ Saved to database → **Used immediately**
- ✅ Synced to config.local.php → Backup created
- ✅ Change logged → Audit trail
- ✅ OTP verified → Security

---

### **Method 2: Manual Config Edit + Sync** 🔄

**Steps:**
1. Edit `config.local.php` on server (via SSH/FTP)
2. Update key values directly in file
3. Open API Key Management modal
4. Click **"Sync from Config File"** button
5. ✅ **Keys imported to database and active**

**What Happens:**
- ✅ Config file read → Keys extracted
- ✅ Database updated → Keys now active
- ✅ Change logged → Audit trail
- ⚠️ No OTP required → Faster but less secure

---

## 🎯 **When to Use Each Method**

### **Use UI Method When:**
- ✅ You want full security (OTP required)
- ✅ You want immediate effect
- ✅ You want complete audit trail
- ✅ You're making changes from admin panel
- ✅ You want to test keys before saving

### **Use Config + Sync When:**
- ✅ You're updating keys via SSH/terminal
- ✅ You're doing bulk updates
- ✅ You're restoring from backup
- ✅ You need to update keys quickly
- ✅ You're migrating keys from another system

---

## 🔍 **Example Scenarios**

### **Scenario 1: Manual Config Edit**

```php
// You edit config.local.php on server:
'AI_API_KEY_ANALYSIS' => 'AIzaSyNEW_KEY_HERE',
```

**Result:**
- ❌ **NOT used** - Database still has old key
- ⚠️ System uses database key (old one)
- ✅ Config file has new key (but unused)

**Solution:**
- Click "Sync from Config File" button
- ✅ New key imported to database
- ✅ Now active immediately

---

### **Scenario 2: UI Update**

```javascript
// You update via UI modal:
1. Enter new key: "AIzaSyNEW_KEY_HERE"
2. Click "Save Changes"
3. Enter OTP: "123456"
```

**Result:**
- ✅ **Used immediately** - Database updated
- ✅ Config file synced automatically
- ✅ Change logged with OTP verification

---

## 🛠️ **Sync Function Details**

### **What "Sync from Config File" Does:**

1. **Reads** `config.local.php` file
2. **Extracts** all API keys:
   - `AI_API_KEY`
   - `AI_API_KEY_TRANSLATION`
   - `AI_API_KEY_ANALYSIS`
   - `AI_API_KEY_ANALYSIS_BACKUP`
   - `AI_API_KEY_EARTHQUAKE`

3. **Updates** database:
   - If key exists → Updates value
   - If key is new → Creates new record
   - If key unchanged → Skips (no update)

4. **Logs** the sync:
   - Who synced (admin)
   - When synced (timestamp)
   - Which keys changed
   - IP address

5. **Returns** summary:
   - How many keys synced
   - Which keys updated
   - Which keys skipped

---

## 📊 **Sync Results Example**

After clicking "Sync from Config File", you'll see:

```
✅ Successfully synced 3 key(s) from config file!

Updated keys:
AI_API_KEY_ANALYSIS
AI_API_KEY_ANALYSIS_BACKUP
AI_API_KEY_EARTHQUAKE (new)

Skipped (unchanged):
AI_API_KEY (unchanged)
AI_API_KEY_TRANSLATION (unchanged)
```

---

## ⚠️ **Important Notes**

### **Security Considerations:**

1. **UI Updates Require OTP:**
   - ✅ More secure
   - ✅ Prevents unauthorized changes
   - ✅ Complete audit trail

2. **Config Sync is Faster:**
   - ⚠️ No OTP required
   - ⚠️ Less secure
   - ✅ Still logged in audit trail

3. **Best Practice:**
   - Use UI for normal updates
   - Use config sync for migrations/restores
   - Always verify keys after sync

---

## 🔄 **Auto-Sync Option (Future)**

Currently, you need to manually click "Sync from Config File".

**Future Enhancement:**
- Auto-detect config file changes
- Background sync process
- Notification when config differs from database

---

## 🧪 **Testing Config Sync**

### **Test Steps:**

1. **Edit config.local.php:**
   ```php
   'AI_API_KEY_ANALYSIS' => 'AIzaSyTEST_KEY_12345',
   ```

2. **Open API Key Management modal**

3. **Click "Sync from Config File"**

4. **Verify:**
   - ✅ Key updated in database
   - ✅ Modal shows new key value
   - ✅ Test button works with new key

5. **Check logs:**
   ```sql
   SELECT * FROM api_key_change_logs 
   WHERE action = 'update' 
   AND notes LIKE '%Synced from config%'
   ORDER BY created_at DESC;
   ```

---

## 📝 **Quick Reference**

| Action | Method | OTP Required | Immediate Effect |
|--------|--------|--------------|------------------|
| Update via UI | UI Modal | ✅ Yes | ✅ Yes |
| Edit config + Sync | Config File | ❌ No | ✅ After Sync |
| Direct config edit | Config File | ❌ No | ❌ No (not used) |

---

## 🎯 **Recommendation**

**For Production:**
- ✅ Use **UI method** for all updates
- ✅ Requires OTP (more secure)
- ✅ Complete audit trail
- ✅ Immediate effect

**For Development/Migration:**
- ✅ Edit config file directly
- ✅ Click "Sync from Config File"
- ✅ Faster bulk updates
- ✅ Still logged in audit trail

---

## 💡 **Pro Tips**

1. **Always test keys** after syncing
2. **Check audit logs** to see what changed
3. **Keep config file** as backup
4. **Use UI for production** changes
5. **Use sync for migrations** or bulk updates

---

## 🆘 **Troubleshooting**

### **Issue: Config changes not working**

**Problem:** You edited config.local.php but system still uses old key

**Solution:**
1. Click "Sync from Config File" button
2. Verify sync completed successfully
3. Check database has new key value
4. Test the key

### **Issue: Sync button not working**

**Check:**
- ✅ File permissions on config.local.php
- ✅ Config file syntax is valid PHP
- ✅ Keys are in correct format
- ✅ Database connection working

### **Issue: Keys not syncing**

**Check:**
- ✅ Config file path is correct
- ✅ Keys exist in config file
- ✅ Key names match exactly:
  - `AI_API_KEY`
  - `AI_API_KEY_TRANSLATION`
  - `AI_API_KEY_ANALYSIS`
  - `AI_API_KEY_ANALYSIS_BACKUP`
  - `AI_API_KEY_EARTHQUAKE`

---

## 📚 **Related Documentation**

- `API_KEY_MANAGEMENT_GUIDE.md` - Complete guide
- `IMPLEMENTATION_SUMMARY.md` - Technical details
- `QUICK_START.md` - 5-minute setup

---

**Last Updated:** <?php echo date('Y-m-d'); ?>  
**Version:** 1.0.0



