-- ============================================
-- ADD AUTO-TRANSLATION PREFERENCE TO USER_PREFERENCES
-- ============================================
-- This migration adds a field to control whether users want
-- AI auto-translation enabled for their account

-- Add auto_translate_enabled column to user_preferences table
ALTER TABLE user_preferences 
ADD COLUMN auto_translate_enabled TINYINT(1) DEFAULT 1 
COMMENT 'Enable AI auto-translation for non-English/Filipino languages' 
AFTER preferred_language;

-- Update existing records to have auto-translation enabled by default
UPDATE user_preferences 
SET auto_translate_enabled = 1 
WHERE auto_translate_enabled IS NULL;

-- Create index for faster queries
CREATE INDEX idx_auto_translate ON user_preferences(auto_translate_enabled);

-- Verify the change
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'user_preferences' 
AND COLUMN_NAME = 'auto_translate_enabled';

