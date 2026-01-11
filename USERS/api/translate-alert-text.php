<?php
/**
 * Translate Alert Text API
 * Client-side translation endpoint for alert card content
 * Uses LibreTranslate API (self-hosted or public)
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

// Load LibreTranslate configuration
function getLibreTranslateConfig() {
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
    
    // Default values
    $libreTranslateUrl = $config['LIBRETRANSLATE_URL'] ?? 'http://localhost:5000/translate';
    $libreTranslateApiKey = $config['LIBRETRANSLATE_API_KEY'] ?? '';
    
    // Fallback to public LibreTranslate if localhost is not accessible
    // User can override this in config.local.php
    if ($libreTranslateUrl === 'http://localhost:5000/translate') {
        // Check if we should use public server instead (user can set in config)
        $libreTranslateUrl = $config['LIBRETRANSLATE_URL'] ?? 'https://libretranslate.com/translate';
    }
    
    return [
        'url' => $libreTranslateUrl,
        'api_key' => $libreTranslateApiKey
    ];
}

/**
 * Translate text using LibreTranslate API
 * @param string $text Text to translate
 * @param string $targetLang Target language code (e.g., 'es', 'fr')
 * @param string $sourceLang Source language code (default: 'en')
 * @return array ['success' => bool, 'translated_text' => string|null, 'error' => string|null]
 */
function translateWithLibreTranslate($text, $targetLang, $sourceLang = 'en') {
    if (empty($text)) {
        return ['success' => true, 'translated_text' => $text, 'error' => null];
    }
    
    // Don't translate if source and target are the same
    if ($sourceLang === $targetLang) {
        return ['success' => true, 'translated_text' => $text, 'error' => null];
    }
    
    $config = getLibreTranslateConfig();
    $apiUrl = $config['url'];
    $apiKey = $config['api_key'];
    
    // Prepare request payload
    $data = [
        'q' => $text,
        'source' => $sourceLang,
        'target' => $targetLang,
        'format' => 'text'
    ];
    
    // Add API key if provided
    if (!empty($apiKey)) {
        $data['api_key'] = $apiKey;
    }
    
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
        CURLOPT_TIMEOUT => 30, // 30 second timeout for self-hosted instances
        CURLOPT_CONNECTTIMEOUT => 10, // 10 second connection timeout
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    // Handle cURL errors (connection timeouts, etc.)
    if ($curlError) {
        error_log("LibreTranslate API cURL error: {$curlError} (code: {$curlErrno})");
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
        error_log("LibreTranslate API HTTP error: {$errorMsg} (code: {$httpCode})");
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
        error_log("LibreTranslate API JSON decode error: " . json_last_error_msg());
        return [
            'success' => false,
            'translated_text' => null,
            'error' => 'Invalid JSON response from translation service'
        ];
    }
    
    // Check for translated text
    if (isset($result['translatedText'])) {
        return [
            'success' => true,
            'translated_text' => $result['translatedText'],
            'error' => null
        ];
    }
    
    // If no translatedText in response, check for error
    if (isset($result['error'])) {
        error_log("LibreTranslate API error: " . $result['error']);
        return [
            'success' => false,
            'translated_text' => null,
            'error' => $result['error']
        ];
    }
    
    // Unknown response format
    error_log("LibreTranslate API unexpected response format: " . substr($response, 0, 200));
    return [
        'success' => false,
        'translated_text' => null,
        'error' => 'Unexpected response format from translation service'
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
    
    if (!isset($input['texts']) || !is_array($input['texts'])) {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request: texts array required']);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    $targetLanguage = $input['target_language'] ?? 'en';
    $sourceLanguage = $input['source_language'] ?? 'en';
    
    // Return original texts if target is English
    if ($targetLanguage === 'en' || empty($targetLanguage)) {
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode([
            'success' => true,
            'translations' => $input['texts']
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Translate each text
    $translations = [];
    $errors = [];
    $allSuccessful = true;
    
    foreach ($input['texts'] as $key => $text) {
        if (empty($text)) {
            $translations[$key] = $text;
            continue;
        }
        
        $result = translateWithLibreTranslate($text, $targetLanguage, $sourceLanguage);
        
        if ($result['success'] && isset($result['translated_text'])) {
            $translatedText = trim($result['translated_text']);
            // Check if translation actually changed the text (ignore if same as original)
            if ($translatedText !== trim($text) && !empty($translatedText)) {
                $translations[$key] = $translatedText;
            } else {
                // Translation returned same text or empty - treat as failure
                $translations[$key] = $text;
                $errorMsg = 'Translation returned same text or empty result';
                $errors[$key] = $errorMsg;
                $allSuccessful = false;
                error_log("Translation failed for key '{$key}': {$errorMsg} (original: '{$text}', returned: '{$translatedText}')");
            }
        } else {
            // If translation fails, use original text
            $translations[$key] = $text;
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
