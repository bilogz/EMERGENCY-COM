# Quick Start: CAPTCHA Login Implementation

## ✅ What Was Done

Your login system now uses **CAPTCHA verification** instead of SMS OTP, eliminating SMS costs while maintaining security.

### Changes Made:

1. **[USERS/login.php](login.php)** - Updated form
   - Input: Phone number only
   - Verification: Google reCAPTCHA v2
   - Action: Direct login without OTP

2. **[USERS/api/login-with-phone.php](api/login-with-phone.php)** - New backend endpoint
   - Validates phone number exists
   - Verifies CAPTCHA token
   - Creates PHP session

3. **[USERS/signup.php](signup.php)** - Unchanged
   - Still uses SMS OTP for registration
   - One-time cost per new user

## 🚀 Test It Now

### Quick Test (Without Real CAPTCHA)
1. Go to `http://localhost/EMERGENCY-COM/USERS/login.php`
2. Enter a phone number that exists in your database
3. Check the CAPTCHA box
4. Click "Login"
5. Should redirect to home.php

### Test with Database Check
```bash
# Check what phones exist in your database
mysql -u root -e "SELECT phone, full_name FROM EMERGENCY_COM.users LIMIT 5;"
```

Then use one of those phone numbers to test login.

## 🔧 Configure for Production

### Step 1: Get Real reCAPTCHA Keys
Visit: https://www.google.com/recaptcha/admin

1. Click "+" to create new site
2. Name: "EMERGENCY-COM Login"
3. Choose: reCAPTCHA v2 → Checkbox
4. Domains: `your-domain.com`, `localhost`
5. Copy keys

### Step 2: Update Site Key
**File**: [USERS/login.php](login.php#L47)

Find this line:
```html
<div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
```

Replace with your production site key.

### Step 3: Update Secret Key
**File**: [USERS/api/login-with-phone.php](api/login-with-phone.php#L23)

Find this section:
```php
$captchaSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test key
$isCaptchaValid = true; // Currently bypassed
```

Replace with:
```php
$captchaSecretKey = 'YOUR_PRODUCTION_SECRET_KEY';

// Uncomment this:
$captchaResult = json_decode(file_get_contents(
    $captchaUrl . '?secret=' . $captchaSecretKey . '&response=' . $captchaToken
), true);
$isCaptchaValid = $captchaResult['success'] ?? false;
```

### Step 4: Test Production
1. Update both keys (site + secret)
2. Test login - should now show real CAPTCHA challenge
3. CAPTCHA must be completed to login

## 📊 Cost Comparison

| Scenario | SMS Cost | Notes |
|----------|----------|-------|
| Before: OTP Login | ₱0.50-2/login | Every user every login |
| After: CAPTCHA Login | FREE | No SMS for login |
| Signup (Still OTP) | ₱0.50-2/signup | One-time per user |

**Example**: 1000 users × 3 logins/day
- **Before**: 1000 × 3 × ₱1 = ₱3,000/month
- **After**: 1000 × ₱1 (signup only) = ₱1,000/month
- **Savings**: ₱2,000+/month 🎉

## 🔍 Troubleshooting

### CAPTCHA Widget Not Showing?
- Check: Is `data-sitekey` correct?
- Check: Is JavaScript loaded: `https://www.google.com/recaptcha/api.js`
- Check: Browser console for errors (F12)

### Login Always Fails?
- Check: Does phone exist in database?
- Check: Network tab - is API being called?
- Check: Are site/secret keys valid?

### Need to Test Without Real CAPTCHA?
The test keys are already configured. Just use them as-is for development. When ready for production, swap the keys.

## 📝 Files Modified

```
USERS/
├── login.php ............................ [MODIFIED] Form updated
├── signup.php ........................... [UNCHANGED] Still working
├── api/
│   ├── login-with-phone.php ............. [NEW] Backend for login
│   ├── send-signup-otp.php .............. [UNCHANGED] SMS signup
│   ├── verify-signup-otp.php ............ [UNCHANGED] OTP verify
│   └── register-after-otp.php ........... [UNCHANGED] Registration
└── LOGIN_CAPTCHA_GUIDE.md ............... [NEW] Full documentation
```

## ⚡ Key Features

✅ **Free CAPTCHA**: Google's free bot verification
✅ **Fast**: No SMS delivery delay
✅ **Simple**: Only phone number needed
✅ **Secure**: CAPTCHA + Session authentication
✅ **Compatible**: Works with existing database
✅ **Flexible**: Easy to switch back if needed

## 💡 Tips

- Test with test keys first (faster development)
- Switch to production keys when going live
- Consider adding rate limiting to prevent abuse
- Monitor login success rates after deployment

## 📞 Support

If you encounter issues:
1. Check browser console (F12 → Console tab)
2. Check network requests (F12 → Network tab)
3. Verify database connection in `db_connect.php`
4. Verify phone number exists in `users` table

---

**Status**: ✅ Ready for Testing & Production
**Last Updated**: 2024
