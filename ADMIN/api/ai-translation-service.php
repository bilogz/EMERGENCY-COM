<?php
/**
 * AI Translation Service
 * Uses Google Gemini AI to translate content into multiple languages
 */

// Only require db_connect.php if $pdo is not already set
if (!isset($pdo) || $pdo === null) {
    require_once 'db_connect.php';
    // Ensure $pdo is in global scope after requiring db_connect.php
    if (isset($pdo) && $pdo !== null) {
        $GLOBALS['pdo'] = $pdo;
    }
}

// Only require activity_logger.php if not already loaded
if (!function_exists('logAdminActivity')) {
    require_once 'activity_logger.php';
}

class AITranslationService {
    private $pdo;
    private $apiKey;
    private $apiUrl;
    private $translationProvider; // 'argos', 'gemini', 'libretranslate', etc.
    private $argosTranslateUrl;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Set global $pdo so secure-api-config.php can access it
        $GLOBALS['pdo'] = $pdo;
        
        // Load translation provider configuration
        try {
            $this->loadTranslationProvider();
        } catch (Throwable $e) {
            error_log("Failed to load translation provider: " . $e->getMessage());
            $this->translationProvider = 'gemini'; // Default fallback
        }
        
        // Try to load API key and URL, but don't fail if they can't be loaded
        try {
            $this->loadApiKey();
        } catch (Throwable $e) {
            error_log("Failed to load API key in AITranslationService: " . $e->getMessage());
            $this->apiKey = null;
        }
        
        try {
            $this->loadApiUrl();
        } catch (Throwable $e) {
            error_log("Failed to load API URL in AITranslationService: " . $e->getMessage());
            // Set a default fallback URL
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        }
    }
    
    /**
     * Load translation provider from config (argos, gemini, libretranslate)
     */
    private function loadTranslationProvider() {
        try {
            // Try config.env.php first (uses getDatabaseConfig)
            if (file_exists(__DIR__ . '/config.env.php')) {
                require_once __DIR__ . '/config.env.php';
                $dbConfig = getDatabaseConfig();
                $this->translationProvider = $dbConfig['translation_provider'] ?? 'gemini';
                $this->argosTranslateUrl = $dbConfig['argos_translate_url'] ?? 'http://localhost:5001/translate';
            }
            // Also check config.local.php for direct config values
            elseif (file_exists(__DIR__ . '/config.local.php')) {
                $localConfig = require __DIR__ . '/config.local.php';
                $this->translationProvider = $localConfig['TRANSLATION_PROVIDER'] ?? 'gemini';
                $this->argosTranslateUrl = $localConfig['ARGOS_TRANSLATE_URL'] ?? 'http://localhost:5001/translate';
            } else {
                $this->translationProvider = 'gemini';
                $this->argosTranslateUrl = 'http://localhost:5001/translate';
            }
        } catch (Throwable $e) {
            error_log("Error loading translation provider config: " . $e->getMessage());
            $this->translationProvider = 'gemini';
            $this->argosTranslateUrl = 'http://localhost:5001/translate';
        }
    }
    
    /**
     * Load API URL dynamically based on configured model
     */
    private function loadApiUrl() {
        try {
            // Ensure $pdo is in global scope before requiring secure-api-config.php
            if (isset($this->pdo)) {
                $GLOBALS['pdo'] = $this->pdo;
            }
            
            if (file_exists(__DIR__ . '/secure-api-config.php')) {
                require_once __DIR__ . '/secure-api-config.php';
                if (function_exists('getGeminiModel')) {
                    $model = getGeminiModel();
                    $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
                } else {
                    // Fallback to default if function doesn't exist
                    $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
                }
            } else {
                // Fallback to default
                $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
            }
        } catch (Throwable $e) {
            error_log("Error in loadApiUrl: " . $e->getMessage());
            // Set default fallback
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        }
    }
    
    /**
     * Load Gemini API key securely (from config file, database, or environment)
     * Uses 'translation' purpose to get AI_API_KEY_TRANSLATION for alert translations
     */
    private function loadApiKey() {
        try {
            // Ensure $pdo is in global scope before requiring secure-api-config.php
            if (isset($this->pdo)) {
                $GLOBALS['pdo'] = $this->pdo;
            }
            
            // Use secure config helper if available
            if (file_exists(__DIR__ . '/secure-api-config.php')) {
                require_once __DIR__ . '/secure-api-config.php';
                // Use 'translation' purpose to get AI_API_KEY_TRANSLATION (AI-Alert-Translator)
                if (function_exists('getGeminiApiKey')) {
                    $this->apiKey = getGeminiApiKey('translation');
                    if ($this->apiKey) {
                        return;
                    }
                }
            }
            
            // Fallback to database (for backward compatibility)
            if ($this->pdo) {
                try {
                    $stmt = $this->pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' LIMIT 1");
                    $stmt->execute();
                    $result = $stmt->fetch();
                    $this->apiKey = $result['api_key'] ?? null;
                } catch (PDOException $e) {
                    error_log("Failed to load API key from database: " . $e->getMessage());
                    $this->apiKey = null;
                }
            } else {
                $this->apiKey = null;
            }
        } catch (Throwable $e) {
            error_log("Error in loadApiKey: " . $e->getMessage());
            $this->apiKey = null;
        }
    }
    
    /**
     * Check if AI translation is available
     */
    public function isAvailable() {
        // Ensure $pdo is in global scope before checking
        if (isset($this->pdo)) {
            $GLOBALS['pdo'] = $this->pdo;
        }
        
        // Check if AI translation is specifically enabled
        if (function_exists('isAIAnalysisEnabled')) {
            // Check if translation is specifically enabled
            if (!isAIAnalysisEnabled('translation')) {
                return false;
            }
        }
        
        // For Argos Translate, we don't need an API key, just check if URL is configured
        if ($this->translationProvider === 'argos') {
            return !empty($this->argosTranslateUrl);
        }
        
        // For Gemini and other services, need API key
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
            // Check if translation is specifically disabled
            if (function_exists('isAIAnalysisEnabled')) {
                if (!isAIAnalysisEnabled('translation')) {
                    return [
                        'success' => false,
                        'error' => 'AI Translation API is currently disabled. Please enable it in General Settings → System Settings → AI Translation API to use translation features.'
                    ];
                }
            }
            return [
                'success' => false,
                'error' => 'Translation service is not configured.'
            ];
        }
        
        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Text to translate is empty.'
            ];
        }
        
        // Use Argos Translate if configured
        if ($this->translationProvider === 'argos') {
            return $this->translateWithArgos($text, $targetLanguage, $sourceLanguage);
        }
        
        // Otherwise use Gemini (default)
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
            // Check if we have required configuration
            if (empty($this->apiUrl)) {
                return [
                    'success' => false,
                    'error' => 'Translation service URL is not configured'
                ];
            }
            
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
            if ($ch === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize CURL'
                ];
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Reduced timeout to fail faster
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Connection timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("CURL Error in translation: " . $curlError);
                return [
                    'success' => false,
                    'error' => 'Connection error: ' . $curlError
                ];
            }
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
                error_log("Translation API returned HTTP $httpCode: " . $errorMsg);
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
            
        } catch (Throwable $e) {
            error_log("AI Translation Error: " . $e->getMessage());
            error_log("Error type: " . get_class($e));
            return [
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Translate using Argos Translate service
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language code (default: 'en')
     * @return array ['success' => bool, 'translated_text' => string, 'error' => string]
     */
    private function translateWithArgos($text, $targetLanguage, $sourceLanguage = 'en') {
        try {
            if (empty($this->argosTranslateUrl)) {
                return [
                    'success' => false,
                    'error' => 'Argos Translate URL is not configured'
                ];
            }
            
            // Map language codes for Argos Translate (e.g., 'fil' -> 'tl')
            $langMap = ['fil' => 'tl', 'tl' => 'tl'];
            $argosSource = $langMap[$sourceLanguage] ?? $sourceLanguage;
            $argosTarget = $langMap[$targetLanguage] ?? $targetLanguage;
            
            $data = [
                'q' => $text,
                'source' => $argosSource,
                'target' => $argosTarget
            ];
            
            $ch = curl_init($this->argosTranslateUrl);
            if ($ch === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize CURL for Argos Translate'
                ];
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout - fail fast if service is down
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Very short connection timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("Argos Translate CURL Error: " . $curlError);
                return [
                    'success' => false,
                    'error' => 'Argos Translate service unavailable: ' . $curlError
                ];
            }
            
            if ($httpCode !== 200) {
                error_log("Argos Translate returned HTTP $httpCode");
                return [
                    'success' => false,
                    'error' => 'Argos Translate service returned error code: ' . $httpCode
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if (isset($responseData['translatedText'])) {
                return [
                    'success' => true,
                    'translated_text' => trim($responseData['translatedText']),
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage
                ];
            } else {
                error_log("Argos Translate response: " . $response);
                return [
                    'success' => false,
                    'error' => 'Invalid response from Argos Translate service'
                ];
            }
            
        } catch (Throwable $e) {
            error_log("Argos Translate Error: " . $e->getMessage());
            error_log("Error type: " . get_class($e));
            return [
                'success' => false,
                'error' => 'Argos Translate failed: ' . $e->getMessage()
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

