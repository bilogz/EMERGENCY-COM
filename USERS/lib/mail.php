<?php
/**
 * Simple mail helper using PHPMailer (if installed) with fallback to mail()
 * Usage: require_once __DIR__ . '/../config/mail_config.php'; then call sendSMTPMail($to, $subject, $body, $isHtml=false)
 */

function load_mail_config() {
    $cfg = [];

    $secureCfgPath = __DIR__ . '/../api/config.env.php';
    // The admin worker already loads its equivalent config.env.php. Reuse the
    // existing function to avoid fatal duplicate function declarations.
    if (!function_exists('getEmailConfig') && file_exists($secureCfgPath)) {
        require_once $secureCfgPath;
    }
    if (function_exists('getEmailConfig')) {
        $emailCfg = getEmailConfig();
        if (is_array($emailCfg)) {
            $cfg = [
                'host' => $emailCfg['smtp_host'] ?? null,
                'port' => $emailCfg['smtp_port'] ?? null,
                'username' => $emailCfg['smtp_user'] ?? null,
                'password' => $emailCfg['smtp_pass'] ?? null,
                'from_email' => $emailCfg['smtp_from'] ?? null,
                'from_name' => $emailCfg['smtp_from_name'] ?? null,
                'auth' => true,
                'secure' => 'tls',
            ];
        }
    }

    $example = __DIR__ . '/../config/mail_config.php.example';
    $actual = __DIR__ . '/../config/mail_config.php';

    if (file_exists($actual)) {
        $fileCfg = include $actual;
        if (is_array($fileCfg)) {
            $cfg = array_merge($cfg, $fileCfg);
        }
    }
    if (file_exists($example)) {
        $fileCfg = include $example;
        if (is_array($fileCfg)) {
            $cfg = array_merge($cfg, $fileCfg);
        }
    }
    return $cfg;
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

            $fromEmail = $cfg['from_email'] ?? 'alertaraqc.notification@gmail.com';
            $fromName = $cfg['from_name'] ?? 'Emergency Alert System';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;

            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $body;
                $mail->AltBody = strip_tags($body);
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
        $headers = "From: " . ($cfg['from_email'] ?? 'alertaraqc.notification@gmail.com') . "\r\n" .
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

/**
 * Generate a modern, beautiful, responsive HTML email template for OTP codes
 */
function buildOTPEmailTemplate($name, $otpCode, $purpose = 'Admin Login', $expiryMinutes = 1) {
    $safeName = htmlspecialchars($name ?: 'User', ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
    $safePurpose = htmlspecialchars($purpose, ENT_QUOTES, 'UTF-8');
    $expiryText = $expiryMinutes == 1 ? '1 minute' : "{$expiryMinutes} minutes";

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$safePurpose} Verification Code</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#334155; -webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding:30px 15px;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:520px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.06); border:1px solid #e2e8f0;">
        
        <!-- Header Banner -->
        <tr>
          <td style="background: linear-gradient(135deg, #1e3a4c 0%, #2b5756 100%); padding: 32px 24px; text-align: center;">
            <div style="display:inline-block; width:52px; height:52px; line-height:52px; background:rgba(255,255,255,0.15); border-radius:14px; margin-bottom:12px; font-size:26px; color:#ffffff;">
              🛡️
            </div>
            <h1 style="margin:0 0 4px 0; color:#ffffff; font-size:20px; font-weight:700; letter-spacing:-0.3px;">Emergency Communication System</h1>
            <p style="margin:0; color:#cbd5e1; font-size:13px; font-weight:400;">Quezon City Disaster Risk Reduction & Management</p>
          </td>
        </tr>

        <!-- Main Body -->
        <tr>
          <td style="padding: 32px 28px 24px 28px;">
            <h2 style="margin:0 0 12px 0; font-size:18px; font-weight:700; color:#0f172a;">Verification Code</h2>
            <p style="margin:0 0 20px 0; font-size:15px; color:#475569; line-height:1.6;">
              Hello <strong style="color:#0f172a;">{$safeName}</strong>,<br>
              Please use the one-time verification code below to authorize your <strong>{$safePurpose}</strong> request:
            </p>

            <!-- Code Badge -->
            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:20px 0;">
              <tr>
                <td align="center" style="background:#f8fafc; border:2px dashed #3a7675; border-radius:14px; padding:22px 16px; text-align:center;">
                  <div style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; color:#64748b; font-weight:600; margin-bottom:8px;">One-Time Security Code</div>
                  <div style="font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:36px; font-weight:800; letter-spacing:8px; color:#1e3a4c; line-height:1.1;">
                    {$safeCode}
                  </div>
                  <div style="display:inline-block; margin-top:12px; background:#ecfdf5; border:1px solid #a7f3d0; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; color:#065f46;">
                    ⏱️ Valid for {$expiryText}
                  </div>
                </td>
              </tr>
            </table>

            <!-- Security Alert -->
            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#fffbeb; border-left:4px solid #f59e0b; border-radius:8px; padding:12px 14px; margin-bottom:20px;">
              <tr>
                <td style="font-size:13px; color:#92400e; line-height:1.5;">
                  🔒 <strong>Security Warning:</strong> Never share this verification code with anyone. Official personnel will never ask for your code.
                </td>
              </tr>
            </table>

            <p style="margin:0; font-size:13px; color:#64748b; line-height:1.5;">
              If you did not make this request, you can safely ignore this email or contact your administrator to secure your account.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:18px 24px; text-align:center; font-size:12px; color:#94a3b8; line-height:1.5;">
            <p style="margin:0 0 4px 0; font-weight:600; color:#64748b;">AlertaraQC Emergency Communication System</p>
            <p style="margin:0;">This is an automated system notification. Please do not reply directly to this email.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}

/**
 * Generate a modern, beautiful, responsive HTML email template for Emergency Alerts
 */
function buildEmergencyAlertEmailTemplate($title, $message, $severity = 'warning', $category = 'Emergency Alert', $issuedAt = null) {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $safeTime = htmlspecialchars($issuedAt ?: date('Y-m-d H:i:s T'), ENT_QUOTES, 'UTF-8');
    $formattedMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    
    $severityUpper = strtoupper((string)$severity);
    $badgeBg = '#dc2626';
    $headerGradient = 'linear-gradient(135deg, #7f1d1d 0%, #b91c1c 100%)';
    if ($severityUpper === 'INFO') {
        $badgeBg = '#2563eb';
        $headerGradient = 'linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%)';
    } elseif ($severityUpper === 'WARNING' || $severityUpper === 'MODERATE') {
        $badgeBg = '#d97706';
        $headerGradient = 'linear-gradient(135deg, #78350f 0%, #d97706 100%)';
    } elseif ($severityUpper === 'LOW') {
        $badgeBg = '#059669';
        $headerGradient = 'linear-gradient(135deg, #064e3b 0%, #059669 100%)';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$safeTitle}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#334155; -webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding:30px 15px;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:580px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.06); border:1px solid #e2e8f0;">
        
        <!-- Alert Header Banner -->
        <tr>
          <td style="background: {$headerGradient}; padding: 28px 24px; text-align: center;">
            <div style="display:inline-block; padding:4px 12px; background:rgba(255,255,255,0.2); border-radius:20px; color:#ffffff; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px;">
              🚨 {$severityUpper} ALERT
            </div>
            <h1 style="margin:0 0 6px 0; color:#ffffff; font-size:22px; font-weight:800; letter-spacing:-0.3px;">{$safeTitle}</h1>
            <p style="margin:0; color:#e2e8f0; font-size:13px;">{$safeCategory} &bull; Issued at {$safeTime}</p>
          </td>
        </tr>

        <!-- Alert Content -->
        <tr>
          <td style="padding: 30px 28px 24px 28px;">
            <div style="background:#f8fafc; border-left:4px solid {$badgeBg}; border-radius:8px; padding:20px; margin-bottom:24px; font-size:15px; line-height:1.7; color:#1e293b;">
              {$formattedMessage}
            </div>

            <!-- Action Advice -->
            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#f1f5f9; border-radius:10px; padding:14px 18px; margin-bottom:20px;">
              <tr>
                <td style="font-size:13px; color:#475569; line-height:1.5;">
                  ℹ️ <strong>Safety Advisory:</strong> Follow official advisories, stay tuned to verified local channels, and keep emergency contacts ready.
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:18px 24px; text-align:center; font-size:12px; color:#94a3b8; line-height:1.5;">
            <p style="margin:0 0 4px 0; font-weight:600; color:#64748b;">Quezon City Disaster Risk Reduction and Management Office</p>
            <p style="margin:0;">AlertaraQC Emergency Communication System</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}
