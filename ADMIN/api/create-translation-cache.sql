-- Translation Cache Table
-- Stores translated text to avoid repeated API calls

CREATE TABLE IF NOT EXISTS translation_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(32) NOT NULL UNIQUE COMMENT 'MD5 hash of text+source+target',
    source_text TEXT NOT NULL COMMENT 'Original text',
    source_lang VARCHAR(10) NOT NULL COMMENT 'Source language code',
    target_lang VARCHAR(10) NOT NULL COMMENT 'Target language code',
    translated_text TEXT NOT NULL COMMENT 'Translated text',
    translation_method VARCHAR(50) DEFAULT 'api' COMMENT 'Translation method used',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_langs (source_lang, target_lang),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some common translations for Filipino
INSERT INTO translation_cache (cache_key, source_text, source_lang, target_lang, translated_text, translation_method) VALUES
(MD5('QUEZON CITY EMERGENCY COMMUNICATION PORTALen fil'), 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL', 'en', 'fil', 'QUEZON CITY EMERGENCY COMMUNICATION PORTAL', 'manual'),
(MD5('Mission:en fil'), 'Mission:', 'en', 'fil', 'Misyon:', 'manual'),
(MD5('Vision:en fil'), 'Vision:', 'en', 'fil', 'Bisyon:', 'manual')
ON DUPLICATE KEY UPDATE updated_at = NOW();

