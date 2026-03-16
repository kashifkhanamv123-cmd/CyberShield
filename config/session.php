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

// CSRF Protection Helpers
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validate_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
