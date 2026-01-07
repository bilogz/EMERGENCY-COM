# FileZilla Connection Troubleshooting

## Issue: TLS/Connection Errors with alertaraqc.com:22

### Problem:
- GnuTLS error -15: An unexpected TLS packet was received
- Failed securing connection (ECONNABORTED)
- Connection aborted errors

---

## Solution 1: Try FTP (Port 21) Instead of SFTP (Port 22)

### Step 1: Change FileZilla Settings
1. In FileZilla Client (not Server), go to **Edit** → **Settings** (or **File** → **Site Manager**)
2. If you have a saved site for alertaraqc.com, edit it
3. Change these settings:
   - **Protocol**: Change from `SFTP - SSH File Transfer Protocol` to `FTP - File Transfer Protocol`
   - **Port**: Change from `22` to `21`
   - **Encryption**: Change to `Only use plain FTP (insecure)` or `Require explicit FTP over TLS`
   - **Logon Type**: `Normal` or `Ask for password`

### Step 2: Try Connection
- Click **Quickconnect** or connect to your saved site
- Enter your FTP credentials

---

## Solution 2: Try SFTP with Different Settings

### Alternative SFTP Settings:
1. **Protocol**: `SFTP - SSH File Transfer Protocol`
2. **Host**: `alertaraqc.com`
3. **Port**: `22` (or try `2222` if your host uses non-standard port)
4. **Logon Type**: `Normal` or `Key file`
5. **Encryption**: Try `Only use plain FTP` first, then try SFTP again

---

## Solution 3: Use cPanel File Manager (EASIEST - Recommended)

If FileZilla keeps having issues, use cPanel File Manager:

1. **Login to cPanel:**
   - Go to: `https://alertaraqc.com/cpanel`
   - Login with your cPanel credentials

2. **Open File Manager:**
   - Find "File Manager" in cPanel
   - Click to open

3. **Upload APK:**
   - Navigate to: `public_html/EMERGENCY-COM/USERS/downloads/`
   - Click "Upload" button
   - Select your APK file: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
   - Wait for upload to complete

**This method doesn't require FTP/SFTP at all!**

---

## Solution 4: Check with Your Hosting Provider

The TLS errors might indicate:
- Server configuration issue
- Firewall blocking the connection
- Wrong port being used
- Server doesn't support SFTP on port 22

**Contact your hosting provider** (Hostinger?) and ask:
- What is the correct FTP/SFTP port?
- Are there any firewall restrictions?
- What FTP client settings do they recommend?

---

## Quick Test: Try These Ports

In FileZilla, try connecting with these different settings:

### Option A: Plain FTP
- **Host**: `alertaraqc.com`
- **Protocol**: `FTP - File Transfer Protocol`
- **Port**: `21`
- **Encryption**: `Only use plain FTP (insecure)`

### Option B: FTP with TLS
- **Host**: `alertaraqc.com`
- **Protocol**: `FTP - File Transfer Protocol`
- **Port**: `21`
- **Encryption**: `Require explicit FTP over TLS`

### Option C: SFTP Alternative Port
- **Host**: `alertaraqc.com`
- **Protocol**: `SFTP - SSH File Transfer Protocol`
- **Port**: `2222` (or ask hosting provider)

---

## Recommended: Use cPanel File Manager

**This is the easiest method and doesn't require FTP:**
1. Login to cPanel at `https://alertaraqc.com/cpanel`
2. Open File Manager
3. Upload the APK directly through the web interface
4. No FTP/SFTP configuration needed!

---

**Your APK file is ready at:**
`C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`

