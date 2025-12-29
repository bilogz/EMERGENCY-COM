# Google reCAPTCHA v2 Setup Guide

## ✅ What Was Implemented

Both **login.php** and **create-admin.php** now use **Google reCAPTCHA v2** ("I am not a robot" checkbox) instead of the text-based CAPTCHA.

### Benefits:
- ✅ More user-friendly (just click a checkbox)
- ✅ More secure (Google's advanced bot detection)
- ✅ Better accessibility
- ✅ Free to use
- ✅ Widely recognized and trusted

---

## 🔧 Current Configuration (Test Mode)

**Test Keys** (for development/testing):
- **Site Key**: `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`
- **Secret Key**: `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`

**Note**: Test keys always return success, so they're perfect for development but **NOT secure for production**.

---

## 🚀 Setup for Production

### Step 1: Get Your reCAPTCHA Keys

1. Go to [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Click **"+ Create"** button
3. Fill in the form:
   - **Label**: `Emergency Communication System - Admin Panel`
   - **reCAPTCHA type**: Select **"reCAPTCHA v2"** → **"I'm not a robot" Checkbox**
   - **Domains**: Add your domains:
     - `yourdomain.com`
     - `www.yourdomain.com`
     - `localhost` (for local testing)
   - Accept the reCAPTCHA Terms of Service
4. Click **Submit**
5. Copy your **Site Key** and **Secret Key**

---

### Step 2: Update Site Keys (Frontend)

#### File 1: `ADMIN/login.php`

Find this line (around line 663):
```html
<div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" data-theme="light"></div>
```

Replace with:
```html
<div class="g-recaptcha" data-sitekey="YOUR_PRODUCTION_SITE_KEY" data-theme="light"></div>
```

#### File 2: `ADMIN/create-admin.php`

Find this line (around line 350):
```html
<div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" data-theme="light"></div>
```

Replace with:
```html
<div class="g-recaptcha" data-sitekey="YOUR_PRODUCTION_SITE_KEY" data-theme="light"></div>
```

---

### Step 3: Update Secret Keys (Backend)

#### File 1: `ADMIN/api/login-web.php`

Find this line (around line 30):
```php
$recaptchaSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test key - replace with production key
```

Replace with:
```php
$recaptchaSecretKey = 'YOUR_PRODUCTION_SECRET_KEY';
```

Then **uncomment** the validation check (around line 50):
```php
// Change this:
// if (!isset($recaptchaJson['success']) || !$recaptchaJson['success']) {

// To this:
if (!isset($recaptchaJson['success']) || !$recaptchaJson['success']) {
    echo json_encode(["success" => false, "message" => "reCAPTCHA verification failed. Please try again."]);
    exit();
}
```

#### File 2: `ADMIN/create-admin.php`

Find this line (around line 35):
```php
$recaptchaSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test key - replace with production key
```

Replace with:
```php
$recaptchaSecretKey = 'YOUR_PRODUCTION_SECRET_KEY';
```

Then **uncomment** the validation check (around line 55):
```php
// Change this:
// if (!isset($recaptchaJson['success']) || !$recaptchaJson['success']) {
//     $message = 'reCAPTCHA verification failed. Please try again.';
// } else {

// To this:
if (!isset($recaptchaJson['success']) || !$recaptchaJson['success']) {
    $message = 'reCAPTCHA verification failed. Please try again.';
} else {
```

And **uncomment** the closing brace (around line 50):
```php
// Change this:
// } // Uncomment this closing brace when enabling production reCAPTCHA check

// To this:
} // Closing brace for reCAPTCHA validation
```

---

## ✅ Verification Checklist

After updating the keys:

- [ ] Site Key updated in `login.php`
- [ ] Site Key updated in `create-admin.php`
- [ ] Secret Key updated in `api/login-web.php`
- [ ] Secret Key updated in `create-admin.php`
- [ ] Validation checks uncommented in both backend files
- [ ] Test login page - reCAPTCHA appears
- [ ] Test create-admin page - reCAPTCHA appears
- [ ] Test successful login with valid credentials
- [ ] Test failed login - reCAPTCHA resets
- [ ] Test account creation - reCAPTCHA works

---

## 🧪 Testing

### Test Mode (Current)
- reCAPTCHA always passes (for development)
- No real verification happens
- Perfect for testing functionality

### Production Mode (After Setup)
- Real bot detection
- May show image challenges for suspicious traffic
- Fully secure

---

## 📝 Notes

1. **Keep Secret Keys Secure**: Never commit secret keys to public repositories
2. **Domain Restrictions**: Make sure your production domain is added to the reCAPTCHA site settings
3. **Rate Limiting**: Google may rate limit if you exceed free tier limits
4. **Accessibility**: reCAPTCHA v2 is more accessible than text CAPTCHAs
5. **Mobile**: Works well on mobile devices

---

## 🆘 Troubleshooting

### reCAPTCHA Not Showing
- Check if Google's script is loading (check browser console)
- Verify Site Key is correct
- Check domain is added to reCAPTCHA settings
- Clear browser cache

### Validation Always Fails
- Verify Secret Key is correct
- Check server can reach Google's API (firewall/network)
- Check error logs for API response
- Ensure validation code is uncommented

### "Invalid site key" Error
- Site Key doesn't match Secret Key
- Domain not added to reCAPTCHA settings
- Using test key on production domain

---

## 📚 Additional Resources

- [Google reCAPTCHA Documentation](https://developers.google.com/recaptcha/docs/display)
- [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
- [reCAPTCHA Best Practices](https://developers.google.com/recaptcha/docs/best-practices)

---

**Last Updated**: After implementing Google reCAPTCHA v2











