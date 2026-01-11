<?php
/**
 * Translate Alert Text API
 * Client-side translation endpoint for alert card content
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

require_once '../../ADMIN/api/db_connect.php';
require_once '../../ADMIN/api/ai-translation-service.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['texts']) || !is_array($input['texts'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request: texts array required']);
        exit;
    }
    
    $targetLanguage = $input['target_language'] ?? 'en';
    $sourceLanguage = $input['source_language'] ?? 'en';
    
    if ($targetLanguage === 'en' || empty($targetLanguage)) {
        // Return original texts if target is English
        echo json_encode([
            'success' => true,
            'translations' => $input['texts']
        ]);
        exit;
    }
    
    // Initialize translation service
    $translationService = new AITranslationService($pdo);
    
    if (!$translationService->isAvailable()) {
        // If translation service not available, return original texts
        echo json_encode([
            'success' => false,
            'message' => 'AI Translation API is not available',
            'translations' => $input['texts']
        ]);
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
    
    echo json_encode([
        'success' => true,
        'translations' => $translations,
        'target_language' => $targetLanguage
    ]);
    
} catch (Exception $e) {
    error_log("Translate Alert Text API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Translation error occurred',
        'error' => $e->getMessage()
    ]);
}
?>
