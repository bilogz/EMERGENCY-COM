<?php
/**
 * Simple Setup Script for Enhanced Multilingual Support
 * Creates tables and columns step by step with proper error handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Multilingual Setup</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #2196f3; }
    .warning { color: #ff9800; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 30px; }
</style></head><body>";
echo "<h1>🌍 Enhanced Multilingual Support Setup</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "<span class='error'>✗ Database connection failed!</span>\n";
    echo "</pre></body></html>";
    exit;
}

echo "<span class='success'>✓ Database connection successful!</span>\n\n";

// Step 1: Add columns to alert_translations
echo "<h2>Step 1: Updating alert_translations table</h2>\n";

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '{$table}' 
            AND COLUMN_NAME = '{$column}'
        ");
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    if (!columnExists($pdo, $table, $column)) {
        try {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$definition}");
            return true;
        } catch (PDOException $e) {
            echo "<span class='error'>✗ Error adding column {$column}: " . $e->getMessage() . "</span>\n";
            return false;
        }
    }
    return false;
}

if (addColumnIfNotExists($pdo, 'alert_translations', 'translated_by_admin_id', 
    "translated_by_admin_id INT DEFAULT NULL COMMENT 'Admin who created/updated this translation'")) {
    echo "<span class='success'>✓ Added column 'translated_by_admin_id'</span>\n";
} else {
    echo "<span class='info'>ℹ Column 'translated_by_admin_id' already exists</span>\n";
}

if (addColumnIfNotExists($pdo, 'alert_translations', 'translation_method', 
    "translation_method VARCHAR(20) DEFAULT 'manual' COMMENT 'manual, ai, hybrid'")) {
    echo "<span class='success'>✓ Added column 'translation_method'</span>\n";
} else {
    echo "<span class='info'>ℹ Column 'translation_method' already exists</span>\n";
}

// Add indexes
try {
    $pdo->exec("CREATE INDEX idx_translated_by ON alert_translations(translated_by_admin_id)");
    echo "<span class='success'>✓ Added index 'idx_translated_by'</span>\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
        echo "<span class='info'>ℹ Index 'idx_translated_by' already exists</span>\n";
    }
}

try {
    $pdo->exec("CREATE INDEX idx_translation_method ON alert_translations(translation_method)");
    echo "<span class='success'>✓ Added index 'idx_translation_method'</span>\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
        echo "<span class='info'>ℹ Index 'idx_translation_method' already exists</span>\n";
    }
}

// Step 2: Create supported_languages table
echo "\n<h2>Step 2: Creating supported_languages table</h2>\n";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS supported_languages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            language_code VARCHAR(10) NOT NULL UNIQUE COMMENT 'ISO 639-1 or custom code',
            language_name VARCHAR(100) NOT NULL COMMENT 'Display name',
            native_name VARCHAR(100) DEFAULT NULL COMMENT 'Native name',
            flag_emoji VARCHAR(10) DEFAULT NULL COMMENT 'Flag emoji',
            is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether active',
            is_ai_supported TINYINT(1) DEFAULT 1 COMMENT 'AI translation available',
            priority INT DEFAULT 0 COMMENT 'Display priority',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_is_active (is_active),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<span class='success'>✓ Created supported_languages table</span>\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<span class='info'>ℹ Table already exists</span>\n";
    } else {
        echo "<span class='error'>✗ Error: " . $e->getMessage() . "</span>\n";
    }
}

// Step 3: Insert languages
echo "\n<h2>Step 3: Inserting 80+ languages</h2>\n";

$languages = [
    ['en', 'English', 'English', '🇺🇸', 1, 1, 100],
    ['es', 'Spanish', 'Español', '🇪🇸', 1, 1, 99],
    ['zh', 'Chinese', '中文', '🇨🇳', 1, 1, 98],
    ['hi', 'Hindi', 'हिन्दी', '🇮🇳', 1, 1, 97],
    ['ar', 'Arabic', 'العربية', '🇸🇦', 1, 1, 96],
    ['pt', 'Portuguese', 'Português', '🇵🇹', 1, 1, 95],
    ['ru', 'Russian', 'Русский', '🇷🇺', 1, 1, 94],
    ['ja', 'Japanese', '日本語', '🇯🇵', 1, 1, 93],
    ['de', 'German', 'Deutsch', '🇩🇪', 1, 1, 92],
    ['fr', 'French', 'Français', '🇫🇷', 1, 1, 91],
    ['fil', 'Filipino', 'Filipino', '🇵🇭', 1, 1, 90],
    ['tl', 'Tagalog', 'Tagalog', '🇵🇭', 1, 1, 89],
    ['ceb', 'Cebuano', 'Cebuano', '🇵🇭', 1, 1, 88],
    ['ilo', 'Ilocano', 'Iloko', '🇵🇭', 1, 1, 87],
    ['pam', 'Kapampangan', 'Kapampangan', '🇵🇭', 1, 1, 86],
    ['bcl', 'Bicolano', 'Bikol', '🇵🇭', 1, 1, 85],
    ['war', 'Waray', 'Waray', '🇵🇭', 1, 1, 84],
    ['hil', 'Hiligaynon', 'Ilonggo', '🇵🇭', 1, 1, 83],
    ['pwg', 'Pangasinan', 'Pangasinan', '🇵🇭', 1, 1, 82],
    ['id', 'Indonesian', 'Bahasa Indonesia', '🇮🇩', 1, 1, 80],
    ['ms', 'Malay', 'Bahasa Melayu', '🇲🇾', 1, 1, 79],
    ['th', 'Thai', 'ไทย', '🇹🇭', 1, 1, 78],
    ['vi', 'Vietnamese', 'Tiếng Việt', '🇻🇳', 1, 1, 77],
    ['my', 'Burmese', 'မြန်မာ', '🇲🇲', 1, 1, 76],
    ['km', 'Khmer', 'ភាសាខ្មែរ', '🇰🇭', 1, 1, 75],
    ['lo', 'Lao', 'ລາວ', '🇱🇦', 1, 1, 74],
    ['ko', 'Korean', '한국어', '🇰🇷', 1, 1, 70],
    ['zh-TW', 'Traditional Chinese', '繁體中文', '🇹🇼', 1, 1, 69],
    ['bn', 'Bengali', 'বাংলা', '🇧🇩', 1, 1, 60],
    ['ur', 'Urdu', 'اردو', '🇵🇰', 1, 1, 59],
    ['ta', 'Tamil', 'தமிழ்', '🇮🇳', 1, 1, 58],
    ['te', 'Telugu', 'తెలుగు', '🇮🇳', 1, 1, 57],
    ['mr', 'Marathi', 'मराठी', '🇮🇳', 1, 1, 56],
    ['gu', 'Gujarati', 'ગુજરાતી', '🇮🇳', 1, 1, 55],
    ['kn', 'Kannada', 'ಕನ್ನಡ', '🇮🇳', 1, 1, 54],
    ['ml', 'Malayalam', 'മലയാളം', '🇮🇳', 1, 1, 53],
    ['si', 'Sinhala', 'සිංහල', '🇱🇰', 1, 1, 52],
    ['ne', 'Nepali', 'नेपाली', '🇳🇵', 1, 1, 51],
    ['it', 'Italian', 'Italiano', '🇮🇹', 1, 1, 50],
    ['tr', 'Turkish', 'Türkçe', '🇹🇷', 1, 1, 49],
    ['pl', 'Polish', 'Polski', '🇵🇱', 1, 1, 48],
    ['uk', 'Ukrainian', 'Українська', '🇺🇦', 1, 1, 47],
    ['ro', 'Romanian', 'Română', '🇷🇴', 1, 1, 46],
    ['nl', 'Dutch', 'Nederlands', '🇳🇱', 1, 1, 45],
    ['el', 'Greek', 'Ελληνικά', '🇬🇷', 1, 1, 44],
    ['cs', 'Czech', 'Čeština', '🇨🇿', 1, 1, 43],
    ['sv', 'Swedish', 'Svenska', '🇸🇪', 1, 1, 42],
    ['hu', 'Hungarian', 'Magyar', '🇭🇺', 1, 1, 41],
    ['fi', 'Finnish', 'Suomi', '🇫🇮', 1, 1, 40],
    ['da', 'Danish', 'Dansk', '🇩🇰', 1, 1, 39],
    ['no', 'Norwegian', 'Norsk', '🇳🇴', 1, 1, 38],
    ['bg', 'Bulgarian', 'Български', '🇧🇬', 1, 1, 37],
    ['hr', 'Croatian', 'Hrvatski', '🇭🇷', 1, 1, 36],
    ['sk', 'Slovak', 'Slovenčina', '🇸🇰', 1, 1, 35],
    ['sr', 'Serbian', 'Српски', '🇷🇸', 1, 1, 34],
    ['sl', 'Slovenian', 'Slovenščina', '🇸🇮', 1, 1, 33],
    ['lt', 'Lithuanian', 'Lietuvių', '🇱🇹', 1, 1, 32],
    ['lv', 'Latvian', 'Latviešu', '🇱🇻', 1, 1, 31],
    ['et', 'Estonian', 'Eesti', '🇪🇪', 1, 1, 30],
    ['fa', 'Persian', 'فارسی', '🇮🇷', 1, 1, 30],
    ['he', 'Hebrew', 'עברית', '🇮🇱', 1, 1, 29],
    ['ps', 'Pashto', 'پښتو', '🇦🇫', 1, 1, 28],
    ['ku', 'Kurdish', 'Kurdî', '🇮🇶', 1, 1, 27],
    ['sw', 'Swahili', 'Kiswahili', '🇹🇿', 1, 1, 20],
    ['am', 'Amharic', 'አማርኛ', '🇪🇹', 1, 1, 19],
    ['zu', 'Zulu', 'isiZulu', '🇿🇦', 1, 1, 18],
    ['af', 'Afrikaans', 'Afrikaans', '🇿🇦', 1, 1, 17],
    ['yo', 'Yoruba', 'Yorùbá', '🇳🇬', 1, 1, 16],
    ['ig', 'Igbo', 'Asụsụ Igbo', '🇳🇬', 1, 1, 15],
    ['ha', 'Hausa', 'Hausa', '🇳🇬', 1, 1, 14],
    ['az', 'Azerbaijani', 'Azərbaycan', '🇦🇿', 1, 1, 10],
    ['be', 'Belarusian', 'Беларуская', '🇧🇾', 1, 1, 9],
    ['ca', 'Catalan', 'Català', '🇪🇸', 1, 1, 8],
    ['eu', 'Basque', 'Euskara', '🇪🇸', 1, 1, 7],
    ['ga', 'Irish', 'Gaeilge', '🇮🇪', 1, 1, 6],
    ['is', 'Icelandic', 'Íslenska', '🇮🇸', 1, 1, 5],
    ['mt', 'Maltese', 'Malti', '🇲🇹', 1, 1, 4],
    ['mk', 'Macedonian', 'Македонски', '🇲🇰', 1, 1, 3],
    ['sq', 'Albanian', 'Shqip', '🇦🇱', 1, 1, 2],
    ['bs', 'Bosnian', 'Bosanski', '🇧🇦', 1, 1, 1]
];

$stmt = $pdo->prepare("
    INSERT INTO supported_languages 
    (language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        language_name = VALUES(language_name),
        native_name = VALUES(native_name),
        flag_emoji = VALUES(flag_emoji),
        is_active = VALUES(is_active),
        is_ai_supported = VALUES(is_ai_supported),
        priority = VALUES(priority),
        updated_at = CURRENT_TIMESTAMP
");

$inserted = 0;
$updated = 0;
$errors = 0;

foreach ($languages as $lang) {
    try {
        $stmt->execute($lang);
        if ($pdo->lastInsertId()) {
            $inserted++;
        } else {
            $updated++;
        }
    } catch (PDOException $e) {
        $errors++;
        if ($errors <= 3) {
            echo "<span class='error'>✗ Error inserting {$lang[1]}: " . substr($e->getMessage(), 0, 60) . "</span>\n";
        }
    }
}

echo "<span class='success'>✓ Inserted {$inserted} new languages</span>\n";
if ($updated > 0) {
    echo "<span class='info'>ℹ Updated {$updated} existing languages</span>\n";
}
if ($errors > 0) {
    echo "<span class='warning'>⚠ {$errors} errors (may be duplicates)</span>\n";
}

// Step 4: Create translation_activity_logs table
echo "\n<h2>Step 4: Creating translation_activity_logs table</h2>\n";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS translation_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL COMMENT 'Admin who performed action',
            action_type VARCHAR(50) NOT NULL COMMENT 'Action type',
            alert_id INT DEFAULT NULL COMMENT 'Related alert ID',
            translation_id INT DEFAULT NULL COMMENT 'Related translation ID',
            source_language VARCHAR(10) DEFAULT NULL,
            target_language VARCHAR(10) DEFAULT NULL,
            translation_method VARCHAR(20) DEFAULT NULL COMMENT 'manual, ai, hybrid',
            success TINYINT(1) DEFAULT 1 COMMENT 'Success status',
            error_message TEXT DEFAULT NULL COMMENT 'Error if failed',
            metadata JSON DEFAULT NULL COMMENT 'Additional data',
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_action_type (action_type),
            INDEX idx_alert_id (alert_id),
            INDEX idx_translation_id (translation_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<span class='success'>✓ Created translation_activity_logs table</span>\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<span class='info'>ℹ Table already exists</span>\n";
    } else {
        echo "<span class='error'>✗ Error: " . $e->getMessage() . "</span>\n";
    }
}

// Final verification
echo "\n<h2>Step 5: Final Verification</h2>\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM supported_languages WHERE is_active = 1");
    $result = $stmt->fetch();
    $activeLanguages = $result['count'] ?? 0;
    
    echo "<span class='success'>✓ Found {$activeLanguages} active languages in database</span>\n";
    
    // Verify tables
    $tables = [
        'alert_translations' => 'Alert translations',
        'supported_languages' => 'Supported languages',
        'translation_activity_logs' => 'Translation activity logs'
    ];
    
    foreach ($tables as $table => $name) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✓ {$name} table exists</span>\n";
        } else {
            echo "<span class='error'>✗ {$name} table missing</span>\n";
        }
    }
    
    // Verify columns
    if (columnExists($pdo, 'alert_translations', 'translated_by_admin_id')) {
        echo "<span class='success'>✓ Column 'translated_by_admin_id' exists</span>\n";
    } else {
        echo "<span class='error'>✗ Column 'translated_by_admin_id' missing</span>\n";
    }
    
    if (columnExists($pdo, 'alert_translations', 'translation_method')) {
        echo "<span class='success'>✓ Column 'translation_method' exists</span>\n";
    } else {
        echo "<span class='error'>✗ Column 'translation_method' missing</span>\n";
    }
    
} catch (PDOException $e) {
    echo "<span class='error'>✗ Verification error: " . $e->getMessage() . "</span>\n";
}

echo "\n<h2>✅ Setup Complete!</h2>\n";
echo "\n<span class='success'>The enhanced multilingual support system is now set up.</span>\n";
echo "\nNext steps:\n";
echo "1. Test the language selector on user pages (globe icon top-right)\n";
echo "2. Go to Profile → Language Settings to manage preferences\n";
echo "3. Admin → Language Management to add/edit languages\n";
echo "4. Languages update in real-time when added by admins\n";

echo "</pre></body></html>";
?>

