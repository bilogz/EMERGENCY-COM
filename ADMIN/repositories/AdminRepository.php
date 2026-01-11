<?php

/**
 * Admin Repository
 * Handles all database operations for admin users
 *
 * @package ADMIN\Repositories
 */

require_once __DIR__ . '/../api/db_connect.php';

class AdminRepository
{
    private $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if admin_user table exists
     *
     * @return bool True if table exists, false otherwise
     */
    public function usesAdminUserTable()
    {
        try {
            $this->pdo->query("SELECT 1 FROM admin_user LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get admin by ID
     * Supports both admin_user and users tables for backward compatibility
     *
     * @param int $adminId Admin user ID
     * @return array|null Admin data or null if not found
     */
    public function getById($adminId)
    {
        $useAdminUserTable = $this->usesAdminUserTable();
        
        if ($useAdminUserTable) {
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, name, username, email, phone, role, status, created_at, updated_at, last_login
                FROM admin_user
                WHERE id = ?
            ");
        } else {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, phone, status, user_type, created_at, updated_at
                FROM users
                WHERE id = ? AND user_type = 'admin'
            ");
        }
        
        $stmt->execute([$adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get admin name and email by ID (for header/profile display)
     * Lightweight query for UI components
     *
     * @param int $adminId Admin user ID
     * @return array|null Array with 'name' and 'email' keys, or null if not found
     */
    public function getNameAndEmailById($adminId)
    {
        $useAdminUserTable = $this->usesAdminUserTable();
        
        if ($useAdminUserTable) {
            $stmt = $this->pdo->prepare("SELECT name, email FROM admin_user WHERE id = ?");
        } else {
            $stmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ? AND user_type = 'admin'");
        }
        
        $stmt->execute([$adminId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get current login session info
     *
     * @param int $loginLogId Login log ID
     * @param int $adminId Admin user ID
     * @return array|null Login session data or null if not found
     */
    public function getCurrentLoginInfo($loginLogId, $adminId)
    {
        if (!$loginLogId) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT login_at, ip_address, user_agent
                FROM admin_login_logs
                WHERE id = ? AND admin_id = ?
            ");
            $stmt->execute([$loginLogId, $adminId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error getting current login info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get last login info for admin
     *
     * @param int $adminId Admin user ID
     * @return array|null Last login data or null if not found
     */
    public function getLastLoginInfo($adminId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT login_at, ip_address, logout_at, session_duration
                FROM admin_login_logs
                WHERE admin_id = ? AND login_status = 'success'
                ORDER BY login_at DESC
                LIMIT 1, 1
            ");
            $stmt->execute([$adminId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error getting last login info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all admins with pagination
     *
     * @param int $limit Number of records per page
     * @param int $offset Offset for pagination
     * @return array Array of admin records
     */
    public function getAll($limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, role, status, created_at, last_login 
                FROM admin_user 
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all admins: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of admin users
     *
     * @return int Total count
     */
    public function getTotalCount()
    {
        try {
            $result = $this->pdo->query("SELECT COUNT(*) FROM admin_user");
            return (int)$result->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting admin total count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get admin statistics
     *
     * @return array Statistics array with keys: admins, staff, pending, inactive
     */
    public function getStats()
    {
        $stats = [
            'admins' => 0,
            'staff' => 0,
            'pending' => 0,
            'inactive' => 0
        ];
        
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    SUM(CASE WHEN role IN ('admin', 'super_admin') THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff,
                    SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                FROM admin_user
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $stats['admins'] = (int)$row['admins'];
                $stats['staff'] = (int)$row['staff'];
                $stats['pending'] = (int)$row['pending'];
                $stats['inactive'] = (int)$row['inactive'];
            }
        } catch (PDOException $e) {
            error_log("Error getting admin stats: " . $e->getMessage());
        }
        
        return $stats;
    }
}