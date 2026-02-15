<?php
/**
 * Facebook Deauthorization Callback
 * Handles when users remove the app from their Facebook account
 * Facebook will POST to this URL when a user deauthorizes the app
 */

// Facebook sends a signed_request parameter
$signedRequest = $_POST['signed_request'] ?? null;

if (!$signedRequest) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing signed_request']);
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
    echo json_encode(['error' => 'App secret not configured']);
    exit;
}

// Verify signature
$expectedSig = hash_hmac('sha256', $payload, $appSecret, true);

if ($sig !== $expectedSig) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Check if data is valid
if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$facebookId = $data['user_id'];

// Include database connection
require_once 'db_connect.php';

try {
    // Find user by Facebook ID
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE facebook_id = ?");
    $stmt->execute([$facebookId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Option 1: Remove Facebook ID from user account (keeps account active)
        $stmt = $pdo->prepare("UPDATE users SET facebook_id = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log the deauthorization
        error_log("Facebook deauthorization: User ID {$user['id']} ({$user['email']}) removed Facebook connection");
        
        // Option 2: If you want to delete the user account entirely, uncomment below:
        // $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        // $stmt->execute([$user['id']]);
        // error_log("Facebook deauthorization: User ID {$user['id']} ({$user['email']}) account deleted");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User deauthorized successfully',
            'user_id' => $user['id']
        ]);
    } else {
        // User not found in database
        error_log("Facebook deauthorization: Facebook ID {$facebookId} not found in database");
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User not found, nothing to deauthorize'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Facebook deauthorization database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
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
