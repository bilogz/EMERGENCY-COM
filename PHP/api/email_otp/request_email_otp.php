<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../../../VENDOR/autoload.php';
/** @var PDO|null $pdo */
if (!($pdo instanceof PDO)) {
    apiResponse::error('Database not initialized.', 500);
}

function otpLoadDotEnvIfPresent() {
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

function otpEnv(array $keys, $default = null) {
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val !== false && $val !== null && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
    }
    return $default;
}

otpLoadDotEnvIfPresent();

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email)) {
    apiResponse::error('Invalid Gmail address.', 400);
}

try {
    $pdo->beginTransaction();

    // Expire stale signup OTPs using DB time.
    $exp = $pdo->prepare("
        UPDATE otp_verifications
        SET status='expired'
        WHERE status='pending'
          AND purpose='signup'
          AND expires_at <= NOW()
    ");
    $exp->execute();

    // invalidate previous pending OTPs for this email/purpose
    $inv = $pdo->prepare("
        UPDATE otp_verifications
        SET status='expired'
        WHERE email = ? AND purpose='signup' AND status='pending'
    ");
    $inv->execute([$email]);

    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $ins = $pdo->prepare("
        INSERT INTO otp_verifications
            (email, otp_code, purpose, status, expires_at, attempts, ip_address, created_at)
        VALUES
            (?, ?, 'signup', 'pending', DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0, ?, NOW())
    ");
    $ins->execute([$email, $otp, $ip]);

    $pdo->commit();

    $smtpUser = otpEnv(['GMAIL_SMTP_USER', 'SMTP_USER', 'SMTP_USERNAME', 'MAIL_USERNAME']);
    $smtpPass = otpEnv(['GMAIL_SMTP_APP_PASSWORD', 'GMAIL_APP_PASSWORD', 'SMTP_PASS', 'SMTP_PASSWORD', 'MAIL_PASSWORD']);
    if (!$smtpUser || !$smtpPass) {
        $envPath = __DIR__ . '/../.env';
        error_log(
            'OTP mailer config missing. env_file=' . (file_exists($envPath) ? 'present' : 'missing') .
            ' smtp_user=' . ($smtpUser ? 'set' : 'empty') .
            ' smtp_pass=' . ($smtpPass ? 'set' : 'empty')
        );
        apiResponse::error('OTP mailer not configured.', 500);
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        apiResponse::error('OTP mailer not available.', 500);
    }

    $mailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
    $mail = new $mailerClass(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mailFrom = otpEnv(['OTP_MAIL_FROM', 'MAIL_FROM_ADDRESS'], $smtpUser);
    $mailName = otpEnv(['OTP_MAIL_NAME', 'MAIL_FROM_NAME'], 'Alertara OTP');
    $mail->setFrom($mailFrom, $mailName);
    $mail->addAddress($email);
    $mail->Subject = 'Your OTP Code';
    $mail->Body = "Your OTP code is: {$otp}\n\nThis code expires in 5 minutes.";
    $mail->send();

    apiResponse::success([], 'OTP sent successfully.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("request_email_otp error: " . $e->getMessage());
    apiResponse::error('Failed to send OTP.', 500);
}
