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

if (preg_match('/^turns?:/i', $turnUrl) && $turnUsername !== '' && $turnCredential !== '') {
    $iceServers[] = [
        'urls' => $turnUrl,
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
