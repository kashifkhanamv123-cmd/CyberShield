<?php
// Centralized session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Ensure the session cookie is available across the entire domain
    session_set_cookie_params([
        'path' => '/',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
