<?php
/**
 * Translation Configuration
 * Simple config for LibreTranslate API
 */

// LibreTranslate API URL (local instance)
define('LIBRETRANSLATE_URL', 'http://localhost:5000/translate');

// Alternative: Use public instance if local is not running
// define('LIBRETRANSLATE_URL', 'https://libretranslate.com/translate');

// Cache duration in days
define('TRANSLATION_CACHE_DAYS', 30);

// Language code mappings (for compatibility)
$LANG_MAP = [
    'fil' => 'tl',      // Filipino -> Tagalog
    'ceb' => 'tl',      // Cebuano -> Tagalog (closest match)
    'ilo' => 'tl',      // Ilocano -> Tagalog
    'pam' => 'tl',      // Kapampangan -> Tagalog
    'bcl' => 'tl',      // Bicolano -> Tagalog
    'war' => 'tl',      // Waray -> Tagalog
    'hil' => 'tl',      // Hiligaynon -> Tagalog
    'pwg' => 'tl',      // Pangasinan -> Tagalog
    'zh-TW' => 'zh',    // Traditional Chinese -> Chinese
];

/**
 * Map language code to LibreTranslate compatible code
 */
function mapLanguageCode($code) {
    global $LANG_MAP;
    return $LANG_MAP[$code] ?? $code;
}

/**
 * Translate text using LibreTranslate
 */
function translateWithLibre($text, $sourceLang, $targetLang) {
    $sourceLang = mapLanguageCode($sourceLang);
    $targetLang = mapLanguageCode($targetLang);
    
    if ($sourceLang === $targetLang) {
        return $text;
    }
    
    $postData = json_encode([
        'q' => $text,
        'source' => $sourceLang,
        'target' => $targetLang,
        'format' => 'text'
    ]);
    
    $ch = curl_init(LIBRETRANSLATE_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['translatedText'])) {
            return $result['translatedText'];
        }
    }
    
    return $text; // Return original if translation fails
}
?>

