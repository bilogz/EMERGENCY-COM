<?php
// Gmail SMTP Configuration for Mass Notification & Email Delivery
return [
    // Gmail SMTP settings
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'alertaraqc.notification@gmail.com',
    'password' => 'gatbylpxrgmcolqm',      // Gmail App password (16 chars, no spaces)
    'secure' => 'tls',                      // Use TLS for Gmail
    'auth' => true,

    // From address (use same as username)
    'from_email' => 'alertaraqc.notification@gmail.com',
    'from_name' => 'Emergency Alert System',

    // Disable fallback to mail() - use SMTP only
    'send_fallback_to_mail' => false,
    'debug' => false
];

