<?php
// Gmail SMTP Configuration for OTP Email Delivery
return [
    // Gmail SMTP settings
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'joecelgarcia1@gmail.com',  // ⚠️ REPLACE THIS with your Gmail address
    'password' => 'ylwfhqfphqazrcbq',      // App password (16 chars, no spaces)
    'secure' => 'tls',                      // Use TLS for Gmail
    'auth' => true,

    // From address (use same as username)
    'from_email' => 'joecelgarcia1@gmail.com', // ⚠️ REPLACE THIS with your Gmail address
    'from_name' => 'Emergency Communication System',

    // Disable fallback to mail() - use SMTP only
    'send_fallback_to_mail' => false,
    'debug' => false
];
