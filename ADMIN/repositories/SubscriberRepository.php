<?php

/**
 * Subscriber Repository
 * Handles all database operations for subscribers and subscriptions
 *
 * @package ADMIN\Repositories
 */

require_once __DIR__ . '/../api/db_connect.php';

class SubscriberRepository
{
    private $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all active subscribers
     *
     * @return array Array of subscriber records with user info
     */
    public function getAllActive()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT s.user_id, s.channels, s.preferred_language,
                       u.name, u.email, u.phone
                FROM subscriptions s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.status = 'active'
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all active subscribers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subscribers by category
     *
     * @param string $category Category name
     * @return array Array of subscriber records
     */
    public function getByCategory($category)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT s.user_id, s.channels, s.preferred_language,
                       u.name, u.email, u.phone
                FROM subscriptions s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.status = 'active'
                AND (s.categories LIKE ? OR s.categories = 'all')
            ");
            $categoryPattern = "%{$category}%";
            $stmt->execute([$categoryPattern]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting subscribers by category: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get subscribers by multiple categories/recipients
     * Handles 'all' and specific categories, removes duplicates
     *
     * @param array $recipients Array of recipient identifiers ('all' or category names)
     * @return array Array of unique subscriber records
     */
    public function getByRecipients(array $recipients)
    {
        $subscribers = [];

        foreach ($recipients as $recipient) {
            if ($recipient === 'all') {
                $allSubs = $this->getAllActive();
                $subscribers = array_merge($subscribers, $allSubs);
            } else {
                $catSubs = $this->getByCategory($recipient);
                $subscribers = array_merge($subscribers, $catSubs);
            }
        }

        // Remove duplicates based on user_id
        return $this->removeDuplicateSubscribers($subscribers);
    }

    /**
     * Remove duplicate subscribers based on user_id
     *
     * @param array $subscribers Array of subscriber records
     * @return array Array of unique subscribers
     */
    private function removeDuplicateSubscribers(array $subscribers)
    {
        $uniqueSubscribers = [];
        $seenUserIds = [];

        foreach ($subscribers as $sub) {
            $userId = $sub['user_id'] ?? null;
            if ($userId && !in_array($userId, $seenUserIds, true)) {
                $uniqueSubscribers[] = $sub;
                $seenUserIds[] = $userId;
            }
        }

        return $uniqueSubscribers;
    }

    /**
     * Get subscriber by user ID
     *
     * @param int $userId User ID
     * @return array|null Subscriber record or null if not found
     */
    public function getByUserId($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name, u.email, u.phone, u.barangay
                FROM subscriptions s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.user_id = ? AND s.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error getting subscriber by user ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user preferred language
     *
     * @param int $userId User ID
     * @return string Language code (default: 'en')
     */
    public function getUserLanguage($userId)
    {
        // First try subscriptions table
        $subscriber = $this->getByUserId($userId);
        if ($subscriber && !empty($subscriber['preferred_language'])) {
            return $subscriber['preferred_language'];
        }

        // Fallback to user_preferences table
        try {
            $stmt = $this->pdo->prepare("
                SELECT preferred_language 
                FROM user_preferences 
                WHERE user_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $pref = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pref && !empty($pref['preferred_language'])) {
                return $pref['preferred_language'];
            }
        } catch (PDOException $e) {
            error_log("Error getting user language preference: " . $e->getMessage());
        }

        return 'en';
    }
}