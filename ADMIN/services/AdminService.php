<?php
/**
 * Admin Service
 * Business logic for admin operations
 * 
 * @package ADMIN\Services
 */

require_once __DIR__ . '/../repositories/AdminRepository.php';

class AdminService {
    private $repository;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->repository = new AdminRepository($pdo);
    }
    
    /**
     * Get admin profile data by ID
     * 
     * @param int $adminId Admin user ID
     * @return array|null Admin profile data or null if not found
     */
    public function getProfileById($adminId) {
        return $this->repository->getById($adminId);
    }
    
    /**
     * Get admin name and email for header/profile display
     * 
     * @param int $adminId Admin user ID
     * @return array|null Array with 'name' and 'email' keys, or null if not found
     */
    public function getNameAndEmailById($adminId) {
        return $this->repository->getNameAndEmailById($adminId);
    }
    
    /**
     * Get complete admin profile with login information
     * 
     * @param int $adminId Admin user ID
     * @param int|null $currentLoginLogId Current login log ID
     * @return array Admin profile with login info
     */
    public function getCompleteProfile($adminId, $currentLoginLogId = null) {
        $admin = $this->repository->getById($adminId);
        
        if (!$admin) {
            return null;
        }
        
        $useAdminUserTable = $this->repository->usesAdminUserTable();
        
        // Prepare profile data based on table used
        if ($useAdminUserTable) {
            $profileData = [
                "id" => $admin['id'],
                "user_id" => $admin['user_id'] ?? null,
                "name" => $admin['name'],
                "username" => $admin['username'] ?? null,
                "email" => $admin['email'],
                "phone" => $admin['phone'] ?? null,
                "role" => $admin['role'] ?? null,
                "status" => $admin['status'],
                "created_at" => $admin['created_at'],
                "updated_at" => $admin['updated_at'] ?? null,
                "last_login" => $admin['last_login'] ?? null
            ];
        } else {
            $profileData = [
                "id" => $admin['id'],
                "name" => $admin['name'],
                "email" => $admin['email'],
                "phone" => $admin['phone'] ?? null,
                "status" => $admin['status'],
                "user_type" => $admin['user_type'] ?? null,
                "created_at" => $admin['created_at'],
                "updated_at" => $admin['updated_at'] ?? null
            ];
        }
        
        $currentLoginInfo = $this->repository->getCurrentLoginInfo($currentLoginLogId, $adminId);
        $lastLoginInfo = $this->repository->getLastLoginInfo($adminId);
        
        return [
            'profile' => $profileData,
            'current_login' => $currentLoginInfo ? [
                "login_at" => $currentLoginInfo['login_at'],
                "ip_address" => $currentLoginInfo['ip_address'],
                "user_agent" => $currentLoginInfo['user_agent']
            ] : null,
            "last_login" => $lastLoginInfo ? [
                "login_at" => $lastLoginInfo['login_at'],
                "ip_address" => $lastLoginInfo['ip_address'],
                "logout_at" => $lastLoginInfo['logout_at'] ?? null,
                "session_duration" => $lastLoginInfo['session_duration'] ?? null
            ] : null
        ];
    }
    
    /**
     * Get all admins with pagination
     * 
     * @param int $page Page number (1-based)
     * @param int $limit Records per page
     * @return array Array with 'users', 'stats', and 'pagination' keys
     */
    public function getAllWithPagination($page = 1, $limit = 50) {
        $page = max(1, (int)$page);
        $limit = min(100, max(10, (int)$limit));
        $offset = ($page - 1) * $limit;
        
        $users = $this->repository->getAll($limit, $offset);
        $totalCount = $this->repository->getTotalCount();
        $stats = $this->repository->getStats();
        
        return [
            'users' => $users,
            'stats' => $stats,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => (int)ceil($totalCount / $limit)
            ]
        ];
    }
}