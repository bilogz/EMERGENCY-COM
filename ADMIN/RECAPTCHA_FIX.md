# reCAPTCHA Key Configuration Fix

## Current Issue
"ERROR for site owner: Invalid site key" - This means the site key being used is invalid.

## Possible Causes:
1. **Keys are swapped** - Site key and Secret key might be reversed
2. **Domain not whitelisted** - Your domain (localhost, 127.0.0.1, etc.) needs to be added in Google reCAPTCHA console
3. **Wrong key type** - Keys might be for v3 but you're using v2 (or vice versa)

## Your Keys:
- Key 1: `6LeXXjcsAAAAALkrHEDevFsVzUsW_fjRnYKItbLE`
- Key 2: `6LeXXjcsAAAAAMchkaNgXKDH32lXqc8-yDvPbzIN`

## Current Configuration (in create-admin.php):
- SECRET_KEY = Key 1 (first key)
- SITE_KEY = Key 2 (second key)

## Try This:
If still getting "Invalid site key", try SWAPPING the keys:

```php
$RECAPTCHA_SECRET_KEY = '6LeXXjcsAAAAAMchkaNgXKDH32lXqc8-yDvPbzIN'; // Try Key 2 as secret
$RECAPTCHA_SITE_KEY = '6LeXXjcsAAAAALkrHEDevFsVzUsW_fjRnYKItbLE'; // Try Key 1 as site
```

## Important Steps:
1. Go to Google reCAPTCHA Admin Console: https://www.google.com/recaptcha/admin
2. Find your reCAPTCHA site
3. Check "Domains" section
4. Make sure your domain is added:
   - For localhost: `localhost` and/or `127.0.0.1`
   - For production: your actual domain
5. Verify you're using **reCAPTCHA v2** keys (not v3)
6. Copy the keys again from the console to make sure they're correct

## Test:
After updating keys, refresh the page and check if the checkbox appears without error.

