<?php
/**
 * Admin OTP Email Configuration
 * SMTP settings for sending admin OTP emails from alertaraqc.notification@gmail.com
 * 
 * IMPORTANT: Replace the password with the Gmail App Password for alertaraqc.notification@gmail.com
 * To get a Gmail App Password:
 * 1. Go to Google Account settings
 * 2. Security > 2-Step Verification > App passwords
 * 3. Generate a new app password for "Mail"
 * 4. Copy the 16-character password and paste it below
 */

return [
    // Gmail SMTP settings for alertaraqc.notification@gmail.com
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'secure' => 'tls',  // Use TLS for Gmail
    'password' => 'gatbylpxrgmcolqm',  // Gmail App Password for alertaraqc.notification@gmail.com
];

