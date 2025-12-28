<?php
/**
 * Auto-Translation API using LibreTranslate (Free & Open Source)
 * Translates text to any supported language
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

require_once '../../ADMIN/api/db_connect.php';

$targetLang = $_POST['target_lang'] ?? $_GET['target_lang'] ?? 'en';
$text = $_POST['text'] ?? $_GET['text'] ?? '';
$sourceLang = $_POST['source_lang'] ?? $_GET['source_lang'] ?? 'en';

if (empty($text)) {
    echo json_encode([
        'success' => false,
        'message' => 'No text provided'
    ]);
    exit;
}

// Language code mapping (ISO 639-1 to common codes)
$langMap = [
    'fil' => 'tl',  // Filipino -> Tagalog
    'tl' => 'tl',   // Tagalog
    'ceb' => 'tl',  // Cebuano -> Tagalog (closest match)
    'ilo' => 'tl',  // Ilocano -> Tagalog
    'pam' => 'tl',  // Kapampangan -> Tagalog
    'zh-TW' => 'zh', // Traditional Chinese -> Chinese
];

$targetLang = $langMap[$targetLang] ?? $targetLang;
$sourceLang = $langMap[$sourceLang] ?? $sourceLang;

// If target is same as source, return original
if ($targetLang === $sourceLang) {
    echo json_encode([
        'success' => true,
        'translated_text' => $text,
        'source_lang' => $sourceLang,
        'target_lang' => $targetLang,
        'method' => 'no_translation_needed'
    ]);
    exit;
}

// Check cache in database first
try {
    if ($pdo) {
        $cacheKey = md5($text . $sourceLang . $targetLang);
        $stmt = $pdo->prepare("
            SELECT translated_text, created_at 
            FROM translation_cache 
            WHERE cache_key = ? 
            AND TIMESTAMPDIFF(DAY, created_at, NOW()) < 30
        ");
        $stmt->execute([$cacheKey]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            echo json_encode([
                'success' => true,
                'translated_text' => $cached['translated_text'],
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'method' => 'cache',
                'cached_at' => $cached['created_at']
            ]);
            exit;
        }
    }
} catch (Exception $e) {
    // Cache check failed, continue with translation
}

// Use LibreTranslate API (Free, open-source)
$apiUrl = 'https://libretranslate.com/translate';

$postData = [
    'q' => $text,
    'source' => $sourceLang,
    'target' => $targetLang,
    'format' => 'text'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    
    if (isset($result['translatedText'])) {
        $translatedText = $result['translatedText'];
        
        // Cache the translation
        try {
            if ($pdo) {
                $cacheKey = md5($text . $sourceLang . $targetLang);
                $stmt = $pdo->prepare("
                    INSERT INTO translation_cache 
                    (cache_key, source_text, source_lang, target_lang, translated_text, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    translated_text = VALUES(translated_text),
                    created_at = NOW()
                ");
                $stmt->execute([$cacheKey, $text, $sourceLang, $targetLang, $translatedText]);
            }
        } catch (Exception $e) {
            // Cache save failed, but we have the translation
        }
        
        echo json_encode([
            'success' => true,
            'translated_text' => $translatedText,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'method' => 'libretranslate_api'
        ]);
        exit;
    }
}

// Translation failed, return original text
echo json_encode([
    'success' => false,
    'message' => 'Translation service unavailable',
    'translated_text' => $text,
    'source_lang' => $sourceLang,
    'target_lang' => $targetLang,
    'method' => 'fallback',
    'error' => $error ?: 'API returned invalid response'
]);
?>

