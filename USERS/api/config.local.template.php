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
    // CHAT IMAGE STORAGE
    // ===========================================
    // Storage driver for chat incident photos:
    // - 'filesystem' (default): saves to USERS/uploads/chat
    // - 'postgres': saves image bytes in PostgreSQL (table: chat_attachments)
    'CHAT_IMAGE_STORAGE_DRIVER' => 'filesystem',

    // PostgreSQL image storage settings (used when driver = 'postgres')
    // You can set either:
    // 1) PG_IMG_URL (full connection URL), OR
    // 2) individual PG_IMG_* keys below.
    'PG_IMG_URL' => '',
    'PG_IMG_HOST' => '127.0.0.1',
    'PG_IMG_PORT' => 5432,
    'PG_IMG_DB' => 'emer_comm_images',
    'PG_IMG_USER' => 'postgres',
    'PG_IMG_PASS' => '',
    'PG_IMG_SSLMODE' => 'prefer',
    'PG_IMG_CHANNEL_BINDING' => '',
    // Optional libpq options (example for Neon pooler/SNI fallback):
    // 'endpoint=your-endpoint-id'
    'PG_IMG_OPTIONS' => '',
    
    // ===========================================
    // AI/TRANSLATION API KEYS
    // ===========================================
    
    // AI Provider: 'argos', 'gemini', 'openai', or 'mymemory'
    'AI_PROVIDER' => 'argos',
    // Citizen-side translation provider (UI + alerts): set to 'argos' to use ArgosTranslate.
    'TRANSLATION_PROVIDER' => 'argos',
    // Local ArgosTranslate API endpoint (argos-translate service)
    'ARGOS_TRANSLATE_URL' => 'http://localhost:5001/translate',
    // Translation cache backend:
    // - 'mysql'  : existing translation_cache table in MySQL
    // - 'neon'   : Neon/PostgreSQL only
    // - 'hybrid' : read Neon first, fallback MySQL, write to both
    // Leave empty to auto-detect (uses 'hybrid' when NEON_TRANSLATION_CACHE_URL is set).
    'TRANSLATION_CACHE_DRIVER' => 'mysql',
    // Full Neon/PostgreSQL URL used for translation cache.
    // Example: postgresql://user:pass@host/dbname?sslmode=require&channel_binding=require
    // If empty, the system may fallback to PG_IMG_URL.
    'NEON_TRANSLATION_CACHE_URL' => '',
    // Optional table override for Neon cache.
    'NEON_TRANSLATION_CACHE_TABLE' => 'translation_cache',
    'GEMINI_MODEL' => 'gemini-2.5-flash',
    
    // API Keys (get from respective providers)
    'AI_API_KEY' => '',                 // Default AI key
    'AI_API_KEY_AI_MESSAGE' => '',      // For AI message generation
    'AI_API_KEY_CHATBOT' => '',         // For user chatbot assistant (fallback: AI_API_KEY_AI_MESSAGE)
    'AI_API_KEY_TRANSLATION' => '',     // For translations
    'AI_API_KEY_ANALYSIS' => '',        // For AI analysis
    'CHAT_ASSISTANT_ENABLED' => true,   // Enable floating-button AI assistant
    'CHAT_ASSISTANT_MODEL' => 'gemini-2.5-flash',
    'CHAT_ASSISTANT_EMERGENCY_CALL_URL' => 'http://localhost/EMERGENCY-COM/USERS/emergency-call.php', // Emergency call URL sent by AI assistant
    'CHAT_ASSISTANT_EMERGENCY_NUMBER' => '122', // Emergency hotline number used by AI assistant (Quezon City)
    'CHAT_ASSISTANT_SYSTEM_PROMPT' => '', // Optional override. Leave blank to use built-in QC emergency prompt.
    
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
];

