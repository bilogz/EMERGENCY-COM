<?php
// FCM Notification Helper
// Uses Firebase Cloud Messaging HTTP v1 (service account + OAuth2).
//
// Setup:
// 1) Download a service account JSON from Firebase/Google Cloud.
// 2) Place it on server (outside public web root if possible).
// 3) Set env var FIREBASE_SERVICE_ACCOUNT_FILE to that file path
//    OR update the default path in fcmLoadServiceAccount().
// 4) Set FIREBASE_PROJECT_ID env var (or keep project_id in JSON).

function fcmLoadDotEnvIfPresent() {
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
        }
    }
}

function fcmBase64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function fcmLoadServiceAccount() {
    fcmLoadDotEnvIfPresent();
    $serviceAccountPath = getenv('FIREBASE_SERVICE_ACCOUNT_FILE');
    if (!$serviceAccountPath) {
        throw new RuntimeException('FIREBASE_SERVICE_ACCOUNT_FILE is not set. Configure it in server env or PHP/api/.env.');
    }

    if (!file_exists($serviceAccountPath)) {
        throw new RuntimeException('Firebase service account file not found at: ' . $serviceAccountPath);
    }

    $json = file_get_contents($serviceAccountPath);
    $sa = json_decode($json, true);
    if (!is_array($sa)) {
        throw new RuntimeException('Invalid Firebase service account JSON.');
    }

    if (empty($sa['client_email']) || empty($sa['private_key']) || empty($sa['project_id'])) {
        throw new RuntimeException('Service account JSON is missing required fields.');
    }

    return $sa;
}

function fcmGetAccessToken() {
    static $cachedToken = null;
    static $cachedExpiry = 0;

    $now = time();
    if (!empty($cachedToken) && $cachedExpiry > ($now + 60)) {
        return $cachedToken;
    }

    $sa = fcmLoadServiceAccount();
    $tokenUri = 'https://oauth2.googleapis.com/token';

    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claimSet = [
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $tokenUri,
        'iat' => $now,
        'exp' => $now + 3600
    ];

    $jwtHeader = fcmBase64UrlEncode(json_encode($header));
    $jwtClaimSet = fcmBase64UrlEncode(json_encode($claimSet));
    $unsignedJwt = $jwtHeader . '.' . $jwtClaimSet;

    $signature = '';
    $signed = openssl_sign($unsignedJwt, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
    if (!$signed) {
        throw new RuntimeException('Failed to sign JWT for Firebase OAuth token.');
    }

    $jwt = $unsignedJwt . '.' . fcmBase64UrlEncode($signature);

    $postFields = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('OAuth token request cURL error: ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode >= 400 || empty($json['access_token'])) {
        throw new RuntimeException('Failed to fetch Firebase OAuth token: ' . $response);
    }

    $cachedToken = $json['access_token'];
    $cachedExpiry = $now + (int)($json['expires_in'] ?? 3600);

    return $cachedToken;
}

function sendFCMNotification($title, $body, $topic = 'emergency-room') {
    try {
        fcmLoadDotEnvIfPresent();
        $sa = fcmLoadServiceAccount();
        $projectId = getenv('FIREBASE_PROJECT_ID') ?: $sa['project_id'];
        $accessToken = fcmGetAccessToken();

        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

        $payload = [
            'message' => [
                'topic' => (string)$topic,
                'notification' => [
                    'title' => (string)$title,
                    'body' => (string)$body
                ],
                'data' => [
                    'type' => 'emergency_alert',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'emergency_alerts'
                    ]
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return json_encode(['success' => false, 'message' => 'FCM send cURL error: ' . $err]);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            return json_encode([
                'success' => false,
                'status_code' => $httpCode,
                'message' => 'FCM send failed.',
                'response' => $response
            ]);
        }

        return $response;
    } catch (Throwable $e) {
        return json_encode([
            'success' => false,
            'message' => 'FCM helper error: ' . $e->getMessage()
        ]);
    }
}
?>
