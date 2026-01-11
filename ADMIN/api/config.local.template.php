<?php
/**
 * LOCAL CONFIGURATION TEMPLATE
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'config.local.php'
 * 2. Fill in your actual credentials
 * 3. NEVER commit config.local.php to Git!
 * 
 * The config.local.php file is already in .gitignore
 */

return [
    // ===========================================
    // APPLICATION ENVIRONMENT
    // ===========================================
    'APP_ENV' => 'development', // 'development' or 'production'
    
    // ===========================================
    // DATABASE CONFIGURATION
    // ===========================================
    
    // Primary Database (used first)
    'DB_HOST' => 'localhost',           // Database host
    'DB_PORT' => 3306,                  // Database port
    'DB_NAME' => 'emer_comm_test',      // Database name
    'DB_USER' => 'root',                // Database username
    'DB_PASS' => '',                    // Database password (empty for XAMPP default)
    
    // Fallback Database (used if primary fails)
    'DB_FALLBACK_HOST' => '127.0.0.1',
    'DB_FALLBACK_PORT' => 3306,
    'DB_FALLBACK_NAME' => 'emer_comm_test',
    'DB_FALLBACK_USER' => 'root',
    'DB_FALLBACK_PASS' => '',
    
    // ===========================================
    // AI/TRANSLATION API KEYS
    // ===========================================
    
    // AI Provider: 'libretranslate', 'gemini', 'openai', or 'mymemory'
    'AI_PROVIDER' => 'libretranslate',
    'GEMINI_MODEL' => 'gemini-2.5-flash',
    
    // API Keys (get from respective providers)
    'AI_API_KEY' => '',                 // Default AI key
    'AI_API_KEY_TRANSLATION' => '',     // AI-Alert-Translator: For alert translations (user preference-based)
    'AI_API_KEY_ANALYSIS' => '',        // For AI analysis
    
    // LibreTranslate (FREE - no API key needed for public servers)
    'LIBRETRANSLATE_URL' => 'https://libretranslate.com/translate',
    'LIBRETRANSLATE_API_KEY' => '',     // Leave empty for public servers
    
    // ===========================================
    // GOOGLE OAUTH CREDENTIALS
    // ===========================================
    'GOOGLE_CLIENT_ID' => '',           // From Google Cloud Console
    'GOOGLE_CLIENT_SECRET' => '',       // From Google Cloud Console
    
    // ===========================================
    // EMAIL/SMTP CONFIGURATION
    // ===========================================
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_USER' => '',                  // Your email address
    'SMTP_PASS' => '',                  // App password (not regular password)
    'SMTP_FROM' => '',                  // From email address
    'SMTP_FROM_NAME' => 'Emergency Alert System',
    
    // ===========================================
    // RECAPTCHA CONFIGURATION
    // ===========================================
    // Get keys from: https://www.google.com/recaptcha/admin
    // Use the Enterprise/v3 keys for invisible captcha on the login form
    'RECAPTCHA_SITE_KEY' => '',         // reCAPTCHA v3/Enterprise site key (public)
    'RECAPTCHA_SECRET_KEY' => '',       // reCAPTCHA v3/Enterprise secret key (private)

    // ===========================================
    // ADMIN SECURITY
    // ===========================================
    // Shared API key for admin-only endpoints (login + OTP).
    // Leave empty to disable the check.
    'ADMIN_API_KEY' => '',
    // Enforce OTP on admin login (recommended in production)
    'ADMIN_REQUIRE_OTP' => true,
];

