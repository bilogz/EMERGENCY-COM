# All Methods to Replace APK on Server

## ✅ Your New APK is Ready
- **Location**: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
- **Size**: 16.2 MB (16,229,208 bytes)
- **Version**: 1.0.2

---

## Method 1: Fix FileZilla - Use FTP (Port 21) Instead of SFTP

### The Problem:
FileZilla is trying to use SFTP (port 22) which has TLS errors.

### The Solution:
Use **FTP** (port 21) instead!

### Steps:
1. **Open FileZilla CLIENT** (not Server)
2. **Go to File → Site Manager** (or press Ctrl+S)
3. **Click "New Site"** (or edit existing)
4. **Set these settings:**
   ```
   Protocol: FTP - File Transfer Protocol
   Host: alertaraqc.com
   Port: 21
   Encryption: Only use plain FTP (insecure)
   Logon Type: Normal
   User: [Your FTP username]
   Password: [Your FTP password]
   ```
5. **Click "Connect"**
6. **Navigate:**
   - Left side: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\`
   - Right side: `/public_html/EMERGENCY-COM/USERS/downloads/` (or `/EMERGENCY-COM/USERS/downloads/`)
7. **Upload:** Drag `emergency-comms-app.apk` from left to right

---

## Method 2: Use PowerShell Built-in FTP

If you have FTP credentials, you can use PowerShell:

### Steps:
1. **Open PowerShell** (as Administrator)
2. **Run these commands:**

```powershell
# Create FTP script
$ftpScript = @"
open alertaraqc.com
[Your FTP Username]
[Your FTP Password]
binary
cd /public_html/EMERGENCY-COM/USERS/downloads
put C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk
quit
"@

# Save script
$ftpScript | Out-File -FilePath "$env:TEMP\ftp_upload.txt" -Encoding ASCII

# Run FTP
ftp -s:"$env:TEMP\ftp_upload.txt"
```

**Replace `[Your FTP Username]` and `[Your FTP Password]` with your actual credentials!**

---

## Method 3: Create a PHP Upload Script (EASY!)

I can create a simple PHP script you can upload via your website, then use it to upload the APK.

### Steps:
1. I'll create the upload script
2. You upload it to your server via your website admin
3. Access it in browser, upload the APK
4. Delete the script after (for security)

---

## Method 4: Try Standard cPanel Ports

Try these URLs directly:

1. **Standard cPanel (HTTP):**
   - `http://alertaraqc.com:2083`
   - Login with cPanel credentials

2. **Secure cPanel (HTTPS):**
   - `https://alertaraqc.com:2082`

3. **Alternative:**
   - `https://alertaraqc.com:2087` (Secure with SSL)

4. **Direct File Manager:**
   - `http://alertaraqc.com:2083/frontend/x3/filemanager/index.html`

Once logged in, look for "File Manager" icon.

---

## Method 5: Contact Your Hosting Provider

If none of the above work:

1. **Contact Hostinger Support** (or your hosting provider)
2. **Ask them:**
   - What is the correct FTP/SFTP port?
   - How to access File Manager?
   - What are the correct FTP credentials?
   - Can they help upload the file?

3. **Provide them:**
   - File location: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
   - Destination: `/public_html/EMERGENCY-COM/USERS/downloads/emergency-comms-app.apk`

---

## Method 6: Use WinSCP (Alternative FTP Client)

If FileZilla keeps failing:

1. **Download WinSCP**: https://winscp.net/
2. **Install and open WinSCP**
3. **Try these connection settings:**
   ```
   File protocol: FTP
   Host name: alertaraqc.com
   Port number: 21
   User name: [Your FTP username]
   Password: [Your FTP password]
   ```
4. **Click Login**
5. **Navigate and upload** like FileZilla

---

## Recommended Next Steps:

1. **First, try Method 1** (Fix FileZilla - use FTP port 21)
2. **If that fails, try Method 4** (Standard cPanel ports)
3. **If still failing, use Method 3** (PHP upload script - I'll create it for you)

---

**Which method would you like to try first?** 

I can help you:
- Create the PHP upload script (Method 3) - This is the easiest if FTP/cPanel isn't working
- Help configure FileZilla with correct settings (Method 1)
- Help find your cPanel login (Method 4)

