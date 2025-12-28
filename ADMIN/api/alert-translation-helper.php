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
     * Checks user_preferences table, falls back to device language
     */
    public function getUserLanguage($userId) {
        if (!$userId) {
            return 'en'; // Default for guests
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT preferred_language FROM user_preferences 
                WHERE user_id = ?
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
     * Get or create translation for an alert
     * Returns translated title and message
     */
    public function getTranslatedAlert($alertId, $targetLanguage = null, $userId = null) {
        // Determine target language
        if (!$targetLanguage && $userId) {
            $targetLanguage = $this->getUserLanguage($userId);
        }
        
        if (!$targetLanguage || $targetLanguage === 'en') {
            // Return original English alert
            return $this->getOriginalAlert($alertId);
        }
        
        // Check if translation exists
        $stmt = $this->pdo->prepare("
            SELECT title, message, translation_method, ai_model
            FROM alert_translations
            WHERE alert_id = ? AND target_language = ? AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$alertId, $targetLanguage]);
        $translation = $stmt->fetch();
        
        if ($translation) {
            return [
                'title' => $translation['title'],
                'message' => $translation['message'],
                'language' => $targetLanguage,
                'method' => $translation['translation_method'],
                'ai_model' => $translation['ai_model']
            ];
        }
        
        // Translation doesn't exist - try to auto-translate
        return $this->autoTranslateAlert($alertId, $targetLanguage);
    }
    
    /**
     * Get original alert
     */
    private function getOriginalAlert($alertId) {
        $stmt = $this->pdo->prepare("
            SELECT id, title, message, category, severity, location, created_at
            FROM alerts
            WHERE id = ?
        ");
        $stmt->execute([$alertId]);
        $alert = $stmt->fetch();
        
        if ($alert) {
            return [
                'title' => $alert['title'],
                'message' => $alert['message'],
                'language' => 'en',
                'method' => 'original'
            ];
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
        
        // Check if language supports AI translation
        $stmt = $this->pdo->prepare("
            SELECT is_ai_supported FROM supported_languages 
            WHERE language_code = ? AND is_active = 1
        ");
        $stmt->execute([$targetLanguage]);
        $lang = $stmt->fetch();
        
        if (!$lang || !$lang['is_ai_supported']) {
            // Language doesn't support AI translation
            return $original;
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
        } catch (PDOException $e) {
            error_log("Error saving translation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Translate alert for multiple users
     * Returns array of user_id => translated_alert
     */
    public function translateAlertForUsers($alertId, $userIds) {
        $results = [];
        
        foreach ($userIds as $userId) {
            $language = $this->getUserLanguage($userId);
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
     */
    public function getAlertMessageForChannel($alertId, $userId, $channel = 'sms') {
        $alert = $this->getTranslatedAlert($alertId, null, $userId);
        
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

