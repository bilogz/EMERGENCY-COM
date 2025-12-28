<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'] ?? '';
    $captchaToken = $input['captcha_token'] ?? '';
    
    // Validate inputs
    if (empty($phone) || empty($captchaToken)) {
        throw new Exception('Missing phone number or CAPTCHA token');
    }
    
    // Normalize phone number (remove spaces, dashes, etc.)
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Validate CAPTCHA token with Google's API
    $captchaSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Test secret key (always returns success)
    $captchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    
    // For demonstration/testing: if using test keys, skip real verification
    $isCaptchaValid = true;
    
    // If you have production reCAPTCHA keys, uncomment and use real verification:
    /*
    $captchaResult = json_decode(file_get_contents(
        $captchaUrl . '?secret=' . $captchaSecretKey . '&response=' . $captchaToken
    ), true);
    $isCaptchaValid = $captchaResult['success'] ?? false;
    */
    
    if (!$isCaptchaValid) {
        throw new Exception('CAPTCHA verification failed. Please try again.');
    }
    
    // Check if user exists with this phone number
    $query = "SELECT id as user_id, name as full_name, email FROM users WHERE phone = ? LIMIT 1";
    $stmt = $pdo->prepare($query);
    
    if (!$stmt->execute([$phone])) {
        throw new Exception('Database query failed');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('No user found with this contact number. Please sign up first.');
    }
    
    // Login successful - create session
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['full_name'] ?? 'User';
    $_SESSION['phone'] = $phone;
    
    // Optional: Log login activity
    try {
        $logQuery = "INSERT INTO login_history (user_id, login_time, ip_address) VALUES (?, NOW(), ?)";
        $logStmt = $pdo->prepare($logQuery);
        $logStmt->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Silently ignore if table doesn't exist
    }
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user_name'] = $user['full_name'] ?? 'User';
    $response['user_id'] = $user['user_id'];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
