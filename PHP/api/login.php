<?php
// login.php

header('Content-Type: application/json');
require_once 'db_connect.php';

/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Method Not Allowed. Use POST.", 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
if (stripos((string)$contentType, 'application/json') === false) {
    apiResponse::error("Invalid Content-Type. Use application/json.", 400);
}

function getAllowedGoogleClientIds() {
    $raw = getenv('GOOGLE_CLIENT_IDS');
    if ($raw === false || trim($raw) === '') {
        // Fallback IDs for environments where PHP-FPM env propagation is not configured.
        return [
            '741992345972-4gmr2mdu5q3db5mr81gcd07vsal7s0dk.apps.googleusercontent.com',
            '741992345972-jidu0oo2d7udq1u0424fd5ocla9f0not.apps.googleusercontent.com'
        ];
    }

    $parts = array_map('trim', explode(',', $raw));
    return array_values(array_filter($parts, function ($v) {
        return $v !== '';
    }));
}

function verifyGoogleIdToken($idToken, $expectedGoogleId = '', $expectedEmail = '') {
    if (empty($idToken)) {
        return [false, 'Missing Google token.', null];
    }

    $allowedClientIds = getAllowedGoogleClientIds();
    if (empty($allowedClientIds)) {
        return [false, 'Google Sign-In is not configured on server.', null];
    }

    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        error_log('Google token verification cURL error: ' . $err);
        return [false, 'Unable to verify Google token.', null];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        error_log('Google token verification failed with status ' . $httpCode . ': ' . $response);
        return [false, 'Invalid Google token.', null];
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        return [false, 'Invalid Google token payload.', null];
    }

    $iss = isset($payload['iss']) ? trim((string)$payload['iss']) : '';
    $aud = isset($payload['aud']) ? trim((string)$payload['aud']) : '';
    $sub = isset($payload['sub']) ? trim((string)$payload['sub']) : '';
    $email = isset($payload['email']) ? trim((string)$payload['email']) : '';
    $emailVerified = isset($payload['email_verified']) ? $payload['email_verified'] : false;
    $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;

    $validIssuer = ($iss === 'accounts.google.com' || $iss === 'https://accounts.google.com');
    if (!$validIssuer || $sub === '' || $aud === '' || $exp <= time()) {
        return [false, 'Invalid Google token claims.', null];
    }

    if (!in_array($aud, $allowedClientIds, true)) {
        return [false, 'Google token audience is not allowed.', null];
    }

    $emailVerifiedBool = ($emailVerified === true || $emailVerified === 'true' || $emailVerified === 1 || $emailVerified === '1');
    if (!$emailVerifiedBool) {
        return [false, 'Google email is not verified.', null];
    }

    if ($expectedGoogleId !== '' && $expectedGoogleId !== $sub) {
        return [false, 'Google ID does not match token.', null];
    }

    if ($expectedEmail !== '' && strcasecmp($expectedEmail, $email) !== 0) {
        return [false, 'Email does not match token.', null];
    }

    return [true, 'OK', $payload];
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    apiResponse::error("Invalid JSON input.", 400);
}

// Check if this is a Google Login request
$isGoogleLogin = isset($data['google_token']) && !empty($data['google_token']);

// Determine the identifiers
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$google_id = isset($data['google_id']) ? trim($data['google_id']) : '';
$plainPassword = isset($data['password']) ? $data['password'] : '';

// Validation
if (!$isGoogleLogin && empty($email) && empty($phone)) {
    apiResponse::error("Email or Phone is required.", 400);
}

try {
    $user = null;
    $authenticated = false;

    if ($isGoogleLogin) {
        list($isValidGoogleToken, $googleTokenMessage, $googleClaims) = verifyGoogleIdToken(
            (string)$data['google_token'],
            $google_id,
            $email
        );
        if (!$isValidGoogleToken) {
            apiResponse::error($googleTokenMessage, 401);
        }

        // Always trust identity fields from verified token, not client-provided values.
        $google_id = trim((string)($googleClaims['sub'] ?? $google_id));
        $email = trim((string)($googleClaims['email'] ?? $email));

        // --- Google Login Flow ---
        // Find user by Google ID or Email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$google_id, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Auto-Register new Google user
            $name = isset($googleClaims['name']) && trim((string)$googleClaims['name']) !== ''
                ? trim((string)$googleClaims['name'])
                : (isset($data['name']) ? trim($data['name']) : 'Google User');

            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, google_id, status, user_type) 
                VALUES (?, ?, ?, 'active', 'citizen')
            ");
            $insertStmt->execute([$name, $email, $google_id]);

            $newId = $pdo->lastInsertId();
            $user = [
                'id' => $newId,
                'name' => $name,
                'email' => $email,
                'phone' => ''
            ];
        } else {
            // Link Google ID if it was missing
            if (empty($user['google_id'])) {
                $upd = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $upd->execute([$google_id, $user['id']]);
            }
        }

        $authenticated = true;

    } else {
        // --- Standard Login Flow ---
        $identifier = !empty($email) ? $email : $phone;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['password']) && password_verify($plainPassword, $user['password'])) {
            $authenticated = true;
        } else {
            apiResponse::error("Invalid email/phone or password.", 401);
            exit;
        }
    }

    if ($authenticated && $user) {
        // --- Device Registration ---
        $deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
        $deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
        $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
        $fcmToken   = isset($data['fcm_token']) ? trim($data['fcm_token']) : (isset($data['push_token']) ? trim($data['push_token']) : null);

        if (!empty($deviceId)) {
            $deviceStmt = $pdo->prepare("
                INSERT INTO user_devices 
                (user_id, device_id, device_type, device_name, fcm_token, is_active, last_active)
                VALUES (?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                    fcm_token = VALUES(fcm_token),
                    device_name = VALUES(device_name),
                    is_active = 1,
                    last_active = NOW()
            ");
            $deviceStmt->execute([
                $user['id'],
                $deviceId,
                $deviceType,
                $deviceName,
                $fcmToken
            ]);
        }

        apiResponse::success([
            "user_id" => (int)$user['id'],
            "username" => $user['name'],
            "email" => $user['email'],
            "phone" => $user['phone'] ?? '',
            "token" => bin2hex(random_bytes(16))
        ], "Login successful!");
    }

} catch (PDOException $e) {
    error_log('Login DB Error: ' . $e->getMessage());
    apiResponse::error("Database error.", 500);
}
?>
