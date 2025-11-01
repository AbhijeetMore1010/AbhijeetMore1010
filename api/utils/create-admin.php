<?php
// api/utils/create-admin.php
// This is a command-line utility to create a new admin user.
// Usage: php create-admin.php

require_once __DIR__ . '/../config/database.php';

// Ensure this script is run from the command line, not a web browser
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Get email and password from the user
echo "Create Admin User\n";
$email = readline("Enter email: ");
$password = readline("Enter password: ");

// Validate input
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format.\n");
}
if (strlen($password) < 8) {
    die("Password must be at least 8 characters long.\n");
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO admin_users (email, password) VALUES (:email, :password)");
    $stmt->execute([':email' => $email, ':password' => $password_hash]);

    echo "Admin user created successfully.\n";
} catch (PDOException $e) {
    // Check if the user already exists (unique constraint violation)
    if ($e->getCode() == 23000) {
        die("Error: An admin with that email already exists.\n");
    } else {
        die("Database error: " . $e->getMessage() . "\n");
    }
}
