# reCAPTCHA Domain Whitelist Setup Guide

## Current Error
"Localhost is not in the list of supported domains for this site key"

## Solution: Whitelist Your Domains

### Step 1: Access Google reCAPTCHA Admin Console
1. Go to: **https://www.google.com/recaptcha/admin**
2. Sign in with your Google account
3. Find your reCAPTCHA site (the one with keys starting with `6LeXXjcs`)

### Step 2: Add Domains to Whitelist
1. Click on your reCAPTCHA site
2. Scroll down to the **"Domains"** section
3. Click **"+ Add Domain"** or edit the existing domains list
4. Add the following domains:

#### For Local Development/Testing:
- `localhost`
- `127.0.0.1`

#### For Production Server:
- Your domain name (e.g., `yourdomain.com`, `www.yourdomain.com`)
- If using IP directly: `72.60.209.226` (if Google allows IP addresses - some versions don't)

### Step 3: Save Changes
1. Click **"Submit"** or **"Save"**
2. Wait **1-2 minutes** for changes to propagate
3. Refresh your website page

### Step 4: Verify
1. Clear your browser cache
2. Refresh the page with the reCAPTCHA
3. The checkbox should now appear without errors

## Important Notes

⚠️ **Google reCAPTCHA v2 Requirements:**
- You must whitelist **exactly** the domains where reCAPTCHA will be used
- Subdomains need to be added separately (e.g., `www.yourdomain.com` and `yourdomain.com` are different)
- IP addresses are sometimes not accepted - use domain names when possible

✅ **Best Practice:**
- For localhost testing: Add `localhost` and `127.0.0.1`
- For production: Use your actual domain name, not IP addresses

## Current Keys in Use

**Site Key** (used in HTML): `6LeXXjcsAAAAALkrHEDevFsVzUsW_fjRnYKItbLE`
**Secret Key** (used in PHP): `6LeXXjcsAAAAAMchkaNgXKDH32lXqc8-yDvPbzIN`

If these don't work after whitelisting, try swapping them in `create-admin.php`.

