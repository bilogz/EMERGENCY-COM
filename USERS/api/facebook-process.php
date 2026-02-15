<?php
/**
 * Facebook OAuth Process Handler
 * Processes Facebook user data and handles login/signup
 */

session_start();

// Check if Facebook user data exists
if (!isset($_SESSION['facebook_user'])) {
    header('Location: ../login.php?error=no_facebook_data');
    exit;
}

$facebookUser = $_SESSION['facebook_user'];
$facebookId = $facebookUser['id'];
$name = $facebookUser['name'];
$email = $facebookUser['email'];
$picture = $facebookUser['picture'];

// Include database connection
require_once 'db_connect.php';

try {
    // Check if user exists with this Facebook ID
    $stmt = $pdo->prepare("SELECT * FROM users WHERE facebook_id = ?");
    $stmt->execute([$facebookId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User exists - log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_method'] = 'facebook';
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Clear Facebook session data
        unset($_SESSION['facebook_user']);
        
        // Redirect to home page
        header('Location: ../../index.php');
        exit;
    }
    
    // Check if user exists with this email
    if ($email) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            // Link Facebook ID to existing account
            $stmt = $pdo->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
            $stmt->execute([$facebookId, $existingUser['id']]);
            
            $_SESSION['user_id'] = $existingUser['id'];
            $_SESSION['user_name'] = $existingUser['full_name'];
            $_SESSION['user_email'] = $existingUser['email'];
            $_SESSION['login_method'] = 'facebook';
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$existingUser['id']]);
            
            // Clear Facebook session data
            unset($_SESSION['facebook_user']);
            
            header('Location: ../../index.php');
            exit;
        }
    }
    
    // New user - create account with minimal info
    // Generate a temporary phone number (user will need to update it)
    $tempPhone = 'FB' . time() . rand(100, 999);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (
            name, email, phone, facebook_id, oauth_provider,
            status, email_verified, phone_verified, user_type,
            created_at, updated_at
        ) VALUES (
            :name, :email, :phone, :facebook_id, 'facebook',
            'active', 1, 0, 'citizen',
            NOW(), NOW()
        )");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email ?: null,
            ':phone' => $tempPhone,
            ':facebook_id' => $facebookId
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Create user preferences
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['login_method'] = 'facebook';
        $_SESSION['needs_profile_completion'] = true;
        
        // Clear Facebook session data
        unset($_SESSION['facebook_user']);
        
        // Log the registration
        error_log("Facebook auto-registration: User ID {$userId} ({$email}) created");
        
        // Redirect to profile completion page or home
        header('Location: ../../index.php?welcome=1&complete_profile=1');
        exit;
        
    } catch (PDOException $e) {
        error_log('Facebook auto-registration error: ' . $e->getMessage());
        
        // If auto-registration fails, redirect to signup with data
        $_SESSION['facebook_signup'] = [
            'facebook_id' => $facebookId,
            'name' => $name,
            'email' => $email,
            'picture' => $picture
        ];
        
        unset($_SESSION['facebook_user']);
        header('Location: ../signup.php?facebook_signup=1');
        exit;
    }
    
} catch (PDOException $e) {
    error_log('Facebook process database error: ' . $e->getMessage());
    header('Location: ../login.php?error=database_error');
    exit;
}
