# Email Setup Guide for Admin OTP

## Why Email Delivery Fails

Email delivery fails because:

1. **PHP's `mail()` function is not configured** - On XAMPP/Windows, the `mail()` function typically doesn't work unless you configure sendmail or SMTP
2. **No SMTP configuration exists** - The `mail_config.php` file hasn't been created/configured yet

## Solution: Configure SMTP

To enable email delivery for OTP codes, you need to configure SMTP. Here are your options:

### Option 1: Gmail SMTP (Recommended for Testing)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate an App Password**:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "Emergency System" as the name
   - Copy the generated 16-character password

3. **Create the mail configuration file**:
   - Copy `EMERGENCY-COM/USERS/config/mail_config.php.example` to `EMERGENCY-COM/USERS/config/mail_config.php`
   - Edit `mail_config.php` with these settings:

```php
<?php
return [
    // Gmail SMTP settings
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com',  // Your Gmail address
    'password' => 'your-app-password',      // 16-character app password from step 2
    'secure' => 'tls',
    'auth' => true,

    // From address
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Emergency Communication System',

    // Enable fallback to mail() if SMTP fails
    'send_fallback_to_mail' => false,
    'debug' => false
];
```

4. **Install PHPMailer** (if not already installed):
   ```bash
   cd EMERGENCY-COM
   composer require phpmailer/phpmailer
   ```

### Option 2: Other SMTP Services

You can use any SMTP service (Outlook, SendGrid, Mailgun, etc.). Just update the settings:

```php
<?php
return [
    'host' => 'smtp.your-provider.com',  // SMTP server
    'port' => 587,                        // Usually 587 (TLS) or 465 (SSL)
    'username' => 'your-username',
    'password' => 'your-password',
    'secure' => 'tls',                    // 'tls' or 'ssl'
    'auth' => true,
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Emergency Communication System',
    'send_fallback_to_mail' => false,
    'debug' => false
];
```

### Option 3: Configure PHP mail() for XAMPP (Advanced)

If you want to use PHP's `mail()` function on XAMPP:

1. Edit `php.ini` file in XAMPP
2. Find `[mail function]` section
3. Configure sendmail or use a tool like **Fake Sendmail** for Windows
4. This is more complex and not recommended for production

## Verification

After configuration:

1. Try sending an OTP code again
2. Check the browser console and server error logs for any errors
3. If successful, `otp_sent: true` will appear in the response
4. The email should arrive in the recipient's inbox

## Current Status

The system currently shows a **debug OTP code** in the response when email fails. This is intentional for testing purposes. Once SMTP is configured, emails will be sent automatically and the debug code can be removed for production.

## Troubleshooting

- **"SMTP Error: Could not authenticate"** - Check your username/password (for Gmail, use App Password, not your regular password)
- **"Connection timeout"** - Check firewall settings and ensure port 587/465 is open
- **"PHPMailer not found"** - Run `composer install` in the EMERGENCY-COM directory
- **Check error logs** - Look in `C:\xampp\apache\logs\error.log` for detailed error messages

## Security Note

⚠️ **Important**: Never commit `mail_config.php` to version control! It contains sensitive credentials. Always keep it in `.gitignore`.

