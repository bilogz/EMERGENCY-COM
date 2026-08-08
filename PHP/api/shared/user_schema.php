<?php
/** Shared users-table compatibility helpers for mobile/web APIs. */

function ensureUsersProfileColumns(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $columns = [
        'phone' => "VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number'",
        'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'Nationality'",
        'district' => "VARCHAR(100) DEFAULT NULL COMMENT 'District name'",
        'barangay' => "VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name'",
        'house_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'House or unit number'",
        'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'",
        'address' => "VARCHAR(500) DEFAULT NULL COMMENT 'Full address'",
        'profile_pic' => "VARCHAR(500) DEFAULT NULL COMMENT 'Profile picture URL'",
        'email_verified' => "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Email verification status'",
        'user_type' => "VARCHAR(50) NOT NULL DEFAULT 'citizen' COMMENT 'User role/type'"
    ];

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM users');
        $existing = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    } catch (Throwable $e) {
        error_log('Unable to inspect users table columns: ' . $e->getMessage());
        return;
    }

    foreach ($columns as $column => $definition) {
        if (in_array($column, $existing, true)) continue;
        try {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `{$column}` {$definition}");
            error_log("Added users.{$column} column");
        } catch (Throwable $e) {
            error_log("Unable to add users.{$column}: " . $e->getMessage());
        }
    }
}
