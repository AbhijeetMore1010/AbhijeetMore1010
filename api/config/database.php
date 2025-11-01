<?php
// api/config/database.php

// Function to establish a secure database connection using PDO
function getDbConnection() {
    // Load environment variables if not already loaded
    if (!getenv('DB_HOST')) {
        // Assuming the .env file is in the 'api' directory
        $dotenvPath = __DIR__ . '/../.env';
        if (file_exists($dotenvPath)) {
            $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                putenv(sprintf('%s=%s', trim($name), trim($value)));
            }
        }
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $dbname = getenv('DB_NAME') ?: 'domain_generator';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // In a real production environment, you would log this error and show a generic message.
        // For development, it's okay to show the error.
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}
