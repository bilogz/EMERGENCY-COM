<?php
/**
 * Facebook User Data Deletion Callback
 * Handles user data deletion requests from Facebook
 * Facebook will POST to this URL when a user requests data deletion
 * 
 * URL: https://your-domain.com/EMERGENCY-COM/USERS/api/facebook-data-deletion.php
 */

// Enable error logging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

// Facebook sends a signed_request parameter
$signedRequest = $_POST['signed_request'] ?? null;

if (!$signedRequest) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing signed_request',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php'
    ]);
    exit;
}

// Parse signed request
list($encodedSig, $payload) = explode('.', $signedRequest, 2);

// Decode the data
$sig = base64UrlDecode($encodedSig);
$data = json_decode(base64UrlDecode($payload), true);

// Load environment variables to get App Secret
$envFile = __DIR__ . '/../../.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

$appSecret = $env['APP_SECRET'] ?? '';

if (empty($appSecret)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'App secret not configured',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php'
    ]);
    exit;
}

// Verify signature
$expectedSig = hash_hmac('sha256', $payload, $appSecret, true);

if ($sig !== $expectedSig) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid signature',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php'
    ]);
    exit;
}

// Check if data is valid
if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid data - user_id not found',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php'
    ]);
    exit;
}

$facebookId = $data['user_id'];
$confirmationCode = generateConfirmationCode();

// Include database connection
require_once 'db_connect.php';

try {
    // Find user by Facebook ID
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE facebook_id = ?");
    $stmt->execute([$facebookId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found - still return success as there's nothing to delete
        error_log("Facebook data deletion: User with Facebook ID {$facebookId} not found");
        
        echo json_encode([
            'success' => true,
            'confirmation_code' => $confirmationCode,
            'message' => 'No user data found for this Facebook account',
            'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php?code=' . $confirmationCode
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Log the deletion request
    $stmt = $pdo->prepare("INSERT INTO data_deletion_requests 
        (facebook_id, user_id, confirmation_code, status, requested_at) 
        VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$facebookId, $userId, $confirmationCode]);
    
    $deletionId = $pdo->lastInsertId();
    
    // Delete user data
    // Note: We anonymize rather than fully delete to maintain audit trails
    
    // 1. Delete user sessions
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 2. Delete user preferences
    $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 3. Delete emergency contacts
    $stmt = $pdo->prepare("DELETE FROM emergency_contacts WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // 4. Anonymize user data (GDPR-compliant deletion)
    $stmt = $pdo->prepare("UPDATE users SET 
        name = 'Deleted User',
        email = CONCAT('deleted_', ?, '@deleted.local'),
        phone = CONCAT('DELETED', ?),
        facebook_id = NULL,
        google_id = NULL,
        oauth_provider = NULL,
        password = NULL,
        barangay = NULL,
        house_number = NULL,
        address = NULL,
        profile_picture = NULL,
        date_of_birth = NULL,
        gender = NULL,
        emergency_contact_name = NULL,
        emergency_contact_phone = NULL,
        emergency_contact_relation = NULL,
        status = 'deleted',
        updated_at = NOW()
        WHERE id = ?");
    $stmt->execute([$confirmationCode, $userId, $userId]);
    
    // 5. Log the deletion
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs 
        (user_id, activity_type, description, status, metadata) 
        VALUES (?, 'data_deletion', 'User data deleted via Facebook callback', 'success', ?)");
    $stmt->execute([$userId, json_encode([
        'facebook_id' => $facebookId,
        'confirmation_code' => $confirmationCode,
        'method' => 'facebook_callback'
    ])]);
    
    // Update deletion request status
    $stmt = $pdo->prepare("UPDATE data_deletion_requests 
        SET status = 'completed', completed_at = NOW() 
        WHERE id = ?");
    $stmt->execute([$deletionId]);
    
    error_log("Facebook data deletion completed: User ID {$userId}, Confirmation Code: {$confirmationCode}");
    
    // Return success response with confirmation code
    echo json_encode([
        'success' => true,
        'confirmation_code' => $confirmationCode,
        'message' => 'User data has been successfully deleted',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php?code=' . $confirmationCode
    ]);
    
} catch (PDOException $e) {
    error_log('Facebook data deletion error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'url' => 'https://your-domain.com/EMERGENCY-COM/USERS/data-deletion-status.php'
    ]);
}

/**
 * Generate a unique confirmation code
 */
function generateConfirmationCode() {
    return 'DEL-' . strtoupper(bin2hex(random_bytes(8)));
}

/**
 * Base64 URL Decode
 * Facebook uses URL-safe base64 encoding
 */
function base64UrlDecode($input) {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}
