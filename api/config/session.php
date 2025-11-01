<?php
// api/config/session.php

// Configure secure session parameters
function start_secure_session() {
    $session_name = 'admin_session'; // Set a custom session name
    $secure = isset($_SERVER['HTTPS']); // If the site is served via HTTPS, use secure cookies
    $httponly = true; // This stops JavaScript from being able to access the session id

    // Get current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"],
        $cookieParams["path"],
        $cookieParams["domain"],
        $secure,
        $httponly
    );

    // Sets the session name to the one set above.
    session_name($session_name);
    session_start(); // Start the php session
    session_regenerate_id(); // regenerated the session, delete the old one.
}

// Function to check if an admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// A function to protect pages that require authentication
function require_admin_login() {
    if (!is_admin_logged_in()) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Authentication required. Please log in.']);
        exit;
    }
}
