<?php
/**
 * Setup Languages - Adds all major world languages to the database
 * Run this once to populate the supported_languages table
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../ADMIN/api/db_connect.php';

echo "<h1>🌍 Setting Up Languages</h1>";

try {
    // Create the table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS supported_languages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            language_code VARCHAR(10) NOT NULL UNIQUE,
            language_name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100) DEFAULT NULL,
            flag_emoji VARCHAR(10) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            is_ai_supported TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_is_active (is_active),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ Table created/verified</p>";
    
    // All major world languages
    $languages = [
        // Most Common Languages (Priority 100-90)
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
        // Philippine Languages (Priority 90-80)
        ['fil', 'Filipino', 'Filipino', '🇵🇭', 1, 1, 90],
        ['tl', 'Tagalog', 'Tagalog', '🇵🇭', 1, 1, 89],
        ['ceb', 'Cebuano', 'Cebuano', '🇵🇭', 1, 1, 88],
        ['ilo', 'Ilocano', 'Iloko', '🇵🇭', 1, 1, 87],
        ['pam', 'Kapampangan', 'Kapampangan', '🇵🇭', 1, 1, 86],
        ['bcl', 'Bicolano', 'Bikol', '🇵🇭', 1, 1, 85],
        ['war', 'Waray', 'Waray', '🇵🇭', 1, 1, 84],
        ['hil', 'Hiligaynon', 'Ilonggo', '🇵🇭', 1, 1, 83],
        ['pwg', 'Pangasinan', 'Pangasinan', '🇵🇭', 1, 1, 82],
        // Southeast Asian Languages (Priority 80-70)
        ['id', 'Indonesian', 'Bahasa Indonesia', '🇮🇩', 1, 1, 80],
        ['ms', 'Malay', 'Bahasa Melayu', '🇲🇾', 1, 1, 79],
        ['th', 'Thai', 'ไทย', '🇹🇭', 1, 1, 78],
        ['vi', 'Vietnamese', 'Tiếng Việt', '🇻🇳', 1, 1, 77],
        ['my', 'Burmese', 'မြန်မာ', '🇲🇲', 1, 1, 76],
        ['km', 'Khmer', 'ភាសាខ្មែរ', '🇰🇭', 1, 1, 75],
        ['lo', 'Lao', 'ລາວ', '🇱🇦', 1, 1, 74],
        // East Asian Languages (Priority 70-60)
        ['ko', 'Korean', '한국어', '🇰🇷', 1, 1, 70],
        ['zh-TW', 'Traditional Chinese', '繁體中文', '🇹🇼', 1, 1, 69],
        // South Asian Languages (Priority 60-50)
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
        // European Languages (Priority 50-40)
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
        // Middle Eastern Languages (Priority 30-20)
        ['fa', 'Persian', 'فارسی', '🇮🇷', 1, 1, 30],
        ['he', 'Hebrew', 'עברית', '🇮🇱', 1, 1, 29],
        ['ps', 'Pashto', 'پښتو', '🇦🇫', 1, 1, 28],
        ['ku', 'Kurdish', 'Kurdî', '🇮🇶', 1, 1, 27],
        // African Languages (Priority 20-10)
        ['sw', 'Swahili', 'Kiswahili', '🇹🇿', 1, 1, 20],
        ['am', 'Amharic', 'አማርኛ', '🇪🇹', 1, 1, 19],
        ['zu', 'Zulu', 'isiZulu', '🇿🇦', 1, 1, 18],
        ['af', 'Afrikaans', 'Afrikaans', '🇿🇦', 1, 1, 17],
        ['yo', 'Yoruba', 'Yorùbá', '🇳🇬', 1, 1, 16],
        ['ig', 'Igbo', 'Asụsụ Igbo', '🇳🇬', 1, 1, 15],
        ['ha', 'Hausa', 'Hausa', '🇳🇬', 1, 1, 14],
        // Other Major Languages (Priority 10-0)
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
    
    foreach ($languages as $lang) {
        $stmt->execute($lang);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
    }
    
    // Count total languages
    $count = $pdo->query("SELECT COUNT(*) FROM supported_languages WHERE is_active = 1")->fetchColumn();
    
    echo "<p>✅ Languages processed: " . count($languages) . "</p>";
    echo "<p>✅ Total active languages in database: <strong>$count</strong></p>";
    
    echo "<h2>Languages List:</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Code</th><th>Name</th><th>Native</th><th>Flag</th><th>Priority</th></tr>";
    
    $result = $pdo->query("SELECT * FROM supported_languages WHERE is_active = 1 ORDER BY priority DESC LIMIT 30");
    while ($row = $result->fetch()) {
        echo "<tr>";
        echo "<td>{$row['language_code']}</td>";
        echo "<td>{$row['language_name']}</td>";
        echo "<td>{$row['native_name']}</td>";
        echo "<td>{$row['flag_emoji']}</td>";
        echo "<td>{$row['priority']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><em>Showing top 30 languages by priority. Total: $count languages.</em></p>";
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p>You can now <a href='../'>go back to the homepage</a> and try changing languages.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

