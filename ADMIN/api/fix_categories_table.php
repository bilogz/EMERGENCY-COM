<?php
require_once 'db_connect.php';

try {
    // Create alert_categories table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS alert_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fa-tag',
        description TEXT,
        color VARCHAR(20) DEFAULT '#95a5a6',
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table alert_categories checked/created.\n";

    // Check if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM alert_categories");
    if ($stmt->fetchColumn() == 0) {
        // Seed default categories
        $categories = [
            ['Weather Update', 'fa-cloud-showers-heavy', 'Updates on weather conditions', '#3498db', 'active'],
            ['Earthquake', 'fa-globe-americas', 'Seismic activity alerts', '#e67e22', 'active'],
            ['Fire Alert', 'fa-fire', 'Fire incidents', '#e74c3c', 'active'],
            ['Flood Warning', 'fa-water', 'Flood risks and warnings', '#2980b9', 'active'],
            ['General Announcement', 'fa-bullhorn', 'General public announcements', '#95a5a6', 'active']
        ];

        $stmt = $pdo->prepare("INSERT INTO alert_categories (name, icon, description, color, status) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        echo "Seeded default categories.\n";
    } else {
        echo "Table already has data.\n";
    }
    
    // Check for notification_templates table and create if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        severity VARCHAR(20) DEFAULT 'Medium',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table notification_templates checked/created.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
