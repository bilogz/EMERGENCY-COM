<?php
/**
 * Translate Alert Text API
 * Client-side translation endpoint for alert card content
 */

// Prevent any output before headers
ob_start();

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
$aiTranslationServicePath = __DIR__ . '/../../ADMIN/api/ai-translation-service.php';
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

try {
    require_once $aiTranslationServicePath;
    
    // Verify the class exists after loading
    if (!class_exists('AITranslationService')) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'AITranslationService class not found after loading file'
        ]);
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load translation service',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
} catch (Error $e) {
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
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
        $translationService = new AITranslationService($pdo);
    } catch (Exception $e) {
        error_log("Failed to initialize AITranslationService: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to initialize translation service',
            'error' => $e->getMessage(),
            'translations' => $input['texts']
        ]);
        exit;
    } catch (Error $e) {
        error_log("Fatal error initializing AITranslationService: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error initializing translation service',
            'error' => $e->getMessage(),
            'translations' => $input['texts']
        ]);
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
    foreach ($input['texts'] as $key => $text) {
        if (empty($text)) {
            $translations[$key] = $text;
            continue;
        }
        
        $result = $translationService->translate($text, $targetLanguage, $sourceLanguage);
        
        if ($result['success'] && isset($result['translated_text'])) {
            $translations[$key] = $result['translated_text'];
        } else {
            // If translation fails, use original text
            $translations[$key] = $text;
            error_log("Translation failed for key '{$key}': " . ($result['error'] ?? 'Unknown error'));
        }
    }
    
    // Ensure clean output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => true,
        'translations' => $translations,
        'target_language' => $targetLanguage
    ]);
    
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
