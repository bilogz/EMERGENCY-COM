<?php
/**
 * Language Management API for Admin
 * Add, update, and manage supported languages
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';

/**
 * Resolve a usable languages table name.
 * Uses legacy table when healthy; otherwise falls back to a new table.
 */
function resolveLanguagesTable(PDO $pdo): string {
    $candidates = ['supported_languages', 'supported_languages_catalog'];
    foreach ($candidates as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$stmt || !$stmt->fetch()) {
                continue;
            }
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            return $table;
        } catch (PDOException $e) {
            // Ignore broken table and try next candidate
        }
    }
    return 'supported_languages_catalog';
}

/**
 * Create supported_languages with expected schema.
 */
function createSupportedLanguagesTable(PDO $pdo, string $tableName): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            language_code VARCHAR(10) NOT NULL,
            language_name VARCHAR(120) NOT NULL,
            native_name VARCHAR(120) DEFAULT NULL,
            flag_emoji VARCHAR(16) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_ai_supported TINYINT(1) NOT NULL DEFAULT 1,
            priority INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_{$tableName}_code (language_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Ensure multilingual table exists and has required columns/data.
 */
function ensureSupportedLanguagesSchema(PDO $pdo, string $tableName): void {
    createSupportedLanguagesTable($pdo, $tableName);

    $existingCols = [];
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
        foreach ($colStmt->fetchAll() as $col) {
            $existingCols[$col['Field']] = true;
        }
    } catch (PDOException $e) {
        // Recover from MySQL metadata corruption: "table doesn't exist in engine" (1932)
        $message = strtolower($e->getMessage());
        if (strpos($message, "doesn't exist in engine") !== false || strpos($message, "error 1932") !== false) {
            $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
            createSupportedLanguagesTable($pdo, $tableName);
            $colStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
            foreach ($colStmt->fetchAll() as $col) {
                $existingCols[$col['Field']] = true;
            }
        } else {
            throw $e;
        }
    }

    if (!isset($existingCols['native_name'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN native_name VARCHAR(120) DEFAULT NULL AFTER language_name");
    }
    if (!isset($existingCols['flag_emoji'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN flag_emoji VARCHAR(16) DEFAULT NULL AFTER native_name");
    }
    if (!isset($existingCols['is_active'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER flag_emoji");
    }
    if (!isset($existingCols['is_ai_supported'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN is_ai_supported TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
    }
    if (!isset($existingCols['priority'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN priority INT NOT NULL DEFAULT 0 AFTER is_ai_supported");
    }
    if (!isset($existingCols['created_at'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER priority");
    }
    if (!isset($existingCols['updated_at'])) {
        $pdo->exec("ALTER TABLE {$tableName} ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    // Seed core languages and PH dialects; preserve existing rows via upsert.
    $seedStmt = $pdo->prepare("
        INSERT INTO {$tableName}
            (language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority)
        VALUES
            (?, ?, ?, ?, 1, 1, ?)
        ON DUPLICATE KEY UPDATE
            language_name = VALUES(language_name),
            native_name = VALUES(native_name),
            is_ai_supported = VALUES(is_ai_supported),
            updated_at = NOW()
    ");
    $seedData = [
        // Core
        ['en', 'English', 'English', '', 100],
        ['fil', 'Filipino', 'Filipino', '', 98],
        ['tl', 'Tagalog', 'Tagalog', '', 96],
        // Major Philippine languages/dialects
        ['ceb', 'Cebuano', 'Cebuano', '', 94],
        ['ilo', 'Ilocano', 'Ilokano', '', 92],
        ['hil', 'Hiligaynon', 'Hiligaynon', '', 90],
        ['war', 'Waray', 'Winaray', '', 88],
        ['bik', 'Bikol', 'Bikol', '', 86],
        ['kap', 'Kapampangan', 'Kapampangan', '', 84],
        ['pan', 'Pangasinan', 'Pangasinan', '', 82],
        ['mrw', 'Maranao', 'Maranao', '', 80],
        ['tsg', 'Tausug', 'Tausug', '', 78],
        ['mag', 'Maguindanaon', 'Maguindanaon', '', 76],
        ['cha', 'Chavacano', 'Chavacano', '', 74],
        ['kin', 'Kinaray-a', 'Kinaray-a', '', 72],
        ['akl', 'Aklanon', 'Aklanon', '', 70],
        ['sur', 'Surigaonon', 'Surigaonon', '', 68],
        ['yka', 'Yakan', 'Yakan', '', 66],
        // Additional global languages
        ['zh', 'Chinese', '中文', '', 60],
        ['es', 'Spanish', 'Español', '', 58],
        ['ja', 'Japanese', '日本語', '', 56],
        ['ko', 'Korean', '한국어', '', 54],
        ['ar', 'Arabic', 'العربية', '', 52],
        ['fr', 'French', 'Français', '', 50],
        ['de', 'German', 'Deutsch', '', 48],
        ['ru', 'Russian', 'Русский', '', 46],
        ['hi', 'Hindi', 'हिन्दी', '', 44],
    ];
    foreach ($seedData as $row) {
        $seedStmt->execute($row);
    }
}

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit();
}

try {
    $languagesTable = resolveLanguagesTable($pdo);
    ensureSupportedLanguagesSchema($pdo, $languagesTable);
} catch (Throwable $e) {
    error_log('Language schema init error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Language setup failed. Please check database permissions.'
    ]);
    exit();
}

$adminId = $_SESSION['admin_user_id'] ?? null;
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    // Add new language
    $input = json_decode(file_get_contents('php://input'), true);
    
    $languageCode = $input['language_code'] ?? '';
    $languageName = $input['language_name'] ?? '';
    $nativeName = $input['native_name'] ?? $languageName;
    $flagEmoji = $input['flag_emoji'] ?? '🌐';
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    $isAISupported = isset($input['is_ai_supported']) ? (int)$input['is_ai_supported'] : 1;
    $priority = isset($input['priority']) ? (int)$input['priority'] : 0;
    
    if (empty($languageCode) || empty($languageName)) {
        echo json_encode(['success' => false, 'message' => 'Language code and name are required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO {$languagesTable} 
            (language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$languageCode, $languageName, $nativeName, $flagEmoji, $isActive, $isAISupported, $priority]);
        
        // Log activity
        logAdminActivity($adminId, 'add_language', "Added language: {$languageName} ({$languageCode})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language added successfully.',
            'language_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo json_encode(['success' => false, 'message' => 'Language code already exists.']);
        } else {
            error_log("Add Language Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'update') {
    // Update language
    $input = json_decode(file_get_contents('php://input'), true);
    $languageId = $input['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($languageId)) {
        echo json_encode(['success' => false, 'message' => 'Language ID is required.']);
        exit;
    }
    
    $updates = [];
    $params = [];
    
    if (isset($input['language_name'])) {
        $updates[] = 'language_name = ?';
        $params[] = $input['language_name'];
    }
    if (isset($input['native_name'])) {
        $updates[] = 'native_name = ?';
        $params[] = $input['native_name'];
    }
    if (isset($input['flag_emoji'])) {
        $updates[] = 'flag_emoji = ?';
        $params[] = $input['flag_emoji'];
    }
    if (isset($input['is_active'])) {
        $updates[] = 'is_active = ?';
        $params[] = (int)$input['is_active'];
    }
    if (isset($input['is_ai_supported'])) {
        $updates[] = 'is_ai_supported = ?';
        $params[] = (int)$input['is_ai_supported'];
    }
    if (isset($input['priority'])) {
        $updates[] = 'priority = ?';
        $params[] = (int)$input['priority'];
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update.']);
        exit;
    }
    
    $updates[] = 'updated_at = NOW()';
    $params[] = $languageId;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE {$languagesTable} 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ");
        
        $stmt->execute($params);
        
        // Log activity
        logAdminActivity($adminId, 'update_language', "Updated language ID: {$languageId}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language updated successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Update Language Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete') {
    // Delete language (soft delete by setting is_active = 0)
    $input = json_decode(file_get_contents('php://input'), true);
    $languageId = $input['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($languageId)) {
        echo json_encode(['success' => false, 'message' => 'Language ID is required.']);
        exit;
    }
    
    try {
        // Get language info before deletion
        $stmt = $pdo->prepare("SELECT language_code, language_name FROM {$languagesTable} WHERE id = ?");
        $stmt->execute([$languageId]);
        $lang = $stmt->fetch();
        
        if (!$lang) {
            echo json_encode(['success' => false, 'message' => 'Language not found.']);
            exit;
        }
        
        // Soft delete (set is_active = 0)
        $stmt = $pdo->prepare("UPDATE {$languagesTable} SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$languageId]);
        
        // Log activity
        logAdminActivity($adminId, 'delete_language', "Deleted language: {$lang['language_name']} ({$lang['language_code']})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Language deactivated successfully.'
        ]);
    } catch (PDOException $e) {
        error_log("Delete Language Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} elseif ($action === 'list') {
    // List all languages
    try {
        $includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';
        
        $query = "
            SELECT id, language_code, language_name, native_name, flag_emoji, 
                   is_active, is_ai_supported, priority, created_at, updated_at
            FROM {$languagesTable}
        ";
        
        if (!$includeInactive) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY priority DESC, language_name ASC";
        
        $stmt = $pdo->query($query);
        $languages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'languages' => $languages,
            'count' => count($languages)
        ]);
    } catch (PDOException $e) {
        error_log("List Languages Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

