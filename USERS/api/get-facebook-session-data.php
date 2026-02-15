<?php
/**
 * Get Facebook Session Data
 * Returns Facebook user data stored in session for form pre-filling
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['facebook_signup'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No Facebook session data found'
    ]);
    exit;
}

$facebookSignup = $_SESSION['facebook_signup'];

// Clear the session data after retrieving it
unset($_SESSION['facebook_signup']);

echo json_encode([
    'success' => true,
    'user' => [
        'name' => $facebookSignup['name'] ?? '',
        'email' => $facebookSignup['email'] ?? ''
    ]
]);
