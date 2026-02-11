<?php
/**
 * Alert Translation Helper
 * Handles retrieval and auto-generation of alert translations
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
     * Get translated alert from database or generate it if missing
     * @param int $alertId
     * @param string $targetLanguage
     * @param string|null $originalTitle (Optional) Required for auto-generation
     * @param string|null $originalMessage (Optional) Required for auto-generation
     * @return array|null
     */
    public function getTranslatedAlert($alertId, $targetLanguage = null, $originalTitle = null, $originalMessage = null) {
        // If English or no language specified, return original structure (caller must handle original retrieval if needed)
        if (!$targetLanguage || $targetLanguage === 'en') {
            return null;
        }
        
        // 1. Check if translation exists in DB
        try {
            $stmt = $this->pdo->prepare("
                SELECT translated_title, translated_content, status
                FROM alert_translations
                WHERE alert_id = ? AND target_language = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$alertId, $targetLanguage]);
            $translation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($translation) {
                return [
                    'title' => $translation['translated_title'],
                    'message' => $translation['translated_content'],
                    'language' => $targetLanguage,
                    'method' => 'cached'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error fetching translation: " . $e->getMessage());
        }

        // 1.5 If caller did not pass original content, load it from alerts table
        if (!$originalTitle || !$originalMessage) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT title, message, content
                    FROM alerts
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmt->execute([$alertId]);
                $alert = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($alert) {
                    if (!$originalTitle) {
                        $originalTitle = $alert['title'] ?? null;
                    }
                    if (!$originalMessage) {
                        $originalMessage = $alert['message'] ?? ($alert['content'] ?? null);
                    }
                }
            } catch (PDOException $e) {
                error_log("Error loading original alert content: " . $e->getMessage());
            }
        }
        
        // 2. If not found and original content provided, Try AI Translation
        if ($originalTitle && $originalMessage && $this->aiService->isAvailable()) {
            $result = $this->aiService->translateAlert($alertId, $originalTitle, $originalMessage, $targetLanguage);
            
            if ($result['success']) {
                return [
                    'title' => $result['title_translation'],
                    'message' => $result['content_translation'],
                    'language' => $targetLanguage,
                    'method' => 'ai_generated'
                ];
            }
        }
        
        // 3. Fallback: Return null (caller should use original)
        return null;
    }

    /**
     * Pre-generate translations for a batch of languages
     * Useful to call before iterating recipients to ensure cache is warm
     */
    public function preGenerateTranslations($alertId, $title, $message, $languages) {
        $uniqueLanguages = array_unique(array_filter($languages, function($lang) {
            return $lang && $lang !== 'en';
        }));

        foreach ($uniqueLanguages as $lang) {
            $this->getTranslatedAlert($alertId, $lang, $title, $message);
        }
    }
}

