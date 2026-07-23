<?php

date_default_timezone_set('Asia/Manila');

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CLI only']);
    exit(1);
}

$dryRun = in_array('--dry-run', $argv ?? [], true);
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
$lockPath = $cacheDir . '/bulletin-auto-runner.lock';
$logPath = $cacheDir . '/bulletin-auto-runner.log';
$lock = fopen($lockPath, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo json_encode(['success' => true, 'skipped' => true, 'message' => 'Another bulletin runner is active.']);
    exit(0);
}

function runBulletinChild(string $script, bool $dryRun = false): array
{
    $command = [PHP_BINARY, $script];
    if ($dryRun) $command[] = '--dry-run';
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__);
    if (!is_resource($process)) {
        return ['success' => false, 'message' => 'Unable to start ' . basename($script)];
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $jsonStart = strrpos((string)$stdout, '{"success"');
    $payload = $jsonStart === false ? null : json_decode(substr($stdout, $jsonStart), true);
    if (!is_array($payload)) {
        return [
            'success' => false,
            'message' => trim($stderr) ?: ('Invalid output from ' . basename($script)),
            'exit_code' => $exitCode
        ];
    }
    $payload['exit_code'] = $exitCode;
    return $payload;
}

function appendBulletinRunnerLog(string $path, array $result): void
{
    if (file_exists($path) && filesize($path) > 2 * 1024 * 1024) {
        @rename($path, $path . '.previous');
    }
    file_put_contents($path, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$startedAt = date('Y-m-d H:i:s');
$pagasa = runBulletinChild(__DIR__ . '/pagasa-auto-alert.php', $dryRun);
$phivolcs = runBulletinChild(__DIR__ . '/phivolcs-auto-alert.php', $dryRun);

$worker = ['success' => true, 'skipped' => $dryRun];
if (!$dryRun) {
    $workerResult = runBulletinChild(__DIR__ . '/notification-worker.php', false);
    // The worker emits text rather than JSON in CLI mode; a zero exit is sufficient.
    if (empty($workerResult['success']) && ($workerResult['exit_code'] ?? 1) === 0) {
        $workerResult['success'] = true;
        $workerResult['message'] = 'Notification worker completed.';
    }
    $worker = $workerResult;
}

$result = [
    'success' => !empty($pagasa['success']) && !empty($phivolcs['success']) && !empty($worker['success']),
    'dry_run' => $dryRun,
    'started_at' => $startedAt,
    'finished_at' => date('Y-m-d H:i:s'),
    'pagasa' => $pagasa,
    'phivolcs' => $phivolcs,
    'worker' => $worker
];
appendBulletinRunnerLog($logPath, $result);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

flock($lock, LOCK_UN);
fclose($lock);
exit($result['success'] ? 0 : 1);
