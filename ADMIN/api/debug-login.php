<?php
/**
 * DEBUG: Check admin account - REMOVE AFTER TESTING!
 */
header('Content-Type: application/json');

// Auto-detect environment
$isProduction = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'alertaraqc.com') !== false;

if ($isProduction) {
    $dbHost = 'localhost';
    $dbName = 'emer_comm_test';
    $dbUser = 'root';
    $dbPass = 'YsqnXk6q#145';
} else {
    $dbHost = 'localhost';
    $dbName = 'emer_comm_test';
    $dbUser = 'root';
    $dbPass = '';
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Get parameters from query string
    $email = $_GET['email'] ?? '';
    $testPassword = $_GET['password'] ?? '';
    $resetPassword = $_GET['reset_password'] ?? '';
    
    // RESET PASSWORD FEATURE
    if (!empty($email) && !empty($resetPassword)) {
        $newHash = password_hash($resetPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_user SET password = ? WHERE email = ?");
        $stmt->execute([$newHash, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset!',
                'email' => $email,
                'new_password' => $resetPassword,
                'next_step' => 'Go to login page and use this password'
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No account found with this email',
                'email' => $email
            ], JSON_PRETTY_PRINT);
        }
        exit();
    }
    
    if (empty($email)) {
        // List all admin accounts
        $stmt = $pdo->query("SELECT id, name, email, role, status, created_at FROM admin_user ORDER BY id");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin accounts in database',
            'total' => count($admins),
            'admins' => $admins,
            'hints' => [
                'test_password' => 'Add ?email=your@email.com&password=yourpassword to test login',
                'reset_password' => 'Add ?email=your@email.com&reset_password=newpassword to reset password'
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        // Check specific account
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM admin_user WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo json_encode([
                'success' => false,
                'message' => 'No admin account found with this email',
                'email' => $email
            ], JSON_PRETTY_PRINT);
        } else {
            $result = [
                'success' => true,
                'message' => 'Account found',
                'account' => [
                    'id' => $admin['id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role' => $admin['role'],
                    'status' => $admin['status'],
                    'password_hash_length' => strlen($admin['password']),
                    'password_starts_with' => substr($admin['password'], 0, 10) . '...'
                ]
            ];
            
            // Test password if provided
            if (!empty($testPassword)) {
                $passwordValid = password_verify($testPassword, $admin['password']);
                $result['password_test'] = [
                    'provided_password_length' => strlen($testPassword),
                    'password_valid' => $passwordValid,
                    'message' => $passwordValid ? 'Password is CORRECT!' : 'Password is WRONG!'
                ];
                
                if (!$passwordValid) {
                    $result['hint'] = 'To reset password, use: ?email=' . $email . '&reset_password=YOUR_NEW_PASSWORD';
                }
            }
            
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

