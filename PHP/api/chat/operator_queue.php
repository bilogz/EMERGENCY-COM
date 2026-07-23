<?php
header('Content-Type: application/json');
http_response_code(410);

echo json_encode([
    'success' => false,
    'message' => 'The operator queue endpoint has been retired. Use the Two-Way Communication conversation APIs instead.',
]);
