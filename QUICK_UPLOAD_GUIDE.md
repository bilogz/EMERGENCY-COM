# Quick APK Upload Guide

## ✅ New APK Ready to Upload

**Local File Location:**
- Path: `c:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
- Size: 16.2 MB
- Version: 1.0.2 (Version Code: 3)
- Created: Just now

**Server Location to Upload To:**
- Remote Path: `/EMERGENCY-COM/USERS/downloads/emergency-comms-app.apk`
- Or: `/public_html/EMERGENCY-COM/USERS/downloads/emergency-comms-app.apk`
- URL: https://emergency-comm.alertaraqc.com/USERS/downloads/emergency-comms-app.apk

---

## 🚀 Fastest Method: cPanel File Manager

### Step 1: Login to cPanel
1. Go to: **https://alertaraqc.com/cpanel** (or your cPanel URL)
2. Login with your credentials

### Step 2: Open File Manager
1. Find **"File Manager"** in cPanel
2. Click to open it
3. Make sure "Show Hidden Files" is checked

### Step 3: Navigate to Downloads Folder
1. Navigate to: `public_html/EMERGENCY-COM/USERS/downloads/`
   - OR: `EMERGENCY-COM/USERS/downloads/`
   - (depends on your server setup)

### Step 4: Delete Old APK (Optional but Recommended)
1. Find `emergency-comms-app.apk`
2. **Right-click** → **Delete** (or select and click Delete button)
3. Confirm deletion

### Step 5: Upload New APK
1. Click **"Upload"** button at the top
2. Click **"Select File"** or drag and drop
3. Navigate to: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\`
4. Select: `emergency-comms-app.apk`
5. Wait for upload to complete (may take 1-2 minutes for 16MB)

### Step 6: Verify Upload
1. Check file size: Should be **16,229,208 bytes** (16.2 MB)
2. Check timestamp: Should be recent (today's date/time)
3. Check file permissions: Should be **644** or **755**

### Step 7: Test Download
1. Open a new browser window (or incognito/private mode)
2. Visit: https://emergency-comm.alertaraqc.com/index.php
3. Click "Download APK" button
4. Install on your phone
5. Check version in app: Should show **1.0.2**

---

## 🔄 Alternative: FileZilla (FTP)

If you have FileZilla installed:

1. **Connect to Server:**
   - Host: `alertaraqc.com` (or server IP)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21 (FTP) or 22 (SFTP)

2. **Navigate:**
   - Left side (Local): `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\`
   - Right side (Remote): `/public_html/EMERGENCY-COM/USERS/downloads/` or `/EMERGENCY-COM/USERS/downloads/`

3. **Upload:**
   - Drag `emergency-comms-app.apk` from left to right
   - OR: Right-click → Upload
   - Overwrite if asked

---

## ⚠️ Troubleshooting

### Browser Shows Old Version After Upload
1. **Clear browser cache:**
   - Press `Ctrl + Shift + Delete`
   - Select "Cached images and files"
   - Clear data

2. **Hard refresh:**
   - Press `Ctrl + F5` on the download page

3. **Test in incognito/private mode:**
   - Open browser in private/incognito mode
   - Download the APK
   - This bypasses cache

### Upload Fails
- Check file size limit (should allow at least 20 MB)
- Check disk space on server
- Try SFTP instead of FTP
- Verify file permissions (644 or 755)

### File Not Found on Server
- Check if path is `/public_html/EMERGENCY-COM/USERS/downloads/`
- Or `/home/username/public_html/EMERGENCY-COM/USERS/downloads/`
- Or `/var/www/html/EMERGENCY-COM/USERS/downloads/`

---

## ✅ After Upload Checklist

- [ ] File uploaded to server successfully
- [ ] File size matches (16.2 MB)
- [ ] File permissions set correctly (644 or 755)
- [ ] Tested download from website (in incognito mode)
- [ ] Installed APK on phone
- [ ] Verified app version shows 1.0.2
- [ ] Tested app connects to production server

---

**Note:** The new APK (v1.0.2) is configured to connect to:
`https://emergency-comm.alertaraqc.com/EMERGENCY-COM/USERS/api/`


