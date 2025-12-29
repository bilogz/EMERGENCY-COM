<?php
/**
 * SECURE API CONFIGURATION - EXAMPLE FILE
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to 'config.local.php'
 * 2. Replace the placeholder values with your actual API keys
 * 3. NEVER commit config.local.php to Git!
 * 
 * OPTION A: Direct values (less secure, easier)
 * OPTION B: Environment variables (more secure, recommended for production)
 */

return [
    // AI Translation Provider
    'AI_PROVIDER' => 'gemini',  // Options: 'gemini', 'openai', 'claude', 'groq'
    
    // Gemini API Key
    // Get yours at: https://makersuite.google.com/app/apikey
    'AI_API_KEY' => getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE',
    
    // Gemini Model - Valid options:
    // - 'gemini-1.5-flash' (recommended - fast & free tier)
    // - 'gemini-1.5-pro' (better quality, more expensive)
    // - 'gemini-2.0-flash-exp' (experimental)
    'GEMINI_MODEL' => 'gemini-1.5-flash',
    
    // Google OAuth Credentials (for Google Sign-In)
    // Get yours at: https://console.cloud.google.com/apis/credentials
    'GOOGLE_CLIENT_ID' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE',
    'GOOGLE_CLIENT_SECRET' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET_HERE',
];

/**
 * SECURITY NOTES:
 * 
 * For Production Servers:
 * Set environment variables instead of hardcoding keys:
 * 
 * Apache (.htaccess or httpd.conf):
 *   SetEnv GEMINI_API_KEY "your-api-key-here"
 *   SetEnv GOOGLE_CLIENT_ID "your-client-id-here"
 *   SetEnv GOOGLE_CLIENT_SECRET "your-client-secret-here"
 * 
 * Nginx (fastcgi_params or server block):
 *   fastcgi_param GEMINI_API_KEY "your-api-key-here";
 * 
 * cPanel:
 *   Go to Software > MultiPHP INI Editor > Environment Variables
 * 
 * For Local Development (XAMPP):
 *   You can hardcode the keys in config.local.php since it's not committed to Git
 */

