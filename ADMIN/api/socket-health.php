<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$host = getenv('SOCKET_SERVER_HOST');
if (!is_string($host) || trim($host) === '') {
    $host = '127.0.0.1';
}
$host = trim($host);

$portRaw = getenv('SOCKET_SERVER_PORT');
$port = is_string($portRaw) && trim($portRaw) !== '' ? (int)$portRaw : 3000;
if ($port <= 0 || $port > 65535) {
    $port = 3000;
}

$timeout = 1.5;
$errno = 0;
$errstr = '';
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
$available = is_resource($socket);

if ($available) {
    fclose($socket);
}

echo json_encode([
    'success' => true,
    'available' => $available,
    'host' => $host,
    'port' => $port,
    'error' => $available ? null : ($errstr !== '' ? $errstr : null),
]);

