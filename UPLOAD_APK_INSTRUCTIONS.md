# How to Upload APK to Production Server

## Method 1: Using FileZilla (FTP/SFTP) - Recommended

### Step 1: Download FileZilla (if you don't have it)
- Download from: https://filezilla-project.org/
- Install it on your computer

### Step 2: Connect to Your Server
1. Open FileZilla
2. Enter your FTP credentials:
   - **Host**: `alertaraqc.com` (or your server IP)
   - **Username**: Your FTP username
   - **Password**: Your FTP password
   - **Port**: 21 (FTP) or 22 (SFTP)
3. Click "Quickconnect"

### Step 3: Navigate to the Downloads Folder
1. On the **right side** (Remote site), navigate to:
   ```
   /EMERGENCY-COM/USERS/downloads/
   ```
   or
   ```
   /public_html/EMERGENCY-COM/USERS/downloads/
   ```
   (depends on your server structure)

### Step 4: Upload the APK
1. On the **left side** (Local site), navigate to:
   ```
   C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\
   ```
2. Find `emergency-comms-app.apk`
3. **Right-click** on the file → **Upload**
   - OR drag and drop it to the right side
4. If asked to **overwrite**, click **Yes**

### Step 5: Verify Upload
- Check file size on server: Should be **16,229,208 bytes** (~16.2 MB)
- Check timestamp: Should be recent

---

## Method 2: Using cPanel File Manager

### Step 1: Login to cPanel
1. Go to: `https://alertaraqc.com/cpanel` (or your cPanel URL)
2. Login with your credentials

### Step 2: Open File Manager
1. Find **"File Manager"** in cPanel
2. Click to open it

### Step 3: Navigate to Downloads Folder
1. Navigate to: `EMERGENCY-COM/USERS/downloads/`
2. You should see the old `emergency-comms-app.apk` file

### Step 4: Upload New APK
1. Click **"Upload"** button at the top
2. Click **"Select File"** or drag and drop
3. Select: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
4. Wait for upload to complete

### Step 5: Replace Old File
1. If the old file still exists, **delete it first**
2. Or rename the new file to replace it
3. Make sure the final filename is: `emergency-comms-app.apk`

---

## Method 3: Using WinSCP (Windows)

### Step 1: Download WinSCP
- Download from: https://winscp.net/
- Install it

### Step 2: Connect
1. Open WinSCP
2. Enter:
   - **File protocol**: SFTP or FTP
   - **Host name**: `alertaraqc.com`
   - **User name**: Your username
   - **Password**: Your password
3. Click **Login**

### Step 3: Upload
1. **Left panel**: Navigate to `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\`
2. **Right panel**: Navigate to `/EMERGENCY-COM/USERS/downloads/`
3. Drag `emergency-comms-app.apk` from left to right
4. Confirm overwrite if asked

---

## Method 4: Using Command Line (if you have SSH access)

```bash
# Using SCP (from your local machine)
scp C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk username@alertaraqc.com:/path/to/EMERGENCY-COM/USERS/downloads/

# Or using SFTP
sftp username@alertaraqc.com
cd EMERGENCY-COM/USERS/downloads/
put C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk
```

---

## After Upload - Verify

1. **Check file on server**:
   - Visit: `https://emergency-comm.alertaraqc.com/USERS/downloads/emergency-comms-app.apk`
   - File should download (not show 404)

2. **Check file size**:
   - Should be **16,229,208 bytes** (16.2 MB)

3. **Test download from website**:
   - Go to: `https://emergency-comm.alertaraqc.com/index.php`
   - Click "Download APK" button
   - Install on phone
   - Check version: Should be **1.0.1**

---

## Troubleshooting

### File uploads but shows old version
- **Clear browser cache**: Ctrl+Shift+Delete
- **Hard refresh**: Ctrl+F5
- **Check file permissions**: Should be 644 or 755

### Can't find the folder
- Check if path is: `/public_html/EMERGENCY-COM/USERS/downloads/`
- Or: `/home/username/EMERGENCY-COM/USERS/downloads/`
- Or: `/var/www/html/EMERGENCY-COM/USERS/downloads/`

### Upload fails
- Check file size limit (should be at least 20 MB)
- Check disk space on server
- Try uploading in smaller chunks or use SFTP instead of FTP

---

## Quick Checklist

- [ ] APK file ready: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
- [ ] Connected to server (FTP/cPanel/SSH)
- [ ] Navigated to correct folder on server
- [ ] Uploaded new APK file
- [ ] Replaced/overwritten old file
- [ ] Verified file size (16.2 MB)
- [ ] Tested download from website
- [ ] Installed on phone and verified version 1.0.1

