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
    
    if ($action === 'list' || $action === 'check-updates') {
        // Get last update timestamp from database
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM supported_languages");
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
            FROM supported_languages
            WHERE is_active = 1
            ORDER BY priority DESC, language_name ASC
        ");
        $languages = $stmt->fetchAll();
        
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
        
        foreach ($languages as $langCode) {
            // Try exact match first
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM supported_languages 
                WHERE language_code = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode]);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = $result;
                break;
            }
            
            // Try prefix match (e.g., 'en' matches 'en-US')
            $stmt = $pdo->prepare("
                SELECT language_code, language_name, native_name, flag_emoji 
                FROM supported_languages 
                WHERE language_code LIKE ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$langCode . '%']);
            $result = $stmt->fetch();
            
            if ($result) {
                $detectedLanguage = $result['language_code'];
                $matchedLanguage = $result;
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

