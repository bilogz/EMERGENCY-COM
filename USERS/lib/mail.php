<?php
/**
 * Simple mail helper using PHPMailer (if installed) with fallback to mail()
 * Usage: require_once __DIR__ . '/../config/mail_config.php'; then call sendSMTPMail($to, $subject, $body, $isHtml=false)
 */

function load_mail_config() {
    $example = __DIR__ . '/../config/mail_config.php.example';
    $actual = __DIR__ . '/../config/mail_config.php';

    if (file_exists($actual)) {
        return include $actual;
    }
    if (file_exists($example)) {
        return include $example;
    }
    return [];
}

function sendSMTPMail($to, $subject, $body, $isHtml = false, &$error = null) {
    $cfg = load_mail_config();
    $error = null;

    // Try PHPMailer via autoload (check both vendor and VENDOR for case sensitivity)
    $composerAutoload1 = __DIR__ . '/../../vendor/autoload.php';
    $composerAutoload2 = __DIR__ . '/../../VENDOR/autoload.php';
    if (file_exists($composerAutoload1)) {
        require_once $composerAutoload1;
    } elseif (file_exists($composerAutoload2)) {
        require_once $composerAutoload2;
    }

    // Also try direct path to PHPMailer-master
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer', false)) {
        $phpmailerPath = __DIR__ . '/../../VENDOR/PHPMailer-master/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/Exception.php';
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/PHPMailer.php';
            require_once __DIR__ . '/../../VENDOR/PHPMailer-master/src/SMTP.php';
        }
    }

    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            // SMTP settings
            if (!empty($cfg['host'])) {
                $mail->isSMTP();
                $mail->Host = $cfg['host'];
                $mail->Port = $cfg['port'] ?? 587;
                $mail->SMTPAuth = isset($cfg['auth']) ? (bool)$cfg['auth'] : true;
                if (!empty($cfg['username'])) {
                    $mail->Username = $cfg['username'];
                    $mail->Password = $cfg['password'];
                }
                if (!empty($cfg['secure'])) {
                    $mail->SMTPSecure = $cfg['secure'];
                }
            }

            $fromEmail = $cfg['from_email'] ?? 'no-reply@example.com';
            $fromName = $cfg['from_name'] ?? 'No Reply';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;

            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $body;
            } else {
                $mail->Body = $body;
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("PHPMailer error: " . $error);
            // Fall through to mail() fallback if allowed
        }
    }

    // Fallback to mail()
    if (!empty($cfg['send_fallback_to_mail'])) {
        $headers = "From: " . ($cfg['from_email'] ?? 'no-reply@example.com') . "\r\n" .
            "Content-Type: text/plain; charset=utf-8\r\n";
        $sent = false;
        try {
            $sent = mail($to, $subject, $body, $headers);
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("mail() error: " . $error);
            $sent = false;
        }
        return $sent;
    }

    $error = 'No mailer available and fallback disabled';
    return false;
}
