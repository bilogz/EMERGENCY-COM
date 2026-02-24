<?php
/**
 * Languages API - Real-time Language Support
 * Provides language list with real-time updates
 */

// Suppress any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

// Determine correct database path
$dbPath = __DIR__ . '/../../ADMIN/api/db_connect.php';
if (!file_exists($dbPath)) {
    // Try alternative path
    $dbPath = __DIR__ . '/../../../ADMIN/api/db_connect.php';
}

if (!file_exists($dbPath)) {
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration not found',
        'languages' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

try {
    require_once $dbPath;
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load database connection',
        'error' => $e->getMessage(),
        'languages' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

function resolveUserLanguagesTable(PDO $pdo): ?string {
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
            // Try next candidate table.
        }
    }
    return null;
}

function isLikelyMojibake(string $value): bool {
    if ($value === '') {
        return false;
    }
    return preg_match('/(?:\\xC3\\x83|\\xC3\\x82|\\xC3\\xA2|\\xC3\\x90|\\xC3\\x91|\\xC3\\xB0\\xC5\\xB8)/', $value) === 1;
}

function defaultFlagEmojiForLanguage(string $languageCode): string {
    return '';
}

function defaultLanguageNameForCode(string $languageCode): string {
    $code = strtolower(trim($languageCode));
    $map = [
        'en' => 'English',
        'es' => 'Spanish',
        'zh' => 'Chinese',
        'hi' => 'Hindi',
        'ar' => 'Arabic',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'de' => 'German',
        'fr' => 'French',
        'fil' => 'Filipino',
        'tl' => 'Tagalog',
        'ceb' => 'Cebuano',
        'ilo' => 'Ilocano',
        'war' => 'Waray',
        'id' => 'Indonesian',
        'ko' => 'Korean',
    ];
    return $map[$code] ?? strtoupper($code);
}

function normalizeLanguageRow(array $row): array {
    $languageCode = strtolower(trim((string)($row['language_code'] ?? 'en')));
    $languageName = trim((string)($row['language_name'] ?? strtoupper($languageCode)));
    $nativeName = trim((string)($row['native_name'] ?? ''));
    $flagEmoji = trim((string)($row['flag_emoji'] ?? ''));

    if ($languageName === '' || isLikelyMojibake($languageName)) {
        $languageName = defaultLanguageNameForCode($languageCode);
    }
    if ($nativeName === '' || isLikelyMojibake($nativeName)) {
        $nativeName = $languageName;
    }
    if ($flagEmoji === '' || isLikelyMojibake($flagEmoji)) {
        $flagEmoji = defaultFlagEmojiForLanguage($languageCode);
    }

    $row['language_code'] = $languageCode;
    $row['language_name'] = $languageName;
    $row['native_name'] = $nativeName;
    $row['flag_emoji'] = $flagEmoji;
    return $row;
}

$languagesTable = null;

$action = $_GET['action'] ?? 'list';
$lastUpdate = $_GET['last_update'] ?? null;

try {
    if ($pdo === null) {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'languages' => []
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }

    $languagesTable = resolveUserLanguagesTable($pdo);
    
    if ($action === 'list' || $action === 'check-updates') {
        if ($languagesTable === null) {
            if (ob_get_level()) {
                ob_clean();
            }
            echo json_encode([
                'success' => true,
                'languages' => [
                    ['language_code' => 'en', 'language_name' => 'English', 'native_name' => 'English', 'flag_emoji' => '', 'is_active' => 1, 'is_ai_supported' => 1, 'priority' => 100],
                    ['language_code' => 'fil', 'language_name' => 'Filipino', 'native_name' => 'Filipino', 'flag_emoji' => '', 'is_active' => 1, 'is_ai_supported' => 1, 'priority' => 98],
                    ['language_code' => 'ceb', 'language_name' => 'Cebuano', 'native_name' => 'Cebuano', 'flag_emoji' => '', 'is_active' => 1, 'is_ai_supported' => 1, 'priority' => 94],
                    ['language_code' => 'ilo', 'language_name' => 'Ilocano', 'native_name' => 'Ilokano', 'flag_emoji' => '', 'is_active' => 1, 'is_ai_supported' => 1, 'priority' => 92],
                    ['language_code' => 'war', 'language_name' => 'Waray', 'native_name' => 'Winaray', 'flag_emoji' => '', 'is_active' => 1, 'is_ai_supported' => 1, 'priority' => 88],
                ],
                'last_update' => null,
                'count' => 5,
                'updated' => true
            ]);
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit;
        }

        // Get last update timestamp from database
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM {$languagesTable}");
        $dbUpdate = $stmt->fetch();
        $dbLastUpdate = $dbUpdate['last_update'] ?? null;
        
        // If checking for updates and no changes, return early
        if ($action === 'check-updates' && $lastUpdate && $dbLastUpdate === $lastUpdate) {
            if (ob_get_level()) {
                ob_clean();
            }
            echo json_encode([
                'success' => true,
                'updated' => false,
                'last_update' => $dbLastUpdate
            ]);
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit;
        }
        
        // Get all active languages from admin-managed database
        // This ensures users/guests see languages that admins have added/configured
        $stmt = $pdo->query("
            SELECT 
                language_code, 
                language_name, 
                native_name, 
                flag_emoji, 
                is_active, 
                is_ai_supported, 
                priority,
                updated_at
            FROM {$languagesTable}
            WHERE is_active = 1
            ORDER BY priority DESC, language_name ASC
        ");
        $languages = $stmt->fetchAll();
        if (is_array($languages)) {
            $languages = array_map('normalizeLanguageRow', $languages);
        }
        
        // Log for debugging (remove in production if needed)
        // error_log("Languages API: Serving " . count($languages) . " active languages to user/guest");
        
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode([
            'success' => true,
            'languages' => $languages,
            'last_update' => $dbLastUpdate,
            'count' => count($languages),
            'updated' => ($action === 'check-updates' && $lastUpdate !== $dbLastUpdate)
        ]);
        
    } elseif ($action === 'detect') {
        // Detect browser/device language
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        $languages = [];
        if (!empty($acceptLanguage)) {
            $parts = explode(',', $acceptLanguage);
            foreach ($parts as $part) {
                $lang = trim(explode(';', $part)[0]);
                $lang = strtolower($lang);
                $langCode = explode('-', $lang)[0];
                if (!in_array($langCode, $languages)) {
                    $languages[] = $langCode;
                }
            }
        }
        
        // Try to match with supported languages
        $detectedLanguage = 'en'; // Default
        $matchedLanguage = null;

        if ($languagesTable === null) {
            if (ob_get_level()) {
                ob_clean();
            }
            echo json_encode([
                'success' => true,
                'detected_language' => $detectedLanguage,
                'matched_language' => null,
                'browser_languages' => $languages,
                'supported' => false
            ]);
            if (ob_get_level()) {
                ob_end_flush();
            }
            exit;
        }
        
        foreach ($languages as $langCode) {
            // Try exact match first
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM {$languagesTable} 
                WHERE language_code = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode]);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = normalizeLanguageRow($result);
                break;
            }
            
            // Try prefix match (e.g., 'en' matches 'en-US')
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM {$languagesTable} 
                WHERE language_code LIKE ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode . '%']);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = normalizeLanguageRow($result);
                break;
            }
        }
        
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode([
            'success' => true,
            'detected_language' => $detectedLanguage,
            'matched_language' => $matchedLanguage,
            'browser_languages' => $languages,
            'supported' => $matchedLanguage !== null
        ]);
        
    } else {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
    // Clean output buffer and flush
    if (ob_get_level()) {
        ob_end_flush();
    }
    
} catch (PDOException $e) {
    error_log("Languages API Error: " . $e->getMessage());
    
    // Return JSON error instead of HTML
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage(),
        'languages' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
} catch (Exception $e) {
    error_log("Languages API Error: " . $e->getMessage());
    
    // Return JSON error instead of HTML
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'languages' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
} catch (Error $e) {
    error_log("Languages API Fatal Error: " . $e->getMessage());
    
    // Return JSON error instead of HTML
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred',
        'error' => $e->getMessage(),
        'languages' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}


