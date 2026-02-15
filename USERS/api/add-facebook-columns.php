<?php
/**
 * Add Facebook OAuth columns to users table
 * Run this script to add facebook_id and google_id columns
 */

require_once 'db_connect.php';

try {
    // Check if facebook_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'facebook_id'");
    $facebookColumnExists = $stmt->fetch();
    
    if (!$facebookColumnExists) {
        // Add facebook_id column
        $pdo->exec("ALTER TABLE users ADD COLUMN facebook_id VARCHAR(255) DEFAULT NULL AFTER password");
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY unique_facebook_id (facebook_id)");
        echo "✓ Added facebook_id column\n";
    } else {
        echo "✓ facebook_id column already exists\n";
    }
    
    // Check if google_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    $googleColumnExists = $stmt->fetch();
    
    if (!$googleColumnExists) {
        // Add google_id column
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER facebook_id");
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY unique_google_id (google_id)");
        echo "✓ Added google_id column\n";
    } else {
        echo "✓ google_id column already exists\n";
    }
    
    // Check if oauth_provider column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'oauth_provider'");
    $oauthProviderExists = $stmt->fetch();
    
    if (!$oauthProviderExists) {
        // Add oauth_provider column to track which OAuth provider was used
        $pdo->exec("ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(20) DEFAULT NULL AFTER google_id");
        echo "✓ Added oauth_provider column\n";
    } else {
        echo "✓ oauth_provider column already exists\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
