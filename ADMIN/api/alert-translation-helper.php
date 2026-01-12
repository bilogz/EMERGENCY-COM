<?php
/**
 * Alert Translation Helper - RESET VERSION
 * Simplified version - only retrieves translations from database
 */

require_once 'db_connect.php';

class AlertTranslationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get translated alert from database
     * Returns original if translation doesn't exist
     */
    public function getTranslatedAlert($alertId, $targetLanguage = null, $userId = null, $explicitLanguage = null) {
        // If English or no language specified, return original
        if (!$targetLanguage || $targetLanguage === 'en') {
            return $this->getOriginalAlert($alertId);
        }
        
        // Check if translation exists
        try {
            $stmt = $this->pdo->prepare("
                SELECT translated_title, translated_content
                FROM alert_translations
                WHERE alert_id = ? AND target_language = ? AND status = 'active'
                ORDER BY translated_at DESC
                LIMIT 1
            ");
            $stmt->execute([$alertId, $targetLanguage]);
            $translation = $stmt->fetch();
            
            if ($translation && isset($translation['translated_title'])) {
                return [
                    'title' => $translation['translated_title'],
                    'message' => $translation['translated_content'],
                    'language' => $targetLanguage,
                    'method' => 'manual'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error fetching translation: " . $e->getMessage());
        }
        
        // Return original if translation doesn't exist
        return $this->getOriginalAlert($alertId);
    }
    
    /**
     * Get original alert
     */
    private function getOriginalAlert($alertId) {
        $stmt = $this->pdo->prepare("
            SELECT id, title, message, content, created_at
            FROM alerts
            WHERE id = ?
        ");
        $stmt->execute([$alertId]);
        $alert = $stmt->fetch();
        
        if ($alert) {
            return [
                'title' => $alert['title'],
                'message' => $alert['message'],
                'content' => $alert['content'] ?? $alert['message'],
                'language' => 'en',
                'method' => 'original'
            ];
        }
        
        return null;
    }
    
    /**
     * Translate alert for multiple users
     */
    public function translateAlertForUsers($alertId, $userIds, $userLanguages = null) {
        $results = [];
        foreach ($userIds as $userId) {
            $language = $userLanguages[$userId] ?? 'en';
            $translated = $this->getTranslatedAlert($alertId, $language, $userId);
            if ($translated) {
                $results[$userId] = $translated;
            }
        }
        return $results;
    }
    
    /**
     * Get alert message for sending (SMS/Email)
     */
    public function getAlertMessageForChannel($alertId, $userId, $channel = 'sms', $explicitLanguage = null) {
        $alert = $this->getTranslatedAlert($alertId, null, $userId, $explicitLanguage);
        
        if (!$alert) {
            return null;
        }
        
        if ($channel === 'sms') {
            return $alert['title'] . "\n\n" . substr($alert['message'], 0, 140);
        } else {
            return [
                'subject' => $alert['title'],
                'body' => $alert['message']
            ];
        }
    }
}
