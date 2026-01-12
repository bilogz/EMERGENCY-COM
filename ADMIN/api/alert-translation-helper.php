<?php
/**
 * Alert Translation Helper
 * Retrieves translated alerts from database based on user language preferences
 * Only uses existing translations from alert_translations table
 */

require_once 'db_connect.php';

class AlertTranslationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get user's preferred language
     * Checks user_preferences table, users table, guest sessions, or falls back to device language
     */
    public function getUserLanguage($userId) {
        // Handle guest users (guest_* prefix)
        if ($userId && strpos($userId, 'guest_') === 0) {
            // For guests, check session or default to browser language
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
     * Get translated alert from database
     * Returns translated title and message if available, otherwise returns original
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
        
        // Check if translation exists in database
        try {
            $stmt = $this->pdo->prepare("
                SELECT translated_title, translated_content, translation_method
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
                    'method' => $translation['translation_method'] ?? 'manual'
                ];
            }
        } catch (PDOException $e) {
            // Try alternative column names (for backward compatibility)
            try {
                $stmt = $this->pdo->prepare("
                    SELECT title, message, translation_method
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
                        'method' => $translation['translation_method'] ?? 'manual'
                    ];
                }
            } catch (PDOException $e2) {
                error_log("Error fetching translation: " . $e2->getMessage());
            }
        }
        
        // Translation doesn't exist - return original English alert
        return $this->getOriginalAlert($alertId);
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
