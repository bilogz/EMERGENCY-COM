<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../../../VENDOR/autoload.php';
/** @var PDO|null $pdo */
if (!($pdo instanceof PDO)) {
    apiResponse::error('Database not initialized.', 500);
}

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

    $cooldown = 60;

    // simple resend limit: 60s per email/purpose
    $last = $pdo->prepare("
        SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed_seconds
        FROM otp_verifications
        WHERE email = ? AND purpose = 'signup'
        ORDER BY id DESC
        LIMIT 1
    ");
    $last->execute([$email]);
    $elapsedSeconds = $last->fetchColumn();

    if ($elapsedSeconds !== false && $elapsedSeconds !== null) {
        $elapsed = (int)$elapsedSeconds;
        if ($elapsed < $cooldown) {
            $retryAfter = max(1, $cooldown - $elapsed);
            $pdo->rollBack();
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: ' . $retryAfter);
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
                'retry_after' => $retryAfter
            ]);
            exit();
        }
    }

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

    $smtpUser = getenv('GMAIL_SMTP_USER');
    $smtpPass = getenv('GMAIL_SMTP_APP_PASSWORD');
    if (!$smtpUser || !$smtpPass) {
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
    $mail->setFrom(getenv('OTP_MAIL_FROM') ?: $smtpUser, getenv('OTP_MAIL_NAME') ?: 'Alertara OTP');
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
