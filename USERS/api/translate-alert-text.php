<?php
/**
 * Translate Alert Text API
 * Client-side translation endpoint for alert card content
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

// Load database connection (try local first, then ADMIN)
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} elseif (file_exists(__DIR__ . '/../../ADMIN/api/db_connect.php')) {
    require_once __DIR__ . '/../../ADMIN/api/db_connect.php';
} else {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found'
    ]);
    exit;
}

// Check if PDO connection is available
if (!isset($pdo) || $pdo === null) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not available'
    ]);
    exit;
}

// Load AI translation service
// Set include path so relative requires in ai-translation-service.php work correctly
$adminApiPath = __DIR__ . '/../../ADMIN/api';
set_include_path(get_include_path() . PATH_SEPARATOR . $adminApiPath);

$aiTranslationServicePath = $adminApiPath . '/ai-translation-service.php';
if (!file_exists($aiTranslationServicePath)) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation service file not found',
        'path_checked' => $aiTranslationServicePath
    ]);
    exit;
}

// Also ensure activity_logger.php is available (required by ai-translation-service.php)
$activityLoggerPath = $adminApiPath . '/activity_logger.php';
if (!file_exists($activityLoggerPath)) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Activity logger file not found (required by translation service)',
        'path_checked' => $activityLoggerPath
    ]);
    exit;
}

try {
    // Save original include path and working directory
    $originalIncludePath = get_include_path();
    $originalDir = getcwd();
    
    // Store our existing $pdo (from USERS/api/db_connect.php)
    $existingPdo = $pdo;
    
    // IMPORTANT: Set $pdo in global scope so secure-api-config.php can access it
    $GLOBALS['pdo'] = $existingPdo;
    
    // Change to ADMIN/api directory so relative requires in ai-translation-service.php work
    if (!chdir($adminApiPath)) {
        throw new Exception("Failed to change directory to: " . $adminApiPath);
    }
    
    // Add ADMIN/api to include path as well
    set_include_path($adminApiPath . PATH_SEPARATOR . $originalIncludePath);
    
    // Require the file (it will require ADMIN/api/db_connect.php which might set a new $pdo)
    // Use absolute path to avoid issues with chdir
    $aiServiceFile = $adminApiPath . '/ai-translation-service.php';
    if (!file_exists($aiServiceFile)) {
        throw new Exception("Translation service file not found: " . $aiServiceFile);
    }
    
    // Suppress warnings but catch fatal errors
    $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', 0);
    
    try {
        require_once $aiServiceFile;
    } catch (ParseError $e) {
        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);
        throw new Exception("Parse error in translation service: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    } catch (Error $e) {
        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);
        throw new Exception("Fatal error in translation service: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    }
    
    error_reporting($oldErrorReporting);
    ini_set('display_errors', $oldDisplayErrors);
    
    // Use our original $pdo (from USERS) instead of the one from ADMIN/api/db_connect.php
    // This ensures we use the correct database connection
    $pdo = $existingPdo;
    $GLOBALS['pdo'] = $existingPdo;
    
    // Restore original directory and include path
    @chdir($originalDir);
    set_include_path($originalIncludePath);
    
    // Verify the class exists after loading
    if (!class_exists('AITranslationService')) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'AITranslationService class not found after loading file',
            'debug' => [
                'file_loaded' => $aiServiceFile,
                'file_exists' => file_exists($aiServiceFile),
                'current_dir' => getcwd(),
                'include_path' => get_include_path(),
                'defined_classes' => get_declared_classes()
            ]
        ]);
        exit;
    }
} catch (Exception $e) {
    // Restore directory and include path if changed
    if (isset($originalDir)) {
        @chdir($originalDir);
    }
    if (isset($originalIncludePath)) {
        set_include_path($originalIncludePath);
    }
    ob_clean();
    http_response_code(500);
    $errorDetails = [
        'success' => false,
        'message' => 'Failed to load translation service',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    // Add debug info in development mode (remove in production)
    if (isset($_GET['debug']) || (defined('DEBUG_MODE') && DEBUG_MODE)) {
        $errorDetails['debug'] = [
            'admin_api_path' => $adminApiPath,
            'ai_service_file' => $aiTranslationServicePath,
            'file_exists' => file_exists($aiTranslationServicePath),
            'current_dir' => getcwd(),
            'original_dir' => $originalDir ?? 'not set',
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo json_encode($errorDetails);
    exit;
} catch (Error $e) {
    // Restore directory and include path if changed
    if (isset($originalDir)) {
        @chdir($originalDir);
    }
    if (isset($originalIncludePath)) {
        set_include_path($originalIncludePath);
    }
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error loading translation service',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// Clean output buffer before processing
ob_clean();

// Verify AITranslationService class is available before processing
if (!class_exists('AITranslationService')) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation service class not available',
        'error' => 'AITranslationService class not found. Please check server logs for details.'
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

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
    
    if ($targetLanguage === 'en' || empty($targetLanguage)) {
        // Return original texts if target is English
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
    
    // Initialize translation service
    try {
        // Ensure $pdo is in global scope before initializing
        $GLOBALS['pdo'] = $pdo;
        $translationService = new AITranslationService($pdo);
    } catch (Exception $e) {
        error_log("Failed to initialize AITranslationService: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        $response = [
            'success' => false,
            'message' => 'Failed to initialize translation service',
            'error' => $e->getMessage()
        ];
        // Only include translations if input was successfully parsed
        if (isset($input['texts']) && is_array($input['texts'])) {
            $response['translations'] = $input['texts'];
        }
        echo json_encode($response);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    } catch (Error $e) {
        error_log("Fatal error initializing AITranslationService: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        $response = [
            'success' => false,
            'message' => 'Fatal error initializing translation service',
            'error' => $e->getMessage()
        ];
        // Only include translations if input was successfully parsed
        if (isset($input['texts']) && is_array($input['texts'])) {
            $response['translations'] = $input['texts'];
        }
        echo json_encode($response);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    if (!$translationService->isAvailable()) {
        // If translation service not available, return original texts
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => 'AI Translation API is not available (check General Settings → AI Translation API toggle)',
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
        
        $result = $translationService->translate($text, $targetLanguage, $sourceLanguage);
        
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
