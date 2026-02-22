<?php
// register.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

function registerLoadDotEnvIfPresent() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }

        $len = strlen($value);
        $isDoubleQuoted = $len >= 2 && $value[0] === '"' && $value[$len - 1] === '"';
        $isSingleQuoted = $len >= 2 && $value[0] === "'" && $value[$len - 1] === "'";
        if ($isDoubleQuoted || $isSingleQuoted) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function b64url_decode($data) {
    $pad = strlen($data) % 4;
    if ($pad) {
        $data .= str_repeat('=', 4 - $pad);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function verify_token($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return null;
    }

    [$p, $s] = $parts;
    $calc = hash_hmac('sha256', $p, $secret, true);
    $sig = b64url_decode($s);
    if (!$sig || !hash_equals($calc, $sig)) {
        return null;
    }

    $payloadJson = b64url_decode($p);
    $payload = json_decode($payloadJson, true);
    if (!$payload || !isset($payload['email'], $payload['otp_id'], $payload['exp'])) {
        return null;
    }
    if ((int)$payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

// --- Server-Side Validation ---
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['phone'])) {
    apiResponse::error('Name, email, password, and phone are required.', 400);
}
if (!isset($data['email_verification_token'])) {
    apiResponse::error('email_verification_token is required.', 400);
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$plainPassword = $data['password'];
$emailVerificationToken = trim($data['email_verification_token']);
$shareLocation = isset($data['share_location']) ? (bool)$data['share_location'] : false;

$deviceId   = isset($data['device_id'])   ? trim($data['device_id'])   : null;
$deviceType = isset($data['device_type']) ? trim($data['device_type']) : 'android';
$deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
$fcmToken   = isset($data['fcm_token']) ? trim($data['fcm_token']) : (isset($data['push_token']) ? trim($data['push_token']) : null);

$latitude  = isset($data['latitude'])  ? (float)$data['latitude']  : 0.0;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
$address   = isset($data['address'])   ? trim($data['address'])    : null;

if (empty($name) || empty($email) || empty($plainPassword) || empty($phone)) {
    apiResponse::error('All fields must be filled.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiResponse::error('Invalid email format.', 400);
}

if (strlen($plainPassword) < 6) {
    apiResponse::error('Password must be at least 6 characters long.', 400);
}

// --- Database Operations ---
try {
    // Core registration must succeed atomically.
    $pdo->beginTransaction();

    registerLoadDotEnvIfPresent();
    $secret = getenv('OTP_TOKEN_SECRET');
    if (!$secret) {
        apiResponse::error('OTP token secret not configured.', 500);
    }

    $payload = verify_token($emailVerificationToken, $secret);
    if (!$payload) {
        apiResponse::error('Invalid or expired email verification token.', 401);
    }
    if (!hash_equals(strtolower($payload['email']), strtolower($email))) {
        apiResponse::error('Verification token email mismatch.', 401);
    }

    $otpId = (int)$payload['otp_id'];
    $otpStmt = $pdo->prepare("
        SELECT id, status
        FROM otp_verifications
        WHERE id = ? AND email = ? AND purpose = 'signup'
        LIMIT 1
        FOR UPDATE
    ");
    $otpStmt->execute([$otpId, $email]);
    $otpRow = $otpStmt->fetch(PDO::FETCH_ASSOC);

    if (!$otpRow || $otpRow['status'] !== 'verified') {
        $pdo->rollBack();
        apiResponse::error('OTP verification not found or already used.', 401);
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        apiResponse::error('This email address is already registered.', 409);
    }

    // 1. Insert user
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $userStmt->execute([$name, $email, $hashedPassword, $phone]);
    $userId = $pdo->lastInsertId();

    // 2. Preferences
    $prefsStmt = $pdo->prepare("INSERT INTO user_preferences (user_id, share_location) VALUES (?, ?)");
    $prefsStmt->execute([$userId, (int)$shareLocation]);

    $consumeOtp = $pdo->prepare("UPDATE otp_verifications SET status='expired' WHERE id=?");
    $consumeOtp->execute([$otpId]);

    $pdo->commit();

    // 3. Device (best-effort; do not fail registration)
    if (!empty($deviceId)) {
        try {
            $deviceSql = "INSERT INTO user_devices (user_id, device_id, device_type, device_name, fcm_token, is_active)
                          VALUES (?, ?, ?, ?, ?, 1)
                          ON DUPLICATE KEY UPDATE
                              user_id = VALUES(user_id),
                              device_type = VALUES(device_type),
                              device_name = VALUES(device_name),
                              fcm_token = VALUES(fcm_token),
                              is_active = 1";
            $deviceStmt = $pdo->prepare($deviceSql);
            $deviceStmt->execute([$userId, $deviceId, $deviceType, $deviceName, $fcmToken]);
        } catch (Exception $deviceEx) {
            error_log("Non-fatal Device Link Error: " . $deviceEx->getMessage());
        }
    }

    // 4. Location (best-effort; do not fail registration)
    if ($latitude != 0 && $longitude != 0) {
        try {
            $locStmt = $pdo->prepare("INSERT INTO user_locations (user_id, latitude, longitude, address, is_current) VALUES (?, ?, ?, ?, 1)");
            $locStmt->execute([$userId, $latitude, $longitude, $address]);
        } catch (Exception $locEx) {
            error_log("Non-fatal Location Save Error: " . $locEx->getMessage());
        }
    }

    apiResponse::success(['user_id' => (int)$userId], 'Registration successful!', 201);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration DB Error: " . $e->getMessage());
    apiResponse::error('DB Error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
