<?php
/**
 * AI Translation Service
 * Uses Google Gemini AI to translate content into multiple languages
 */

require_once 'db_connect.php';
require_once 'activity_logger.php';

class AITranslationService {
    private $pdo;
    private $apiKey;
    private $apiUrl;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadApiKey();
        $this->loadApiUrl();
    }
    
    /**
     * Load API URL dynamically based on configured model
     */
    private function loadApiUrl() {
        if (file_exists(__DIR__ . '/secure-api-config.php')) {
            require_once __DIR__ . '/secure-api-config.php';
            $model = getGeminiModel();
            $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        } else {
            // Fallback to default
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        }
    }
    
    /**
     * Load Gemini API key securely (from config file, database, or environment)
     */
    private function loadApiKey() {
        // Use secure config helper if available
        if (file_exists(__DIR__ . '/secure-api-config.php')) {
            require_once __DIR__ . '/secure-api-config.php';
            $this->apiKey = getGeminiApiKey();
            if ($this->apiKey) {
                return;
            }
        }
        
        // Fallback to database (for backward compatibility)
        try {
            $stmt = $this->pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            $this->apiKey = $result['api_key'] ?? null;
        } catch (PDOException $e) {
            error_log("Failed to load API key: " . $e->getMessage());
            $this->apiKey = null;
        }
    }
    
    /**
     * Check if AI translation is available
     */
    public function isAvailable() {
        return !empty($this->apiKey);
    }
    
    /**
     * Translate text using AI
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code (e.g., 'fil', 'ceb', 'es', 'fr')
     * @param string $sourceLanguage Source language code (default: 'en')
     * @return array ['success' => bool, 'translated_text' => string, 'error' => string]
     */
    public function translate($text, $targetLanguage, $sourceLanguage = 'en') {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'AI translation service is not configured. Please set up Gemini API key.'
            ];
        }
        
        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Text to translate is empty.'
            ];
        }
        
        // Get language names for better AI prompts
        $languageNames = $this->getLanguageNames();
        $targetLangName = $languageNames[$targetLanguage] ?? $targetLanguage;
        $sourceLangName = $languageNames[$sourceLanguage] ?? $sourceLanguage;
        
        // Create translation prompt
        $prompt = "Translate the following text from {$sourceLangName} to {$targetLangName}. " .
                  "Provide ONLY the translated text without any explanations, notes, or additional text. " .
                  "Maintain the same tone, formality level, and meaning. " .
                  "If the text contains emergency or safety information, ensure accuracy and clarity.\n\n" .
                  "Text to translate:\n{$text}";
        
        try {
            $url = $this->apiUrl . "?key=" . urlencode($this->apiKey);
            
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3, // Lower temperature for more accurate translations
                    'maxOutputTokens' => 2048,
                    'topP' => 0.95,
                    'topK' => 40
                ]
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'CURL Error: ' . $curlError
                ];
            }
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
                return [
                    'success' => false,
                    'error' => 'API Error: ' . $errorMsg
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $translatedText = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
                
                return [
                    'success' => true,
                    'translated_text' => $translatedText,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No translation received from AI'
                ];
            }
            
        } catch (Exception $e) {
            error_log("AI Translation Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Translate alert (title and content)
     * @param int $alertId Alert ID
     * @param string $title Alert title
     * @param string $content Alert content
     * @param string $targetLanguage Target language code
     * @param int $adminId Admin ID performing the translation
     * @return array Result with success status and translations
     */
    public function translateAlert($alertId, $title, $content, $targetLanguage, $adminId = null) {
        $results = [
            'success' => false,
            'title_translation' => null,
            'content_translation' => null,
            'errors' => []
        ];
        
        // Translate title
        $titleResult = $this->translate($title, $targetLanguage);
        if ($titleResult['success']) {
            $results['title_translation'] = $titleResult['translated_text'];
        } else {
            $results['errors'][] = 'Title translation failed: ' . $titleResult['error'];
        }
        
        // Translate content
        $contentResult = $this->translate($content, $targetLanguage);
        if ($contentResult['success']) {
            $results['content_translation'] = $contentResult['translated_text'];
        } else {
            $results['errors'][] = 'Content translation failed: ' . $contentResult['error'];
        }
        
        // If both translations succeeded, save to database
        if ($titleResult['success'] && $contentResult['success']) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO alert_translations (
                        alert_id, target_language, translated_title, translated_content, 
                        status, translated_at, translated_by_admin_id, translation_method
                    )
                    VALUES (?, ?, ?, ?, 'active', NOW(), ?, 'ai')
                    ON DUPLICATE KEY UPDATE 
                        translated_title = VALUES(translated_title),
                        translated_content = VALUES(translated_content),
                        translated_at = NOW(),
                        translated_by_admin_id = VALUES(translated_by_admin_id),
                        translation_method = 'ai'
                ");
                
                $stmt->execute([
                    $alertId,
                    $targetLanguage,
                    $results['title_translation'],
                    $results['content_translation'],
                    $adminId
                ]);
                
                $results['success'] = true;
                $results['translation_id'] = $this->pdo->lastInsertId();
                
                // Log activity
                if ($adminId) {
                    logAdminActivity(
                        $adminId,
                        'ai_translation',
                        "AI translated alert #{$alertId} to {$targetLanguage}"
                    );
                }
                
            } catch (PDOException $e) {
                error_log("Failed to save translation: " . $e->getMessage());
                $results['errors'][] = 'Failed to save translation: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Get language names mapping
     */
    private function getLanguageNames() {
        return [
            'en' => 'English',
            'fil' => 'Filipino (Tagalog)',
            'tl' => 'Filipino (Tagalog)',
            'ceb' => 'Cebuano',
            'ilo' => 'Ilocano',
            'pam' => 'Kapampangan',
            'bcl' => 'Bicolano',
            'war' => 'Waray',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'ru' => 'Russian',
            'tr' => 'Turkish',
            'pl' => 'Polish',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'no' => 'Norwegian',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'cs' => 'Czech',
            'hu' => 'Hungarian',
            'ro' => 'Romanian',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'uk' => 'Ukrainian',
            'sw' => 'Swahili',
            'af' => 'Afrikaans',
            'am' => 'Amharic',
            'az' => 'Azerbaijani',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bs' => 'Bosnian',
            'ca' => 'Catalan',
            'et' => 'Estonian',
            'ga' => 'Irish',
            'is' => 'Icelandic',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'mk' => 'Macedonian',
            'mt' => 'Maltese',
            'ne' => 'Nepali',
            'sr' => 'Serbian',
            'si' => 'Sinhala',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'ur' => 'Urdu'
        ];
    }
    
    /**
     * Get supported languages list
     */
    public function getSupportedLanguages() {
        return array_keys($this->getLanguageNames());
    }
}

// Initialize service if called directly
if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) === 'ai-translation-service.php') {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $service = new AITranslationService($pdo);
        
        if (!$service->isAvailable()) {
            echo json_encode([
                'success' => false,
                'error' => 'AI translation service is not configured'
            ]);
            exit;
        }
        
        $text = $input['text'] ?? '';
        $targetLanguage = $input['target_language'] ?? 'fil';
        $sourceLanguage = $input['source_language'] ?? 'en';
        
        $result = $service->translate($text, $targetLanguage, $sourceLanguage);
        echo json_encode($result);
    } else {
        $service = new AITranslationService($pdo);
        echo json_encode([
            'success' => true,
            'available' => $service->isAvailable(),
            'supported_languages' => $service->getSupportedLanguages()
        ]);
    }
}

