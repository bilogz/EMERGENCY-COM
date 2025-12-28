<?php
/**
 * Languages API - Real-time Language Support
 * Provides language list with real-time updates
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

require_once '../../ADMIN/api/db_connect.php';

$action = $_GET['action'] ?? 'list';
$lastUpdate = $_GET['last_update'] ?? null;

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'languages' => []
        ]);
        exit;
    }
    
    if ($action === 'list' || $action === 'check-updates') {
        // Get last update timestamp from database
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM supported_languages");
        $dbUpdate = $stmt->fetch();
        $dbLastUpdate = $dbUpdate['last_update'] ?? null;
        
        // If checking for updates and no changes, return early
        if ($action === 'check-updates' && $lastUpdate && $dbLastUpdate === $lastUpdate) {
            echo json_encode([
                'success' => true,
                'updated' => false,
                'last_update' => $dbLastUpdate
            ]);
            exit;
        }
        
        // Get all active languages from admin-managed database
        // This ensures users/guests see languages that admins have added/configured
        $stmt = $pdo->query("
            SELECT 
                language_code, 
                language_name, 
                native_name, 
                flag_emoji, 
                is_active, 
                is_ai_supported, 
                priority,
                updated_at
            FROM supported_languages
            WHERE is_active = 1
            ORDER BY priority DESC, language_name ASC
        ");
        $languages = $stmt->fetchAll();
        
        // Log for debugging (remove in production if needed)
        // error_log("Languages API: Serving " . count($languages) . " active languages to user/guest");
        
        echo json_encode([
            'success' => true,
            'languages' => $languages,
            'last_update' => $dbLastUpdate,
            'count' => count($languages),
            'updated' => ($action === 'check-updates' && $lastUpdate !== $dbLastUpdate)
        ]);
        
    } elseif ($action === 'detect') {
        // Detect browser/device language
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        $languages = [];
        if (!empty($acceptLanguage)) {
            $parts = explode(',', $acceptLanguage);
            foreach ($parts as $part) {
                $lang = trim(explode(';', $part)[0]);
                $lang = strtolower($lang);
                $langCode = explode('-', $lang)[0];
                if (!in_array($langCode, $languages)) {
                    $languages[] = $langCode;
                }
            }
        }
        
        // Try to match with supported languages
        $detectedLanguage = 'en'; // Default
        $matchedLanguage = null;
        
        foreach ($languages as $langCode) {
            // Try exact match first
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM supported_languages 
                WHERE language_code = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode]);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = $result;
                break;
            }
            
            // Try prefix match (e.g., 'en' matches 'en-US')
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM supported_languages 
                WHERE language_code LIKE ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode . '%']);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = $result;
                break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'detected_language' => $detectedLanguage,
            'matched_language' => $matchedLanguage,
            'browser_languages' => $languages,
            'supported' => $matchedLanguage !== null
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Languages API Error: " . $e->getMessage());
    // Fallback to basic languages
    $fallbackLanguages = [
        ['language_code' => 'en', 'language_name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇺🇸', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'fil', 'language_name' => 'Filipino', 'native_name' => 'Filipino', 'flag_emoji' => '🇵🇭', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'es', 'language_name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'fr', 'language_name' => 'French', 'native_name' => 'Français', 'flag_emoji' => '🇫🇷', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'de', 'language_name' => 'German', 'native_name' => 'Deutsch', 'flag_emoji' => '🇩🇪', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'zh', 'language_name' => 'Chinese', 'native_name' => '中文', 'flag_emoji' => '🇨🇳', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'ja', 'language_name' => 'Japanese', 'native_name' => '日本語', 'flag_emoji' => '🇯🇵', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'ko', 'language_name' => 'Korean', 'native_name' => '한국어', 'flag_emoji' => '🇰🇷', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'ar', 'language_name' => 'Arabic', 'native_name' => 'العربية', 'flag_emoji' => '🇸🇦', 'is_active' => 1, 'is_ai_supported' => 1],
        ['language_code' => 'hi', 'language_name' => 'Hindi', 'native_name' => 'हिन्दी', 'flag_emoji' => '🇮🇳', 'is_active' => 1, 'is_ai_supported' => 1]
    ];
    
    echo json_encode([
        'success' => true,
        'languages' => $fallbackLanguages,
        'last_update' => date('Y-m-d H:i:s'),
        'count' => count($fallbackLanguages),
        'fallback' => true
    ]);
}

