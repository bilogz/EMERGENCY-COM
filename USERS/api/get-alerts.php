<?php
/**
 * Get Live Alerts API
 * Returns real-time alerts from the database for Quezon City
 */

// Prevent any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error occurred',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'alerts' => []
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit();
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

// Use admin database connection to ensure we get alerts from the same source
// The alerts table is in the ADMIN database
try {
    if (file_exists(__DIR__ . '/../../ADMIN/api/db_connect.php')) {
        require_once __DIR__ . '/../../ADMIN/api/db_connect.php';
    } else {
        require_once __DIR__ . '/db_connect.php';
    }
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load database connection',
        'error' => $e->getMessage(),
        'alerts' => []
    ]);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit();
}

// Load translation helper if available
$translationHelper = null;
if (file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
    require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
    try {
        $translationHelper = new AlertTranslationHelper($pdo);
    } catch (Exception $e) {
        error_log("Failed to initialize AlertTranslationHelper: " . $e->getMessage());
    }
}

try {
    if ($pdo === null) {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'alerts' => []
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Get query parameters
    $category = $_GET['category'] ?? null;
    $status = $_GET['status'] ?? 'active';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $lastUpdate = $_GET['last_update'] ?? null;
    $timeFilter = $_GET['time_filter'] ?? 'recent'; // recent (24h), older, all
    $severityFilter = $_GET['severity_filter'] ?? null; // emergency_only, warnings_only, null (all)
    
    // Get user area for filtering (if logged in)
    $userArea = null;
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            try {
                $areaStmt = $pdo->prepare("SELECT barangay FROM users WHERE id = ? LIMIT 1");
                $areaStmt->execute([$userId]);
                $user = $areaStmt->fetch();
                if ($user && !empty($user['barangay'])) {
                    $userArea = $user['barangay'];
                }
            } catch (PDOException $e) {
                error_log("Error getting user area: " . $e->getMessage());
            }
        }
    }
    
    // Check if alerts table exists first
    $tableExists = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'alerts'");
        $tableExists = $checkTable->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking alerts table: " . $e->getMessage());
        $tableExists = false;
    }
    
    if (!$tableExists) {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Alerts table does not exist',
            'alerts' => []
        ]);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    
    // Check if alert_categories table exists
    $categoriesTableExists = false;
    try {
        $checkCategoriesTable = $pdo->query("SHOW TABLES LIKE 'alert_categories'");
        $categoriesTableExists = $checkCategoriesTable->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking alert_categories table: " . $e->getMessage());
        $categoriesTableExists = false;
    }
    
    // Check if area and category columns exist in alerts table
    $hasAreaColumn = false;
    $hasCategoryColumn = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'area'");
        $hasAreaColumn = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking area column: " . $e->getMessage());
        $hasAreaColumn = false;
    }
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'category'");
        $hasCategoryColumn = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking category column: " . $e->getMessage());
        $hasCategoryColumn = false;
    }
    
    // Build query - prioritize recent alerts
    $query = "
        SELECT 
            a.id,
            a.title,
            a.message,
            a.content,
            a.status,
            a.created_at,
            a.updated_at,
            COALESCE(ac.name, 'General') as category_name,
            COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
            COALESCE(ac.color, '#95a5a6') as category_color";
    
    // Add area and category if columns exist
    if ($hasAreaColumn || $hasCategoryColumn) {
        $addFields = [];
        if ($hasAreaColumn) {
            $addFields[] = "a.area";
        }
        if ($hasCategoryColumn) {
            $addFields[] = "a.category";
        }
        if (!empty($addFields)) {
            $query .= ", " . implode(", ", $addFields);
        }
    }
    
    $query .= "
        FROM alerts a";
    
    // Only join alert_categories if the table exists
    if ($categoriesTableExists) {
        $query .= " LEFT JOIN alert_categories ac ON a.category_id = ac.id";
    } else {
        // Create a dummy alias for compatibility
        $query .= " LEFT JOIN (SELECT NULL as id, NULL as name, NULL as icon, NULL as color) ac ON 1=0";
    }
    
    $query .= " WHERE a.status = :status";
    
    $params = [':status' => $status];
    
    // Filter by area if user is logged in and area column exists
    if ($userArea && $hasAreaColumn) {
        // Show alerts for user's area OR alerts with NULL area (city-wide)
        $query .= " AND (a.area = :user_area OR a.area IS NULL OR a.area = '')";
        $params[':user_area'] = $userArea;
    }
    
    // Filter by category if provided and categories table exists
    if ($category && $category !== 'all' && $categoriesTableExists) {
        $query .= " AND (ac.name = :category OR (:category = 'General' AND ac.name IS NULL))";
        $params[':category'] = $category;
    }
    
    // Time-based filtering (default: last 24 hours for initial load, all for incremental updates)
    if ($timeFilter === 'recent' && $lastId == 0) {
        // Default to last 24 hours for initial load
        $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    } elseif ($timeFilter === 'older' && $lastId == 0) {
        // Show alerts older than 24 hours
        $query .= " AND a.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    }
    // 'all' or incremental updates (lastId > 0) shows all alerts
    
    // Severity filtering based on category field (if exists) or category name
    // Also check for [EXTREME] in title for emergency alerts
    if ($severityFilter === 'emergency_only') {
        if ($hasCategoryColumn) {
            $query .= " AND (a.category = 'Emergency Alert' OR a.title LIKE '%[EXTREME]%' OR a.title LIKE '%EXTREME%')";
        } elseif ($categoriesTableExists) {
            // Fallback: filter by category names that are typically emergency-related OR title contains EXTREME
            $query .= " AND (ac.name IN ('Earthquake', 'Bomb Threat', 'Fire') OR a.title LIKE '%[EXTREME]%' OR a.title LIKE '%EXTREME%')";
        } else {
            // If no category info available, just check title for EXTREME
            $query .= " AND (a.title LIKE '%[EXTREME]%' OR a.title LIKE '%EXTREME%')";
        }
    } elseif ($severityFilter === 'warnings_only') {
        if ($hasCategoryColumn) {
            $query .= " AND a.category = 'Warning'";
        } elseif ($categoriesTableExists) {
            // Fallback: filter by category names that are typically warning-related
            $query .= " AND ac.name IN ('Weather', 'General')";
        }
        // If no category info available, severity filter is skipped
    }
    
    // Get only new alerts if lastId is provided (for incremental updates)
    if ($lastId > 0) {
        $query .= " AND a.id > :last_id";
        $params[':last_id'] = $lastId;
    }
    
    // Alternative: check by updated_at timestamp for more reliable real-time updates
    if ($lastUpdate && $lastId == 0) {
        // Convert ISO 8601 timestamp to MySQL datetime format
        $lastUpdateTime = date('Y-m-d H:i:s', strtotime($lastUpdate));
        $query .= " AND a.updated_at > :last_update";
        $params[':last_update'] = $lastUpdateTime;
    }
    
    $query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT :limit";
    
    try {
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error executing alerts query: " . $e->getMessage());
        error_log("Query: " . $query);
        error_log("Params: " . print_r($params, true));
        
        // Set alerts to empty array and continue - we'll return empty alerts
        $alerts = [];
        
        // Log the error but continue processing to return a proper response
    }
    
    // Resolve language using priority order:
    // 1. Logged-in user's saved language preference (database)
    // 2. Global language selector (UI language icon - query parameter)
    // 3. Guest browser language detection
    // 4. System default language (English)
    $targetLanguage = resolveAlertLanguage($pdo);
    
    // Translate alerts if language is specified and translation helper is available
    $translationApplied = false;
    $translationAttempted = 0;
    $translationSuccess = 0;
    $aiServiceAvailable = false;
    
    // Check if AI service is available (for debugging)
    if ($translationHelper) {
        try {
            $reflection = new ReflectionClass($translationHelper);
            $aiServiceProperty = $reflection->getProperty('aiService');
            $aiServiceProperty->setAccessible(true);
            $aiService = $aiServiceProperty->getValue($translationHelper);
            if ($aiService) {
                $aiServiceAvailable = $aiService->isAvailable();
            }
        } catch (Exception $e) {
            // Ignore reflection errors
        }
    }
    
    // Debug: Log why translation might not be attempted
    $alertsCount = is_array($alerts) ? count($alerts) : 0;
    if ($targetLanguage && $targetLanguage !== 'en' && $alertsCount > 0) {
        if (!$translationHelper) {
            error_log("Translation skipped: translationHelper is null (file may not exist or initialization failed)");
        }
    }
    
    // Check condition: targetLanguage must be set and not 'en', translationHelper must exist, and alerts must not be empty
    if ($targetLanguage && $targetLanguage !== 'en' && $translationHelper && $alertsCount > 0) {
        foreach ($alerts as &$alert) {
            try {
                $translationAttempted++;
                $translated = $translationHelper->getTranslatedAlert($alert['id'], $targetLanguage, null, $targetLanguage);
                if ($translated && isset($translated['title'])) {
                    // Apply translation if language field indicates it's translated (not 'en')
                    // getTranslatedAlert returns original with language='en' on failure, translated with language=targetLanguage on success
                    $returnedLanguage = $translated['language'] ?? 'unknown';
                    $returnedMethod = $translated['method'] ?? 'unknown';
                    $isTranslated = isset($translated['language']) && $translated['language'] !== 'en';
                    
                    // Log translation attempt for debugging
                    error_log("Alert {$alert['id']} translation check: language={$returnedLanguage}, method={$returnedMethod}, isTranslated=" . ($isTranslated ? 'yes' : 'no'));
                    
                    if ($isTranslated) {
                        $alert['title'] = $translated['title'];
                        if (isset($translated['message']) && !empty($translated['message'])) {
                            $alert['message'] = $translated['message'];
                            // Use translated message for content field if no separate content translation
                            if (isset($translated['content']) && !empty($translated['content'])) {
                                $alert['content'] = $translated['content'];
                            } else {
                                $alert['content'] = $translated['message'];
                            }
                        }
                        $translationSuccess++;
                        $translationApplied = true;
                    } else {
                        // Log why translation wasn't applied
                        error_log("Alert {$alert['id']} translation NOT applied: returned language was '{$returnedLanguage}' (expected '{$targetLanguage}')");
                    }
                } else {
                    error_log("Alert {$alert['id']} translation failed: getTranslatedAlert returned null or missing title");
                }
            } catch (Exception $e) {
                // If translation fails, use original alert (silently fail to avoid breaking the API)
                error_log("Translation error for alert {$alert['id']} (lang: {$targetLanguage}): " . $e->getMessage());
            }
        }
        unset($alert); // Break reference
        
        // Log translation stats for debugging
        if ($translationAttempted > 0) {
            error_log("Translation attempt: {$translationAttempted} alerts, {$translationSuccess} translated for language: {$targetLanguage}");
        }
    }
    
    // Format timestamps
    foreach ($alerts as &$alert) {
        $alert['timestamp'] = $alert['created_at'];
        $alert['time_ago'] = getTimeAgo($alert['created_at']);
    }
    unset($alert); // Break reference
    
    // Calculate final counts after all processing
    $finalAlertsCount = is_array($alerts) ? count($alerts) : 0;
    
    // Enhanced debug info to help diagnose issues
    $debugInfo = [
        'target_language' => $targetLanguage ?? 'en',
        'alerts_count' => $finalAlertsCount,
        'translation_attempted' => $translationAttempted ?? 0,
        'translation_success' => $translationSuccess ?? 0,
        'ai_service_available' => $aiServiceAvailable,
        'translation_helper_available' => $translationHelper !== null,
        'translation_applied' => $translationApplied,
        'translation_condition_met' => ($targetLanguage && $targetLanguage !== 'en' && $translationHelper && $finalAlertsCount > 0)
    ];
    
    // Log debug info for server-side debugging
    if ($targetLanguage && $targetLanguage !== 'en') {
        error_log("Translation Debug - Language: {$targetLanguage}, Alerts: {$finalAlertsCount}, Helper: " . ($translationHelper ? 'yes' : 'no') . ", Attempted: {$translationAttempted}, Success: {$translationSuccess}");
    }
    
    // Ensure clean output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'count' => $finalAlertsCount,
        'timestamp' => date('c'),
        'language' => $targetLanguage ?? 'en',
        'translation_applied' => $translationApplied,
        'translation_helper_available' => $translationHelper !== null,
        'debug' => $debugInfo
    ], JSON_UNESCAPED_UNICODE);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
    
} catch (PDOException $e) {
    error_log("Get Alerts API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage(),
        'alerts' => []
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
} catch (Exception $e) {
    error_log("Get Alerts API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'alerts' => []
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
} catch (Error $e) {
    error_log("Get Alerts API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred',
        'error' => $e->getMessage(),
        'alerts' => []
    ]);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
}

/**
 * Resolve alert display language using priority order:
 * 1. Global language selector (UI language icon - query parameter from localStorage)
 *    - Takes precedence as it represents the user's CURRENT session selection
 * 2. Logged-in user's saved language preference (database - persistent preference)
 * 3. Guest browser language detection (Accept-Language header)
 * 4. System default language (English)
 * 
 * Note: Query parameter (UI selector) is checked FIRST to respect immediate user selections.
 * When a user changes language via the UI, the query parameter reflects their current choice
 * and should override the database preference for that request.
 * 
 * @param PDO $pdo Database connection
 * @return string Language code (e.g., 'en', 'fil', 'es')
 */
function resolveAlertLanguage($pdo) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    $targetLanguage = null;
    $userId = null;
    $isLoggedIn = false;
    
    // Check if user is logged in
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $isLoggedIn = true;
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    // Priority 2: Global language selector (UI language icon - query parameter from localStorage)
    // This represents the user's CURRENT session selection and takes precedence when explicitly provided
    // Check this FIRST to respect immediate user selections via the UI
    $queryLang = $_GET['lang'] ?? $_GET['language'] ?? null;
    if ($queryLang && strlen($queryLang) >= 2) {
        // Validate language code is reasonable (2-5 characters, alphanumeric with optional dash)
        if (preg_match('/^[a-z]{2}(-[a-z]{2,3})?$/i', $queryLang)) {
            $targetLanguage = strtolower($queryLang);
            // Query parameter (UI selector) takes precedence - return immediately
            return $targetLanguage;
        }
    }
    
    // Priority 1: Logged-in user's saved language preference (database)
    // Only checked if no query parameter was provided (user hasn't explicitly selected via UI)
    if ($isLoggedIn && $userId) {
        try {
            // Try user_preferences table first
            $prefStmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
            $prefStmt->execute([$userId]);
            $pref = $prefStmt->fetch();
            
            if ($pref && !empty($pref['preferred_language'])) {
                $targetLanguage = $pref['preferred_language'];
            } else {
                // Fallback to users table for backward compatibility
                $userStmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ? LIMIT 1");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch();
                if ($user && !empty($user['preferred_language'])) {
                    $targetLanguage = $user['preferred_language'];
                }
            }
        } catch (PDOException $e) {
            error_log("Error getting user language preference: " . $e->getMessage());
        }
    }
    
    // Priority 3: Guest browser language detection (Accept-Language header)
    if (!$targetLanguage) {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($acceptLanguage) {
            // Parse Accept-Language header (e.g., "en-US,en;q=0.9,es;q=0.8")
            $languages = [];
            preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/i', $acceptLanguage, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $lang) {
                    $quality = isset($matches[2][$index]) ? (float)$matches[2][$index] : 1.0;
                    $langCode = strtolower(explode('-', $lang)[0]); // Get base language code
                    $languages[$langCode] = $quality;
                }
                
                // Sort by quality (highest first)
                arsort($languages);
                
                // Use the highest quality language
                $detectedLang = array_key_first($languages);
                if ($detectedLang && strlen($detectedLang) === 2) {
                    // Map common browser languages to supported codes
                    $langMap = [
                        'en' => 'en', 'fil' => 'fil', 'tl' => 'fil',
                        'es' => 'es', 'fr' => 'fr', 'de' => 'de',
                        'it' => 'it', 'pt' => 'pt', 'zh' => 'zh',
                        'ja' => 'ja', 'ko' => 'ko', 'ar' => 'ar',
                        'hi' => 'hi', 'th' => 'th', 'vi' => 'vi',
                        'id' => 'id', 'ms' => 'ms', 'ru' => 'ru'
                    ];
                    $targetLanguage = $langMap[$detectedLang] ?? $detectedLang;
                }
            }
        }
    }
    
    // Priority 4: System default language (English)
    if (!$targetLanguage || $targetLanguage === 'en') {
        $targetLanguage = 'en';
    }
    
    return $targetLanguage;
}

/**
 * Calculate time ago string
 */
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>

