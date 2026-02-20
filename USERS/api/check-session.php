<?php
/**
 * Debug script to check current session status
 */

session_start();
header('Content-Type: text/plain');

echo "=== Session Debug ===\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n\n";

echo "Session Variables:\n";
if (empty($_SESSION)) {
    echo "  (empty)\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "  $key = " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
}

echo "\n\nLogin Status Check:\n";
echo "  user_logged_in: " . (isset($_SESSION['user_logged_in']) ? ($_SESSION['user_logged_in'] ? 'TRUE' : 'FALSE') : 'NOT SET') . "\n";
echo "  user_type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'NOT SET') . "\n";
echo "  user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "  user_name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "\n";

echo "\n\nIs Logged In: " . (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true ? 'YES' : 'NO');
