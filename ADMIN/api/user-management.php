<?php
/**
 * User Management API
 * Handles CRUD operations for admin and staff users
 * Only accessible by super_admin role
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user is super_admin
$userRole = $_SESSION['admin_role'] ?? 'admin';
if ($userRole !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Super admin privileges required.']);
    exit();
}

// Include database connection
try {
    require_once 'db_connect.php';
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit();
}

if (!isset($pdo) || $pdo === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle GET requests (list users)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        try {
            // Get all users from admin_user table
            $stmt = $pdo->query("
                SELECT id, name, email, role, status, created_at, last_login 
                FROM admin_user 
                ORDER BY created_at DESC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate stats
            $stats = [
                'admins' => 0,
                'staff' => 0,
                'pending' => 0,
                'inactive' => 0
            ];
            
            foreach ($users as $user) {
                if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                    $stats['admins']++;
                } elseif ($user['role'] === 'staff') {
                    $stats['staff']++;
                }
                
                if ($user['status'] === 'pending_approval') {
                    $stats['pending']++;
                } elseif ($user['status'] === 'inactive') {
                    $stats['inactive']++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'stats' => $stats
            ]);
        } catch (PDOException $e) {
            error_log('User list error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to fetch users']);
        }
        exit();
    }
    
    if ($action === 'get' && isset($_GET['id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at, last_login FROM admin_user WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (PDOException $e) {
            error_log('User get error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to fetch user']);
        }
        exit();
    }
}

// Handle POST requests (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data === null) {
        $data = $_POST;
    }
    
    $action = $data['action'] ?? '';
    
    // CREATE USER
    if ($action === 'create') {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'staff';
        $status = $data['status'] ?? 'active';
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Name, email, and password are required']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit();
        }
        
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            exit();
        }
        
        // Validate role
        $allowedRoles = ['admin', 'staff'];
        if (!in_array($role, $allowedRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
            exit();
        }
        
        // Validate status
        $allowedStatuses = ['active', 'inactive', 'pending_approval'];
        if (!in_array($status, $allowedStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status specified']);
            exit();
        }
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM admin_user WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email address already exists']);
                exit();
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO admin_user (name, email, password, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $hashedPassword, $role, $status]);
            
            $newUserId = $pdo->lastInsertId();
            
            // Log activity
            error_log("User created: {$email} (ID: {$newUserId}) by admin ID: " . ($_SESSION['admin_user_id'] ?? 'unknown'));
            
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'user_id' => $newUserId
            ]);
        } catch (PDOException $e) {
            error_log('User create error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }
        exit();
    }
    
    // UPDATE USER
    if ($action === 'update') {
        $id = $data['id'] ?? null;
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';
        $status = $data['status'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }
        
        // Validation
        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit();
        }
        
        // Can't change super_admin role
        try {
            $stmt = $pdo->prepare("SELECT role FROM admin_user WHERE id = ?");
            $stmt->execute([$id]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }
            
            if ($existingUser['role'] === 'super_admin' && $role !== 'super_admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot change super admin role']);
                exit();
            }
            
            // Check if email already exists (for another user)
            $stmt = $pdo->prepare("SELECT id FROM admin_user WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email address already exists']);
                exit();
            }
            
            // Build update query
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
                    exit();
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE admin_user 
                    SET name = ?, email = ?, password = ?, role = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $hashedPassword, $role, $status, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE admin_user 
                    SET name = ?, email = ?, role = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $role, $status, $id]);
            }
            
            error_log("User updated: {$email} (ID: {$id}) by admin ID: " . ($_SESSION['admin_user_id'] ?? 'unknown'));
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } catch (PDOException $e) {
            error_log('User update error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        exit();
    }
    
    // DELETE USER
    if ($action === 'delete') {
        $id = $data['id'] ?? null;
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }
        
        // Cannot delete yourself
        if ($id == ($_SESSION['admin_user_id'] ?? null)) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit();
        }
        
        try {
            // Check if user exists and is not super_admin
            $stmt = $pdo->prepare("SELECT role, email FROM admin_user WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }
            
            if ($user['role'] === 'super_admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot delete super admin account']);
                exit();
            }
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM admin_user WHERE id = ?");
            $stmt->execute([$id]);
            
            error_log("User deleted: {$user['email']} (ID: {$id}) by admin ID: " . ($_SESSION['admin_user_id'] ?? 'unknown'));
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } catch (PDOException $e) {
            error_log('User delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Invalid request method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>






