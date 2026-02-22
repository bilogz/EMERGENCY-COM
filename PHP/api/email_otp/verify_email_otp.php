<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
/** @var PDO|null $pdo */
if (!($pdo instanceof PDO)) {
    apiResponse::error('Database not initialized.', 500);
}

function verifyOtpLoadDotEnvIfPresent() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envPath = __DIR__ . '/../.env';
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

function b64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function sign_token($email, $otpId, $expTs, $secret) {
    $payload = json_encode(['email' => $email, 'otp_id' => $otpId, 'exp' => $expTs], JSON_UNESCAPED_SLASHES);
    $p = b64url_encode($payload);
    $sig = hash_hmac('sha256', $p, $secret, true);
    return $p . '.' . b64url_encode($sig);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$otp = trim($data['otp'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email)) {
    apiResponse::error('Invalid Gmail address.', 400);
}
if (!preg_match('/^\d{6}$/', $otp)) {
    apiResponse::error('OTP must be 6 digits.', 400);
}

verifyOtpLoadDotEnvIfPresent();
$secret = getenv('OTP_TOKEN_SECRET');
if (!$secret) {
    apiResponse::error('OTP token secret not configured.', 500);
}

try {
    $pdo->beginTransaction();

    // expire old pending OTPs
    $pdo->prepare("UPDATE otp_verifications SET status='expired' WHERE status='pending' AND expires_at <= NOW()")->execute();

    $q = $pdo->prepare("
        SELECT id, otp_code, attempts, expires_at
        FROM otp_verifications
        WHERE email = ?
          AND purpose = 'signup'
          AND status = 'pending'
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");
    $q->execute([$email]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        apiResponse::error('No pending OTP found. Request a new OTP.', 400);
    }

    $otpId = (int)$row['id'];
    $attempts = (int)$row['attempts'];

    if (strtotime($row['expires_at']) <= time()) {
        $pdo->prepare("UPDATE otp_verifications SET status='expired' WHERE id=?")->execute([$otpId]);
        $pdo->commit();
        apiResponse::error('OTP expired. Request a new OTP.', 400);
    }

    if ($attempts >= 5) {
        $pdo->prepare("UPDATE otp_verifications SET status='failed' WHERE id=?")->execute([$otpId]);
        $pdo->commit();
        apiResponse::error('Too many failed attempts.', 400);
    }

    if (!hash_equals((string)$row['otp_code'], $otp)) {
        $attempts++;
        if ($attempts >= 5) {
            $u = $pdo->prepare("UPDATE otp_verifications SET attempts=?, status='failed' WHERE id=?");
            $u->execute([$attempts, $otpId]);
        } else {
            $u = $pdo->prepare("UPDATE otp_verifications SET attempts=? WHERE id=?");
            $u->execute([$attempts, $otpId]);
        }
        $pdo->commit();
        apiResponse::error('Invalid OTP.', 400);
    }

    $u = $pdo->prepare("UPDATE otp_verifications SET status='verified', verified_at=NOW() WHERE id=?");
    $u->execute([$otpId]);

    // token valid for 15 minutes
    $expTs = time() + 900;
    $token = sign_token($email, $otpId, $expTs, $secret);

    $pdo->commit();
    apiResponse::success(['verification_token' => $token], 'Email verified successfully.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("verify_email_otp error: " . $e->getMessage());
    apiResponse::error('OTP verification failed.', 500);
}
