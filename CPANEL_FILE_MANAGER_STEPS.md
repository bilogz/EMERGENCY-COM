# How to Access File Manager in cPanel

## Current Situation
You're at: `alertaraqc.com/cpanel` - This might be showing a landing page or custom interface.

## Steps to Access File Manager

### Option 1: Standard cPanel Login
1. Try accessing cPanel directly:
   - Go to: `https://alertaraqc.com:2083` (cPanel standard port)
   - OR: `https://alertaraqc.com:2082` (if using SSL)
   - OR: `https://cpanel.alertaraqc.com`
   - OR: `https://alertaraqc.com/cpanel` (then look for a login form)

2. Login with your cPanel credentials:
   - Username: (usually your hosting account username)
   - Password: (your cPanel password)

3. Once logged in, look for **"File Manager"** icon/button in the cPanel dashboard

### Option 2: Direct File Manager URL
Try these direct URLs:
- `https://alertaraqc.com:2083/files` (if port 2083 works)
- `https://alertaraqc.com/cpanel/files` (if cPanel is in subdirectory)

### Option 3: From the Current Page
On the page you're seeing (`alertaraqc.com/cpanel`):
1. Look for a **"Login"** button or link
2. Look for **"File Manager"** in a menu
3. Check if there's a **"cPanel"** or **"Control Panel"** link
4. Look for any navigation that says "File Management" or similar

### Option 4: Check Your Hosting Provider's Documentation
- If you're using Hostinger or another provider, check their documentation for:
  - How to access cPanel
  - Direct File Manager URL
  - Alternative file upload methods

## Once You're in File Manager:

1. **Navigate to the downloads folder:**
   - Look for: `public_html/EMERGENCY-COM/USERS/downloads/`
   - OR: `EMERGENCY-COM/USERS/downloads/`
   - OR: `domains/alertaraqc.com/public_html/EMERGENCY-COM/USERS/downloads/`

2. **Delete the old APK (if it exists):**
   - Find `emergency-comms-app.apk`
   - Select it and click Delete

3. **Upload the new APK:**
   - Click **"Upload"** button (usually at the top)
   - Select file: `C:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk`
   - Wait for upload to complete (1-2 minutes for 16MB file)

4. **Verify:**
   - Check file size: Should be 16.2 MB
   - Check timestamp: Should be today's date

## Alternative: If You Can't Access File Manager

If you can't get into File Manager, you can also try:

1. **FTP with correct settings** (once we fix the connection)
2. **Hosting provider's file upload tool** (some providers have their own)
3. **SSH access** (if you have it enabled)

---

**Need Help?** 
- What happens when you go to: `https://alertaraqc.com:2083`?
- Do you see a login page or error?
- Is there a "Login" or "File Manager" link visible on the current page?


