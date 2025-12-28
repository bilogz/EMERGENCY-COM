-- Enhanced Multilingual Support Schema Updates
-- Adds support for AI translations and admin activity tracking

-- Update alert_translations table to track admin and translation method
ALTER TABLE alert_translations 
ADD COLUMN IF NOT EXISTS translated_by_admin_id INT DEFAULT NULL COMMENT 'Admin who created/updated this translation',
ADD COLUMN IF NOT EXISTS translation_method VARCHAR(20) DEFAULT 'manual' COMMENT 'manual, ai, hybrid',
ADD COLUMN IF NOT EXISTS ai_model VARCHAR(50) DEFAULT NULL COMMENT 'AI model used (e.g., gemini-2.5-flash)',
ADD COLUMN IF NOT EXISTS translation_quality_score DECIMAL(3,2) DEFAULT NULL COMMENT 'Quality score 0-1 if available',
ADD INDEX idx_translated_by (translated_by_admin_id),
ADD INDEX idx_translation_method (translation_method);

-- Create supported_languages table for language management
CREATE TABLE IF NOT EXISTS supported_languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL UNIQUE COMMENT 'ISO 639-1 or custom code (en, fil, ceb, etc.)',
    language_name VARCHAR(100) NOT NULL COMMENT 'Display name (English, Filipino, Cebuano, etc.)',
    native_name VARCHAR(100) DEFAULT NULL COMMENT 'Native name of the language',
    flag_emoji VARCHAR(10) DEFAULT NULL COMMENT 'Flag emoji for display',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether this language is currently supported',
    is_ai_supported TINYINT(1) DEFAULT 1 COMMENT 'Whether AI translation is available',
    priority INT DEFAULT 0 COMMENT 'Display priority (higher = shown first)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default supported languages
INSERT INTO supported_languages (language_code, language_name, native_name, flag_emoji, is_active, is_ai_supported, priority) VALUES
('en', 'English', 'English', '🇺🇸', 1, 1, 100),
('fil', 'Filipino', 'Filipino', '🇵🇭', 1, 1, 90),
('tl', 'Tagalog', 'Tagalog', '🇵🇭', 1, 1, 89),
('ceb', 'Cebuano', 'Cebuano', '🇵🇭', 1, 1, 80),
('ilo', 'Ilocano', 'Iloko', '🇵🇭', 1, 1, 70),
('pam', 'Kapampangan', 'Kapampangan', '🇵🇭', 1, 1, 60),
('bcl', 'Bicolano', 'Bikol', '🇵🇭', 1, 1, 50),
('war', 'Waray', 'Waray', '🇵🇭', 1, 1, 40),
('es', 'Spanish', 'Español', '🇪🇸', 1, 1, 30),
('fr', 'French', 'Français', '🇫🇷', 1, 1, 30),
('de', 'German', 'Deutsch', '🇩🇪', 1, 1, 30),
('it', 'Italian', 'Italiano', '🇮🇹', 1, 1, 30),
('pt', 'Portuguese', 'Português', '🇵🇹', 1, 1, 30),
('zh', 'Chinese', '中文', '🇨🇳', 1, 1, 30),
('ja', 'Japanese', '日本語', '🇯🇵', 1, 1, 30),
('ko', 'Korean', '한국어', '🇰🇷', 1, 1, 30),
('ar', 'Arabic', 'العربية', '🇸🇦', 1, 1, 30),
('hi', 'Hindi', 'हिन्दी', '🇮🇳', 1, 1, 30),
('th', 'Thai', 'ไทย', '🇹🇭', 1, 1, 30),
('vi', 'Vietnamese', 'Tiếng Việt', '🇻🇳', 1, 1, 30),
('id', 'Indonesian', 'Bahasa Indonesia', '🇮🇩', 1, 1, 30),
('ms', 'Malay', 'Bahasa Melayu', '🇲🇾', 1, 1, 30),
('ru', 'Russian', 'Русский', '🇷🇺', 1, 1, 30),
('tr', 'Turkish', 'Türkçe', '🇹🇷', 1, 1, 30)
ON DUPLICATE KEY UPDATE language_name = VALUES(language_name);

-- Create translation_activity_logs table for detailed tracking
CREATE TABLE IF NOT EXISTS translation_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL COMMENT 'Admin who performed the action',
    action_type VARCHAR(50) NOT NULL COMMENT 'create_translation, update_translation, delete_translation, ai_translate, etc.',
    alert_id INT DEFAULT NULL COMMENT 'Related alert ID',
    translation_id INT DEFAULT NULL COMMENT 'Related translation ID',
    source_language VARCHAR(10) DEFAULT NULL,
    target_language VARCHAR(10) DEFAULT NULL,
    translation_method VARCHAR(20) DEFAULT NULL COMMENT 'manual, ai, hybrid',
    success TINYINT(1) DEFAULT 1 COMMENT 'Whether the action succeeded',
    error_message TEXT DEFAULT NULL COMMENT 'Error message if failed',
    metadata JSON DEFAULT NULL COMMENT 'Additional data (e.g., AI response time, quality score)',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_alert_id (alert_id),
    INDEX idx_translation_id (translation_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE SET NULL,
    FOREIGN KEY (translation_id) REFERENCES alert_translations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

