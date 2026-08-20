<?php
/**
 * User Language Preference API
 * Handles saving and retrieving user language preferences.
 * Production-supported languages: English and Tagalog.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';
require_once '../../ADMIN/api/security-helpers.php';

session_start();

function normalizePreferenceLanguage(?string $language): string {
    $language = strtolower(trim((string)$language));
    if ($language === 'fil' || $language === 'tl') {
        return 'tl';
    }
    return $language === 'en' ? 'en' : '';
}

function resolvePreferenceLanguagesTable(PDO $pdo): ?string {
    $candidates = ['supported_languages', 'supported_languages_catalog', 'emergency_comm_supported_languages'];
    foreach ($candidates as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$stmt || !$stmt->fetch()) {
                continue;
            }
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return $table;
        } catch (PDOException $e) {
            // Try next table.
        }
    }
    return null;
}

$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'set' : 'get');

try {
    if ($pdo === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $languagesTable = resolvePreferenceLanguagesTable($pdo);

    if ($action === 'set') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }

        $language = normalizePreferenceLanguage($input['language'] ?? ($_POST['language'] ?? null));
        $autoTranslate = $input['auto_translate_enabled'] ?? $_POST['auto_translate_enabled'] ?? null;

        if ($language === '' && $autoTranslate === null) {
            echo json_encode(['success' => false, 'message' => 'Language code or auto-translate preference is required']);
            exit;
        }

        if ($language !== '' && $languagesTable !== null) {
            $stmt = $pdo->prepare("SELECT language_code FROM {$languagesTable} WHERE language_code = ? AND is_active = 1 AND language_code IN ('en', 'tl') LIMIT 1");
            $stmt->execute([$language]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Language not supported']);
                exit;
            }
        }

        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            if ($language !== '' && $autoTranslate !== null) {
                $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preferred_language, auto_translate_enabled, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE preferred_language = VALUES(preferred_language), auto_translate_enabled = VALUES(auto_translate_enabled), updated_at = NOW()");
                $stmt->execute([$userId, $language, $autoTranslate ? 1 : 0]);
            } elseif ($language !== '') {
                $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, preferred_language, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE preferred_language = VALUES(preferred_language), updated_at = NOW()");
                $stmt->execute([$userId, $language]);
            } elseif ($autoTranslate !== null) {
                $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, auto_translate_enabled, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE auto_translate_enabled = VALUES(auto_translate_enabled), updated_at = NOW()");
                $stmt->execute([$userId, $autoTranslate ? 1 : 0]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Preferences saved successfully',
                'language' => $language !== '' ? $language : null,
                'auto_translate_enabled' => $autoTranslate,
                'saved_to_account' => true,
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Preferences set (guest mode)',
            'language' => $language !== '' ? $language : null,
            'auto_translate_enabled' => $autoTranslate,
            'saved_to_account' => false,
        ]);
        exit;
    }

    if ($action === 'get') {
        $userId = $_SESSION['user_id'] ?? null;
        $language = 'en';
        $autoTranslate = true;

        if ($userId) {
            $stmt = $pdo->prepare("SELECT preferred_language, auto_translate_enabled FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();

            if ($result) {
                $storedLanguage = normalizePreferenceLanguage($result['preferred_language'] ?? null);
                if ($storedLanguage !== '') {
                    $language = $storedLanguage;
                }
                if (isset($result['auto_translate_enabled'])) {
                    $autoTranslate = (bool)$result['auto_translate_enabled'];
                }
            }
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langCode = strtolower(explode('-', explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])[0]);
            $detected = normalizePreferenceLanguage($langCode);
            if ($detected !== '') {
                $language = $detected;
            }
        }

        echo json_encode([
            'success' => true,
            'language' => $language,
            'auto_translate_enabled' => $autoTranslate,
            'user_id' => $userId,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (PDOException $e) {
    error_log('User Language Preference Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('User Language Preference Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
