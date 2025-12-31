# Login CAPTCHA Implementation Guide

## Overview
The login system has been redesigned to use **phone number + CAPTCHA verification** instead of SMS OTP. This reduces SMS costs while maintaining security.

## Key Changes

### 1. Login Flow (USERS/login.php)
- **Form Fields**: Phone number only (no name required)
- **Verification**: Google reCAPTCHA v2 (free bot verification)
- **Redirect**: Direct login to home.php on success

### 2. New API Endpoint (USERS/api/login-with-phone.php)
- Accepts: `phone` and `captcha_token`
- Validates phone number in database
- Creates PHP session for authenticated user
- Returns user information and success status

### 3. reCAPTCHA Configuration
- **Current Setup**: Test keys (always returns success)
- **Production**: Replace with your own Google reCAPTCHA keys

#### Test Keys (Current)
```
Site Key: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
Secret Key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

#### Get Production Keys
1. Go to [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Create a new site
3. Select reCAPTCHA v2 (Checkbox)
4. Add your domain
5. Get your Site Key and Secret Key

### 4. Integration Steps

#### Update Site Key (USERS/login.php)
Find line ~47 and replace:
```html
<div class="g-recaptcha" data-sitekey="YOUR_PRODUCTION_SITE_KEY" data-theme="dark"></div>
```

#### Update Secret Key (USERS/api/login-with-phone.php)
Find line ~23 and uncomment/update:
```php
$captchaSecretKey = 'YOUR_PRODUCTION_SECRET_KEY';

// Uncomment the verification code below:
$captchaResult = json_decode(file_get_contents(
    $captchaUrl . '?secret=' . $captchaSecretKey . '&response=' . $captchaToken
), true);
$isCaptchaValid = $captchaResult['success'] ?? false;
```

### 5. Cost Comparison

| Method | Cost per Login | Notes |
|--------|---|---|
| **Old (OTP)** | ₱0.50-2 per SMS | Expensive for frequent logins |
| **New (CAPTCHA)** | FREE | No SMS cost |
| **Signup (OTP)** | ₱0.50-2 per SMS | Still used for registration |

### 6. File Structure
```
USERS/
├── login.php                    (Updated - CAPTCHA form)
├── signup.php                   (Unchanged - Still uses SMS OTP)
├── api/
│   ├── login-with-phone.php     (New - CAPTCHA verification)
│   ├── send-signup-otp.php      (Existing - SMS signup)
│   └── verify-signup-otp.php    (Existing - Verify signup OTP)
```

### 7. Testing

#### Test Login Without Production Keys
1. Navigate to `USERS/login.php`
2. Enter any phone number (must exist in database)
3. Complete CAPTCHA verification
4. Should redirect to home.php

#### Test Login With Production Keys
1. Set production reCAPTCHA keys
2. Login with valid phone number
3. Complete actual CAPTCHA challenge
4. Verify session is created

### 8. Database Requirements

The `users` table should have:
- `user_id` (primary key)
- `phone` (unique, indexed)
- `full_name`
- `email` (optional)

### 9. Security Notes
- Phone numbers are normalized (spaces, dashes removed)
- CAPTCHA prevents automated bot attacks
- Session-based authentication after login
- Optional: Consider adding login attempt rate limiting

### 10. SMS OTP Still Used For
- **Signup**: Users must verify phone via OTP during registration
- **Password Recovery**: If needed in future

## Troubleshooting

### CAPTCHA Not Appearing
- Check if reCAPTCHA JavaScript loaded: `https://www.google.com/recaptcha/api.js`
- Verify site key is correct
- Check browser console for errors

### Login Always Fails
- Check if phone exists in database
- Verify database connection in `db_connect.php`
- Check if `captcha_token` is being sent from frontend

### CAPTCHA Always Valid (Test Mode)
- This is expected with test keys
- Switch to production keys to enforce real verification

## Migration Notes

If you had existing login sessions with OTP:
1. Clear old `otp_*` session variables
2. Users will need to re-login with new CAPTCHA method
3. No database changes required
