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

    // expire old pending OTPs
    $exp = $pdo->prepare("UPDATE otp_verifications SET status='expired' WHERE status='pending' AND expires_at <= NOW()");
    $exp->execute();

    // simple resend limit: 60s per email/purpose
    $last = $pdo->prepare("
        SELECT created_at
        FROM otp_verifications
        WHERE email = ? AND purpose = 'signup'
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");
    $last->execute([$email]);
    $lastCreated = $last->fetchColumn();

    if ($lastCreated && (time() - strtotime($lastCreated) < 60)) {
        $pdo->rollBack();
        apiResponse::error('Please wait before requesting another OTP.', 429);
    }

    // invalidate previous pending OTPs for this email/purpose
    $inv = $pdo->prepare("
        UPDATE otp_verifications
        SET status='expired'
        WHERE email = ? AND purpose='signup' AND status='pending'
    ");
    $inv->execute([$email]);

    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 mins

    $ins = $pdo->prepare("
        INSERT INTO otp_verifications
            (email, otp_code, purpose, status, expires_at, attempts, ip_address, created_at)
        VALUES
            (?, ?, 'signup', 'pending', ?, 0, ?, NOW())
    ");
    $ins->execute([$email, $otp, $expiresAt, $ip]);

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
