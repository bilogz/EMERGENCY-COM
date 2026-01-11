<?php
/**
 * Alert Translation Helper
 * Automatically translates alerts based on user language preferences
 */

require_once 'db_connect.php';
require_once 'ai-translation-service.php';

class AlertTranslationHelper {
    private $pdo;
    private $aiService;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->aiService = new AITranslationService($pdo);
    }
    
    /**
     * Get user's preferred language
     * Checks user_preferences table, users table, guest sessions, or falls back to device language
     */
    public function getUserLanguage($userId) {
        // Handle guest users (guest_* prefix)
        if ($userId && strpos($userId, 'guest_') === 0) {
            // For guests, check session or default to browser language
            // Note: In production, guest language should be passed from frontend
            // For now, we'll check if there's a way to get it from session
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['guest_language'])) {
                return $_SESSION['guest_language'];
            }
            // Try to detect from Accept-Language header
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            if ($acceptLanguage) {
                $langCode = strtolower(explode('-', explode(',', $acceptLanguage)[0])[0]);
                // Validate it's a reasonable language code
                if (strlen($langCode) === 2) {
                    return $langCode;
                }
            }
            return 'en'; // Default for guests
        }
        
        if (!$userId) {
            return 'en'; // Default for guests
        }
        
        try {
            // First check user_preferences table
            $stmt = $this->pdo->prepare("
                SELECT preferred_language FROM user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['preferred_language']) {
                return $result['preferred_language'];
            }
            
            // Fallback to users table
            $stmt = $this->pdo->prepare("
                SELECT preferred_language FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['preferred_language']) {
                return $result['preferred_language'];
            }
        } catch (PDOException $e) {
            error_log("Error getting user language: " . $e->getMessage());
        }
        
        return 'en'; // Default fallback
    }
    
    /**
     * Get user language with explicit language parameter (for API calls)
     * This allows passing language preference directly when sending alerts
     */
    public function getUserLanguageWithFallback($userId, $explicitLanguage = null) {
        // If explicit language is provided, use it
        if ($explicitLanguage && strlen($explicitLanguage) >= 2) {
            return $explicitLanguage;
        }
        
        // Otherwise, use the standard method
        return $this->getUserLanguage($userId);
    }
    
    /**
     * Get or create translation for an alert
     * Returns translated title and message
     * @param int $alertId Alert ID
     * @param string|null $targetLanguage Explicit target language (optional)
     * @param string|int|null $userId User ID or guest ID (optional)
     * @param string|null $explicitLanguage Explicit language preference (for API calls)
     */
    public function getTranslatedAlert($alertId, $targetLanguage = null, $userId = null, $explicitLanguage = null) {
        // Determine target language
        if (!$targetLanguage) {
            if ($explicitLanguage) {
                $targetLanguage = $explicitLanguage;
            } elseif ($userId) {
                $targetLanguage = $this->getUserLanguage($userId);
            } else {
                $targetLanguage = 'en'; // Default to English
            }
        }
        
        if (!$targetLanguage || $targetLanguage === 'en') {
            // Return original English alert
            return $this->getOriginalAlert($alertId);
        }
        
        // Check if translation exists
        // Try both column name formats for compatibility
        try {
            $stmt = $this->pdo->prepare("
                SELECT translated_title, translated_content, translation_method, ai_model
                FROM alert_translations
                WHERE alert_id = ? AND target_language = ? AND status = 'active'
                ORDER BY translated_at DESC, created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$alertId, $targetLanguage]);
            $translation = $stmt->fetch();
            
            if ($translation && isset($translation['translated_title'])) {
                return [
                    'title' => $translation['translated_title'],
                    'message' => $translation['translated_content'],
                    'language' => $targetLanguage,
                    'method' => $translation['translation_method'] ?? 'manual',
                    'ai_model' => $translation['ai_model'] ?? null
                ];
            }
        } catch (PDOException $e) {
            // Try alternative column names (for backward compatibility)
            try {
                $stmt = $this->pdo->prepare("
                    SELECT title, message, translation_method, ai_model
                    FROM alert_translations
                    WHERE alert_id = ? AND target_language = ? AND status = 'active'
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$alertId, $targetLanguage]);
                $translation = $stmt->fetch();
                
                if ($translation && isset($translation['title'])) {
                    return [
                        'title' => $translation['title'],
                        'message' => $translation['message'],
                        'language' => $targetLanguage,
                        'method' => $translation['translation_method'] ?? 'manual',
                        'ai_model' => $translation['ai_model'] ?? null
                    ];
                }
            } catch (PDOException $e2) {
                error_log("Error fetching translation: " . $e2->getMessage());
            }
        }
        
        // Translation doesn't exist - try to auto-translate
        return $this->autoTranslateAlert($alertId, $targetLanguage);
    }
    
    /**
     * Get original alert
     */
    private function getOriginalAlert($alertId) {
        // Check if content column exists
        $hasContent = false;
        try {
            $checkStmt = $this->pdo->query("SHOW COLUMNS FROM alerts LIKE 'content'");
            $hasContent = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Column might not exist, continue without it
        }
        
        $fields = "id, title, message";
        if ($hasContent) {
            $fields .= ", content";
        }
        $fields .= ", category, severity, location, created_at";
        
        $stmt = $this->pdo->prepare("
            SELECT {$fields}
            FROM alerts
            WHERE id = ?
        ");
        $stmt->execute([$alertId]);
        $alert = $stmt->fetch();
        
        if ($alert) {
            $result = [
                'title' => $alert['title'],
                'message' => $alert['message'],
                'language' => 'en',
                'method' => 'original'
            ];
            if ($hasContent && isset($alert['content'])) {
                $result['content'] = $alert['content'];
            }
            return $result;
        }
        
        return null;
    }
    
    /**
     * Auto-translate alert using AI
     */
    private function autoTranslateAlert($alertId, $targetLanguage) {
        // Get original alert
        $original = $this->getOriginalAlert($alertId);
        if (!$original) {
            return null;
        }
        
        // Check if AI translation is available
        if (!$this->aiService->isAvailable()) {
            // Fallback to original
            return $original;
        }
        
        // Check if language exists in supported_languages table (optional check)
        // If table doesn't exist or check fails, proceed anyway (AI can translate any language)
        try {
            $stmt = $this->pdo->prepare("
                SELECT is_ai_supported FROM supported_languages 
                WHERE language_code = ? AND is_active = 1
            ");
            $stmt->execute([$targetLanguage]);
            $lang = $stmt->fetch();
            
            // If language exists in table and is_ai_supported is explicitly 0, skip
            if ($lang && isset($lang['is_ai_supported']) && $lang['is_ai_supported'] == 0) {
                // Language explicitly marked as not AI-supported
                return $original;
            }
            // Otherwise proceed with AI translation (table doesn't exist, or is_ai_supported is 1/null)
        } catch (PDOException $e) {
            // Table might not exist or column might not exist - proceed anyway
            error_log("Note: Could not check supported_languages table: " . $e->getMessage());
            // Continue with AI translation
        }
        
        try {
            // Translate title
            $titleResult = $this->aiService->translate(
                $original['title'],
                $targetLanguage,
                'en'
            );
            
            // Translate message
            $messageResult = $this->aiService->translate(
                $original['message'],
                $targetLanguage,
                'en'
            );
            
            $translatedTitle = $titleResult['success'] ? $titleResult['translated_text'] : null;
            $translatedMessage = $messageResult['success'] ? $messageResult['translated_text'] : null;
            
            if ($translatedTitle && $translatedMessage) {
                // Save translation to database
                $this->saveTranslation(
                    $alertId,
                    $targetLanguage,
                    $translatedTitle,
                    $translatedMessage,
                    'ai',
                    null // No admin ID for auto-translations
                );
                
                return [
                    'title' => $translatedTitle,
                    'message' => $translatedMessage,
                    'language' => $targetLanguage,
                    'method' => 'ai',
                    'ai_model' => 'gemini-2.5-flash' // Default model name
                ];
            }
        } catch (Exception $e) {
            error_log("Auto-translation error: " . $e->getMessage());
        }
        
        // Fallback to original
        return $original;
    }
    
    /**
     * Save translation to database
     */
    private function saveTranslation($alertId, $targetLanguage, $title, $message, $method = 'ai', $adminId = null) {
        try {
            // Try with standard column names first
            $stmt = $this->pdo->prepare("
                INSERT INTO alert_translations (
                    alert_id,
                    target_language,
                    translated_title,
                    translated_content,
                    translation_method,
                    ai_model,
                    translated_by_admin_id,
                    status,
                    translated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                ON DUPLICATE KEY UPDATE
                    translated_title = VALUES(translated_title),
                    translated_content = VALUES(translated_content),
                    translation_method = VALUES(translation_method),
                    ai_model = VALUES(ai_model),
                    updated_at = NOW()
            ");
            
            $aiModel = ($method === 'ai') ? 'gemini-2.5-flash' : null;
            
            $stmt->execute([
                $alertId,
                $targetLanguage,
                $title,
                $message,
                $method,
                $aiModel,
                $adminId
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Try alternative column names for backward compatibility
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO alert_translations (
                        alert_id,
                        target_language,
                        title,
                        message,
                        translation_method,
                        ai_model,
                        translated_by_admin_id,
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                    ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        message = VALUES(message),
                        translation_method = VALUES(translation_method),
                        ai_model = VALUES(ai_model),
                        updated_at = NOW()
                ");
                
                $aiModel = ($method === 'ai') ? 'gemini-2.5-flash' : null;
                
                $stmt->execute([
                    $alertId,
                    $targetLanguage,
                    $title,
                    $message,
                    $method,
                    $aiModel,
                    $adminId
                ]);
                
                return true;
            } catch (PDOException $e2) {
                error_log("Error saving translation: " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Translate alert for multiple users
     * Returns array of user_id => translated_alert
     * @param int $alertId Alert ID
     * @param array $userIds Array of user IDs
     * @param array|null $userLanguages Optional array of user_id => language_code for explicit preferences
     */
    public function translateAlertForUsers($alertId, $userIds, $userLanguages = null) {
        $results = [];
        
        foreach ($userIds as $userId) {
            // Use explicit language if provided, otherwise get from user preference
            $language = null;
            if ($userLanguages && isset($userLanguages[$userId])) {
                $language = $userLanguages[$userId];
            } else {
                $language = $this->getUserLanguage($userId);
            }
            
            $translated = $this->getTranslatedAlert($alertId, $language, $userId);
            
            if ($translated) {
                $results[$userId] = $translated;
            }
        }
        
        return $results;
    }
    
    /**
     * Get alert message for sending (SMS/Email)
     * Formats the alert appropriately for the channel
     * @param int $alertId Alert ID
     * @param string|int|null $userId User ID or guest ID
     * @param string $channel Channel type ('sms', 'email', etc.)
     * @param string|null $explicitLanguage Explicit language preference (optional)
     */
    public function getAlertMessageForChannel($alertId, $userId, $channel = 'sms', $explicitLanguage = null) {
        $alert = $this->getTranslatedAlert($alertId, null, $userId, $explicitLanguage);
        
        if (!$alert) {
            return null;
        }
        
        // Format based on channel
        if ($channel === 'sms') {
            // Short format for SMS
            return $alert['title'] . "\n\n" . substr($alert['message'], 0, 140);
        } else {
            // Full format for email
            return [
                'subject' => $alert['title'],
                'body' => $alert['message']
            ];
        }
    }
}

