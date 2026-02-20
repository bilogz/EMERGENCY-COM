<?php
/**
 * Centralized Session Configuration
 * Include this file at the start of any page that needs session access
 */

// Only configure if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    // Check if headers have already been sent
    if (!headers_sent()) {
        // Set session cookie parameters for cross-directory access
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => '/',  // Make cookie available across entire domain
            'domain' => $cookieParams['domain'],
            'secure' => false,  // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    session_start();
}
