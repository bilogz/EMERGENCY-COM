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
 * Parse structured or plain-text notifications into components for rich email rendering
 */
function parseNotificationMessageForEmail($rawMessage) {
    $lines = preg_split('/\r\n|\r|\n/', trim((string)$rawMessage));
    $summary = [];
    $metrics = [];
    $precautions = [];
    $ctaUrl = null;
    $ctaLabel = null;
    $inPrecautions = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed === '---') continue;

        // Check for Link / CTA
        if (preg_match('/(?:View Full Forecast|View Forecast|View Bulletin|View Alert|View Details|More info|Full Forecast|Link|Open Alertara|Open App|Dashboard)\s*:\s*(https?:\/\/[^\s]+)/i', $trimmed, $m)) {
            $ctaLabel = 'View Full Live Forecast & Radar';
            $ctaUrl = $m[1];
            continue;
        } elseif (preg_match('/^(https?:\/\/[^\s]+)$/i', $trimmed, $m)) {
            $ctaUrl = $m[1];
            $ctaLabel = 'View Full Details & Live Map';
            continue;
        }

        // Check for Precautions / Recommendations header
        if (preg_match('/^(?:PRECAUTIONS|SAFETY MEASURES|RECOMMENDATIONS|ACTION ITEMS|WHAT TO DO|SAFETY ADVISORY)\s*:?$/i', $trimmed)) {
            $inPrecautions = true;
            continue;
        }

        // Precautions items
        if ($inPrecautions || preg_match('/^[-*•]\s*(.+)/', $trimmed, $m)) {
            $item = preg_replace('/^[-*•]\s*/', '', $trimmed);
            if ($item !== '') {
                $precautions[] = $item;
            }
            continue;
        }

        // Metrics (Key: Value)
        if (preg_match('/^(Rain chance|Precipitation probability|Expected rainfall|Rain total|Rainfall|Temperature|Temp|Heat index|Feels like|Wind|Wind speed|Gusts|Peak period|Peak time|Magnitude|Depth|Location|Reported Intensities|Intensity)\s*:\s*(.+)$/i', $trimmed, $m)) {
            $k = trim($m[1]);
            $v = trim($m[2]);

            // Clean temperature units like "26.2-30.2 C" -> "26.2 - 30.2°C"
            $v = preg_replace('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)\s*C\b/i', '$1 - $2°C', $v);
            $v = preg_replace('/(\d+(?:\.\d+)?)\s*C\b/i', '$1°C', $v);

            $icon = '📊';
            $kl = strtolower($k);
            if (strpos($kl, 'rain chance') !== false || strpos($kl, 'probability') !== false) $icon = '🌧️';
            elseif (strpos($kl, 'rain') !== false) $icon = '💧';
            elseif (strpos($kl, 'temp') !== false || strpos($kl, 'heat') !== false) $icon = '🌡️';
            elseif (strpos($kl, 'wind') !== false || strpos($kl, 'gust') !== false) $icon = '💨';
            elseif (strpos($kl, 'peak') !== false || strpos($kl, 'time') !== false) $icon = '⏰';
            elseif (strpos($kl, 'magnitude') !== false) $icon = '⚡';
            elseif (strpos($kl, 'depth') !== false) $icon = '📏';
            elseif (strpos($kl, 'location') !== false) $icon = '📍';
            elseif (strpos($kl, 'intensity') !== false) $icon = '📈';

            $metrics[] = [
                'label' => $k,
                'value' => $v,
                'icon' => $icon
            ];
            continue;
        }

        // Header lines to skip if redundant with title
        if (preg_match('/^WEATHER FORECAST\s*-\s*QUEZON CITY$/i', $trimmed)) {
            continue;
        }

        // Summary text
        $summary[] = $trimmed;
    }

    return [
        'summary' => implode("\n\n", $summary),
        'metrics' => $metrics,
        'precautions' => $precautions,
        'cta_url' => $ctaUrl,
        'cta_label' => $ctaLabel
    ];
}

/**
 * Generate a modern, beautiful, responsive HTML email template for Mass Notifications & Alerts
 */
function buildEmergencyAlertEmailTemplate($title, $message, $severity = 'warning', $category = 'Emergency Alert', $issuedAt = null, $lang = 'en') {
    $isFilipino = ($lang === 'fil' || $lang === 'tl' || preg_match('/MGA PAGSUBOK|Naiulat|Apektadong|Paraan ng pag-iingat/i', $title . ' ' . $message));

    $safeTitle = htmlspecialchars($title ?: ($isFilipino ? 'Abiso sa Emerhensya' : 'Emergency Notification'), ENT_QUOTES, 'UTF-8');
    $safeCategory = htmlspecialchars($category ?: ($isFilipino ? 'Alerto sa Emerhensya' : 'Emergency Alert'), ENT_QUOTES, 'UTF-8');
    $safeTime = htmlspecialchars($issuedAt ?: date('M d, Y \a\t h:i A T'), ENT_QUOTES, 'UTF-8');
    
    // Parse message structure
    $parsed = parseNotificationMessageForEmail($message);
    $summary = $parsed['summary'];
    $metrics = $parsed['metrics'];
    $precautions = $parsed['precautions'];
    $ctaUrl = $parsed['cta_url'];
    $ctaLabel = $parsed['cta_label'] ?: ($isFilipino ? 'Buksan ang Emergency Portal ng Alertara' : 'Open Alertara Emergency Portal');

    $severityLower = strtolower(trim((string)$severity));
    $categoryLower = strtolower(trim((string)$category));
    $titleLower = strtolower(trim((string)$title));

    // Determine Theme & Colors
    $isWeather = (strpos($categoryLower, 'weather') !== false || strpos($titleLower, 'weather') !== false || strpos($titleLower, 'rain') !== false || strpos($titleLower, 'typhoon') !== false || strpos($titleLower, 'flood') !== false);
    $isEarthquake = (strpos($categoryLower, 'earthquake') !== false || strpos($titleLower, 'earthquake') !== false || strpos($titleLower, 'seismic') !== false || strpos($titleLower, 'phivolcs') !== false);
    $isFire = (strpos($categoryLower, 'fire') !== false || strpos($titleLower, 'fire') !== false);
    
    // Gradients & Badges
    if ($severityLower === 'critical' || $severityLower === 'high' || $severityLower === 'extreme') {
        $headerGradient = 'linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%)';
        $badgeBg = 'rgba(255, 255, 255, 0.22)';
        $badgeBorder = '#fca5a5';
        $accentColor = '#dc2626';
        $headerIcon = '🚨';
        $badgeText = $isFilipino ? 'MATAAS NA ALERTO SA EMERHENSYA' : 'CRITICAL EMERGENCY ALERT';
        $btnBg = '#dc2626';
    } elseif ($isWeather) {
        if ($severityLower === 'warning') {
            $headerGradient = 'linear-gradient(135deg, #1e3a8a 0%, #0369a1 50%, #0284c7 100%)';
            $badgeText = $isFilipino ? 'BABALA SA PANAHON • QUEZON CITY' : 'WEATHER WARNING • QUEZON CITY';
            $headerIcon = '⛈️';
            $btnBg = '#0284c7';
        } elseif ($severityLower === 'advisory') {
            $headerGradient = 'linear-gradient(135deg, #0f4c75 0%, #1b262c 50%, #1e3c72 100%)';
            $badgeText = $isFilipino ? 'ABISO SA PANAHON • QUEZON CITY' : 'WEATHER ADVISORY • QUEZON CITY';
            $headerIcon = '🌧️';
            $btnBg = '#0284c7';
        } else {
            $headerGradient = 'linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1e3a5f 100%)';
            $badgeText = $isFilipino ? 'TAYA NG PANAHON • QUEZON CITY' : 'WEATHER FORECAST • QUEZON CITY';
            $headerIcon = '🌦️';
            $btnBg = '#0ea5e9';
        }
        $badgeBg = 'rgba(255, 255, 255, 0.2)';
        $badgeBorder = '#7dd3fc';
        $accentColor = '#0284c7';
    } elseif ($isEarthquake) {
        $headerGradient = 'linear-gradient(135deg, #78350f 0%, #991b1b 50%, #b45309 100%)';
        $badgeBg = 'rgba(255, 255, 255, 0.2)';
        $badgeBorder = '#fde68a';
        $accentColor = '#b45309';
        $headerIcon = '🌋';
        $badgeText = $isFilipino ? 'ULAT NG LINDOL' : 'EARTHQUAKE BULLETIN';
        $btnBg = '#b45309';
    } elseif ($isFire) {
        $headerGradient = 'linear-gradient(135deg, #7c2d12 0%, #c2410c 50%, #ea580c 100%)';
        $badgeBg = 'rgba(255, 255, 255, 0.2)';
        $badgeBorder = '#fdba74';
        $accentColor = '#ea580c';
        $headerIcon = '🔥';
        $badgeText = $isFilipino ? 'ALERTO SA SUNOG' : 'FIRE INCIDENT ALERT';
        $btnBg = '#ea580c';
    } else {
        $headerGradient = 'linear-gradient(135deg, #1e3a4c 0%, #2b5756 50%, #3a7675 100%)';
        $badgeBg = 'rgba(255, 255, 255, 0.2)';
        $badgeBorder = '#99f6e4';
        $accentColor = '#3a7675';
        $headerIcon = '📢';
        $badgeText = ($isFilipino ? 'ALERTO SA EMERHENSYA' : strtoupper($safeCategory)) . ' • QUEZON CITY';
        $btnBg = '#3a7675';
    }

    // Build Metrics HTML if present
    $metricsHtml = '';
    if (!empty($metrics)) {
        $metricRows = '';
        $chunks = array_chunk($metrics, 2);
        foreach ($chunks as $chunk) {
            $metricRows .= '<tr>';
            foreach ($chunk as $m) {
                $mLabel = htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8');
                $mVal = htmlspecialchars($m['value'], ENT_QUOTES, 'UTF-8');
                $mIcon = $m['icon'];
                $metricRows .= <<<HTML
                <td width="50%" valign="top" style="padding: 5px;">
                  <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; margin-bottom: 4px;">
                      {$mIcon} {$mLabel}
                    </div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.25;">
                      {$mVal}
                    </div>
                  </div>
                </td>
HTML;
            }
            if (count($chunk) === 1) {
                $metricRows .= '<td width="50%" style="padding: 5px;"></td>';
            }
            $metricRows .= '</tr>';
        }

        $metricsTitle = $isFilipino ? '📊 Mga Metric sa Panahon at Kapaligiran' : '📊 Key Forecast & Environmental Metrics';
        $metricsHtml = <<<HTML
        <!-- Key Metrics Section -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 10px; margin-bottom: 22px;">
          <tr>
            <td colspan="2" style="padding: 4px 6px 8px 6px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.8px;">
              {$metricsTitle}
            </td>
          </tr>
          {$metricRows}
        </table>
HTML;
    }

    // Build Summary HTML
    $summaryHtml = '';
    if (!empty($summary)) {
        $formattedSummary = nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'));
        $summaryHtml = <<<HTML
        <div style="background: #f8fafc; border-left: 4px solid {$accentColor}; border-radius: 10px; padding: 16px 18px; margin-bottom: 20px; font-size: 15px; line-height: 1.65; color: #1e293b;">
          {$formattedSummary}
        </div>
HTML;
    }

    // Build Precautions HTML
    $precautionsHtml = '';
    if (!empty($precautions)) {
        $itemsHtml = '';
        foreach ($precautions as $p) {
            $pSafe = htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
            $itemsHtml .= "<li style=\"margin-bottom: 6px;\">{$pSafe}</li>";
        }
        $precautionsTitle = $isFilipino ? '🛡️ Mga Inirerekomendang Aksyon sa Pag-iingat' : '🛡️ Recommended Safety Precautions';
        $precautionsHtml = <<<HTML
        <div style="background: #fff8f6; border: 1px solid #ffedd5; border-radius: 12px; padding: 16px 18px; margin-bottom: 20px;">
          <div style="font-size: 13px; font-weight: 800; color: #c2410c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            {$precautionsTitle}
          </div>
          <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #431407; line-height: 1.6;">
            {$itemsHtml}
          </ul>
        </div>
HTML;
    }

    // Build CTA Button HTML
    $ctaHtml = '';
    if (!empty($ctaUrl)) {
        $safeCtaUrl = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');
        $safeCtaLabel = htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8');
        $ctaHtml = <<<HTML
        <!-- CTA Button -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 24px 0 12px 0;">
          <tr>
            <td align="center">
              <a href="{$safeCtaUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-block; width: 100%; max-width: 440px; box-sizing: border-box; background: {$btnBg}; color: #ffffff; text-decoration: none; padding: 14px 24px; border-radius: 12px; font-weight: 700; font-size: 15px; text-align: center; box-shadow: 0 4px 14px rgba(0,0,0,0.15); letter-spacing: 0.2px;">
                {$safeCtaLabel} &rarr;
              </a>
            </td>
          </tr>
        </table>
HTML;
    }

    $issuedLabel = $isFilipino ? 'Inisyu noong' : 'Issued';
    $hotlineLabel = $isFilipino ? 'Hotline ng Emerhensya sa Quezon City:' : 'Quezon City Emergency Hotline:';
    $footerOffice = $isFilipino ? 'Mga Operasyon sa Emerhensya ng AlertaraQC' : 'AlertaraQC Emergency Operations';
    $footerSystem = $isFilipino ? 'Sistemang Pang-abiso at Komunikasyon sa Emerhensya ng AlertaraQC' : 'AlertaraQC Emergency Broadcast & Mass Communication System';
    $footerNotice = $isFilipino ? 'Ito ay isang awtomatikong abiso sa kaligtasan ng publiko. Mangyaring huwag direktang tumugon sa email na ito.' : 'This is an automated public safety advisory. Please do not reply directly to this email.';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$safeTitle}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#334155; -webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding: 28px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background:#ffffff; border-radius: 18px; overflow:hidden; box-shadow:0 12px 32px rgba(0,0,0,0.08); border:1px solid #e2e8f0;">
        
        <!-- Header Banner -->
        <tr>
          <td style="background: {$headerGradient}; padding: 32px 24px 28px 24px; text-align: center;">
            <div style="display:inline-block; padding: 5px 14px; background: {$badgeBg}; border: 1px solid {$badgeBorder}; border-radius: 20px; color: #ffffff; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;">
              {$headerIcon} {$badgeText}
            </div>
            <h1 style="margin:0 0 8px 0; color:#ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.3px; line-height: 1.3;">
              {$safeTitle}
            </h1>
            <p style="margin:0; color: #e2e8f0; font-size: 13px; font-weight: 400;">
              AlertaraQC &bull; {$issuedLabel} {$safeTime}
            </p>
          </td>
        </tr>

        <!-- Main Body Content -->
        <tr>
          <td style="padding: 28px 24px 20px 24px;">
            {$summaryHtml}
            {$metricsHtml}
            {$precautionsHtml}
            {$ctaHtml}
          </td>
        </tr>

        <!-- Emergency Helpline Bar -->
        <tr>
          <td style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px;">
            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td style="font-size: 12px; color: #64748b; line-height: 1.6; text-align: center;">
                  <strong style="color: #0f172a;">📞 {$hotlineLabel}</strong> <a href="https://emergency-comm.alertaraqc.com/USERS/emergency-call.php" target="_blank" rel="noopener noreferrer" style="color: #dc2626; font-weight: 800; text-decoration: underline;">https://emergency-comm.alertaraqc.com/USERS/emergency-call.php</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 18px 24px; text-align:center; font-size: 11px; color: #94a3b8; line-height: 1.6;">
            <p style="margin:0 0 4px 0; font-weight: 700; color: #64748b;">{$footerOffice}</p>
            <p style="margin:0;">{$footerSystem}</p>
            <p style="margin: 6px 0 0 0; color: #cbd5e1; font-size: 10px;">{$footerNotice}</p>
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
