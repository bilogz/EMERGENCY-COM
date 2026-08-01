<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/USERS/api/config.env.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$iceServers = [
    ['urls' => 'stun:stun.l.google.com:19302'],
    ['urls' => 'stun:global.stun.twilio.com:3478'],
];

$turnUrl = trim((string) getSecureConfig('WEBRTC_TURN_URL', ''));
$turnUsername = trim((string) getSecureConfig('WEBRTC_TURN_USERNAME', ''));
$turnCredential = trim((string) getSecureConfig('WEBRTC_TURN_CREDENTIAL', ''));

// Use the live gitignored ADMIN configuration as the final fallback. This
// avoids a blank USERS override silently downgrading mobile calls to STUN-only.
if ($turnUrl === '' || $turnUsername === '' || $turnCredential === '') {
    $adminConfigPath = dirname(__DIR__) . '/ADMIN/api/config.local.php';
    if (is_file($adminConfigPath)) {
        $adminConfig = require $adminConfigPath;
        if (is_array($adminConfig)) {
            $turnUrl = $turnUrl !== '' ? $turnUrl : trim((string)($adminConfig['WEBRTC_TURN_URL'] ?? ''));
            $turnUsername = $turnUsername !== '' ? $turnUsername : trim((string)($adminConfig['WEBRTC_TURN_USERNAME'] ?? ''));
            $turnCredential = $turnCredential !== '' ? $turnCredential : trim((string)($adminConfig['WEBRTC_TURN_CREDENTIAL'] ?? ''));
        }
    }
}

if (preg_match('/^turns?:/i', $turnUrl) && $turnUsername !== '' && $turnCredential !== '') {
    $turnUrls = [$turnUrl];
    if (preg_match('/^turns?:(?:\/\/)?([^:\/?]+)/i', $turnUrl, $hostMatch)) {
        $turnHost = strtolower($hostMatch[1]);
        if ($turnHost === 'global.relay.metered.ca') {
            $turnUrls = [
                'turn:' . $turnHost . ':80',
                'turn:' . $turnHost . ':80?transport=tcp',
                'turn:' . $turnHost . ':443',
                'turns:' . $turnHost . ':443?transport=tcp',
            ];
        }
    }
    $iceServers[] = [
        'urls' => array_values(array_unique($turnUrls)),
        'username' => $turnUsername,
        'credential' => $turnCredential,
    ];
}
echo json_encode([
    'success' => true,
    'data' => [
        'iceServers' => $iceServers,
        'turnConfigured' => count($iceServers) > 2,
    ],
], JSON_UNESCAPED_SLASHES);
