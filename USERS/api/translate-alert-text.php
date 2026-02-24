<?php
/**
 * Translate Alert Text API
 * Client-side translation endpoint for alert card content.
 * Citizen-side translation is ArgosTranslate-only.
 */

// Prevent any output before headers
ob_start();

// Set error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error occurred',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit();
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

// Translation cache backends (MySQL/Neon)
$pdo = $pdo ?? null;
try {
    $dbConnectPath = __DIR__ . '/../../ADMIN/api/db_connect.php';
    if (file_exists($dbConnectPath)) {
        require_once $dbConnectPath;
    }
} catch (Throwable $e) {
    // Translation must still work even if DB cache is unavailable.
    $pdo = null;
    error_log('Translate Alert Text API cache DB bootstrap failed: ' . $e->getMessage());
}
require_once __DIR__ . '/translation-cache-store.php';

// Load translation service configuration
function getTranslationConfig() {
    // Try ADMIN/api/config.local.php first
    $adminConfigPath = __DIR__ . '/../../ADMIN/api/config.local.php';
    $userConfigPath = __DIR__ . '/config.local.php';
    
    $config = [];
    
    // Load ADMIN config if available
    if (file_exists($adminConfigPath)) {
        $adminConfig = require $adminConfigPath;
        if (is_array($adminConfig)) {
            $config = array_merge($config, $adminConfig);
        }
    }
    
    // Load USERS config if available (overrides ADMIN config)
    if (file_exists($userConfigPath)) {
        $userConfig = require $userConfigPath;
        if (is_array($userConfig)) {
            $config = array_merge($config, $userConfig);
        }
    }
    
    // Argos Translate configuration (preferred - offline)
    $argosTranslateUrl = $config['ARGOS_TRANSLATE_URL'] ?? 'http://localhost:5001/translate';
    
    // Translation provider preference (Argos-only for citizen-side translation)
    $translationProvider = strtolower(trim((string)($config['TRANSLATION_PROVIDER'] ?? 'argos')));
    if ($translationProvider !== 'argos') {
        $translationProvider = 'argos';
    }
    
    return [
        'provider' => $translationProvider,
        'argos_url' => $argosTranslateUrl
    ];
}

/**
 * Normalize language code for Argos service.
 * Argos commonly uses "tl" for Filipino/Tagalog.
 */
function mapArgosLanguageCode($lang) {
    $code = strtolower(trim((string)$lang));
    if ($code === 'fil' || $code === 'tl') {
        return 'tl';
    }
    return $code;
}

/**
 * Translate text using Argos Translate API
 * @param string $text Text to translate
 * @param string $targetLang Target language code (e.g., 'es', 'fr')
 * @param string $sourceLang Source language code (default: 'en')
 * @return array ['success' => bool, 'translated_text' => string|null, 'error' => string|null]
 */
function translateWithArgos($text, $targetLang, $sourceLang = 'en') {
    if (empty($text)) {
        return ['success' => true, 'translated_text' => $text, 'error' => null];
    }
    
    // Don't translate if source and target are the same
    if ($sourceLang === $targetLang) {
        return ['success' => true, 'translated_text' => $text, 'error' => null];
    }
    
    $config = getTranslationConfig();
    $apiUrl = $config['argos_url'];
    
    // Prepare request payload
    $argosSource = mapArgosLanguageCode($sourceLang);
    $argosTarget = mapArgosLanguageCode($targetLang);

    $data = [
        'q' => $text,
        'source' => $argosSource,
        'target' => $argosTarget
    ];
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // Allow self-signed certs for localhost
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    // Handle cURL errors
    if ($curlError) {
        error_log("Argos Translate API cURL error: {$curlError} (code: {$curlErrno})");
        return [
            'success' => false,
            'translated_text' => null,
            'error' => 'Connection error: ' . $curlError
        ];
    }
    
    // Handle HTTP errors
    if ($httpCode !== 200) {
        $errorMsg = "HTTP {$httpCode}";
        if ($response) {
            $errorData = json_decode($response, true);
            if (isset($errorData['error'])) {
                $errorMsg = $errorData['error'];
            }
        }
        error_log("Argos Translate API HTTP error: {$errorMsg} (code: {$httpCode})");
        return [
            'success' => false,
            'translated_text' => null,
            'error' => $errorMsg
        ];
    }
    
    // Parse response
    if (!$response) {
        return [
            'success' => false,
            'translated_text' => null,
            'error' => 'Empty response from translation service'
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Argos Translate API JSON decode error: " . json_last_error_msg());
        return [
            'success' => false,
            'translated_text' => null,
            'error' => 'Invalid JSON response from translation service'
        ];
    }
    
    // Argos standard response shape: {"translatedText":"..."}
    if (isset($result['translatedText']) && trim((string)$result['translatedText']) !== '') {
        return [
            'success' => true,
            'translated_text' => $result['translatedText'],
            'error' => null
        ];
    }
    
    // Check for error in response
    if (isset($result['error'])) {
        error_log("Argos Translate API error: " . $result['error']);
        return [
            'success' => false,
            'translated_text' => null,
            'error' => $result['error']
        ];
    }
    
    // Unknown response format
    error_log("Argos Translate API unexpected response format: " . substr($response, 0, 200));
    return [
        'success' => false,
        'translated_text' => null,
        'error' => 'Unexpected response format from translation service'
    ];
}

/**
 * Translate text using ArgosTranslate service
 * @param string $text Text to translate
 * @param string $targetLang Target language code
 * @param string $sourceLang Source language code (default: 'en')
 * @return array ['success' => bool, 'translated_text' => string|null, 'error' => string|null]
 */
function translateText($text, $targetLang, $sourceLang = 'en') {
    $result = translateWithArgos($text, $targetLang, $sourceLang);
    if ($result['success']) {
        return $result;
    }

    error_log("ArgosTranslate unavailable for alert text translation: " . ($result['error'] ?? 'unknown error'));
    return [
        'success' => false,
        'translated_text' => $text,
        'error' => $result['error'] ?? 'ArgosTranslate unavailable'
    ];
}

// Clean output buffer before processing
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Read and decode JSON input with proper error handling
    $rawInput = file_get_contents('php://input');
    if ($rawInput === false) {
        error_log("Failed to read PHP input stream");
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to read request body'
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    $input = json_decode($rawInput, true);
    $jsonError = json_last_error();
    
    // Check if JSON decoding failed
    if ($jsonError !== JSON_ERROR_NONE || $input === null) {
        $errorMessages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        ];
        $errorMessage = $errorMessages[$jsonError] ?? 'Unknown JSON error';
        
        error_log("JSON decode error: {$errorMessage} (code: {$jsonError})");
        error_log("Raw input (first 200 chars): " . substr($rawInput, 0, 200));
        
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON in request body',
            'error' => $errorMessage
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    $targetLanguage = strtolower(trim((string)($input['target_language'] ?? ($input['target_lang'] ?? 'en'))));
    $sourceLanguage = strtolower(trim((string)($input['source_language'] ?? ($input['source_lang'] ?? 'en'))));

    // Accept both payload formats:
    // 1) { texts: { key: "text" }, target_language, source_language }
    // 2) { text: "text", target_lang, source_lang }
    $singleResponseKey = null;
    if (isset($input['texts']) && is_array($input['texts'])) {
        $texts = $input['texts'];
    } elseif (isset($input['text']) && (is_string($input['text']) || is_numeric($input['text']))) {
        $texts = ['text' => (string)$input['text']];
        $singleResponseKey = 'text';
    } else {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request: provide texts[] or text']);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Return original texts if target is English
    if ($targetLanguage === 'en' || empty($targetLanguage)) {
        if (ob_get_level()) {
            ob_clean();
        }
        $response = [
            'success' => true,
            'translations' => $texts
        ];
        if ($singleResponseKey !== null) {
            $response['translated_text'] = (string)($texts[$singleResponseKey] ?? '');
        }
        echo json_encode($response);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Translate each text
    $translations = [];
    $errors = [];
    $allSuccessful = true;
    $cacheDays = translation_cache_days();
    $translationMethod = 'argos';
    
    foreach ($texts as $key => $text) {
        if (empty($text)) {
            $translations[$key] = $text;
            continue;
        }

        $originalText = (string)$text;
        $cacheKey = md5($originalText . $sourceLanguage . $targetLanguage);
        $cachedText = translation_cache_read($cacheKey, $cacheDays, $pdo ?? null);
        if ($cachedText !== null && trim((string)$cachedText) !== '') {
            $translations[$key] = $cachedText;
            continue;
        }

        $result = translateText($originalText, $targetLanguage, $sourceLanguage);
        
        if ($result['success'] && isset($result['translated_text'])) {
            $translatedText = trim($result['translated_text']);
            // Check if translation actually changed the text (ignore if same as original)
            if ($translatedText !== trim($originalText) && !empty($translatedText)) {
                $translations[$key] = $translatedText;
                translation_cache_write(
                    $cacheKey,
                    $originalText,
                    $sourceLanguage,
                    $targetLanguage,
                    $translatedText,
                    $translationMethod,
                    $pdo ?? null
                );
            } else {
                // Translation returned same text or empty - treat as failure
                $translations[$key] = $originalText;
                $errorMsg = 'Translation returned same text or empty result';
                $errors[$key] = $errorMsg;
                $allSuccessful = false;
                error_log("Translation failed for key '{$key}': {$errorMsg} (original: '{$originalText}', returned: '{$translatedText}')");
            }
        } else {
            // If translation fails, use original text
            $translations[$key] = $originalText;
            $errorMsg = $result['error'] ?? 'Unknown error';
            $errors[$key] = $errorMsg;
            $allSuccessful = false;
            error_log("Translation failed for key '{$key}': {$errorMsg}");
        }
    }
    
    // Ensure clean output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    $response = [
        'success' => $allSuccessful,
        'translations' => $translations,
        'target_language' => $targetLanguage
    ];

    if ($singleResponseKey !== null) {
        $response['translated_text'] = (string)($translations[$singleResponseKey] ?? '');
    }
    
    // Include errors if any translations failed
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Some translations failed: ' . implode(', ', array_keys($errors));
    }
    
    echo json_encode($response);
    
    // End output buffering
    if (ob_get_level()) {
        ob_end_flush();
    }
    
} catch (Exception $e) {
    error_log("Translate Alert Text API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation error occurred',
        'error' => $e->getMessage()
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
} catch (Error $e) {
    error_log("Translate Alert Text API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred',
        'error' => $e->getMessage()
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
}
?>
