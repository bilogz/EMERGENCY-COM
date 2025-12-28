-- Admin Activity Logs Table
CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action performed (e.g., login, logout, send_notification, etc.)',
    description TEXT DEFAULT NULL COMMENT 'Detailed description of the action',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address of the admin',
    user_agent TEXT DEFAULT NULL COMMENT 'Browser/user agent information',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Login Logs Table
CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    login_status VARCHAR(20) NOT NULL COMMENT 'success, failed, blocked',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    logout_at DATETIME DEFAULT NULL,
    session_duration INT DEFAULT NULL COMMENT 'Session duration in seconds',
    INDEX idx_admin_id (admin_id),
    INDEX idx_email (email),
    INDEX idx_login_status (login_status),
    INDEX idx_login_at (login_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






