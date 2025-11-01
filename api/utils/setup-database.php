<?php
// api/utils/setup-database.php
// This script creates the database and tables.

require_once __DIR__ . '/../config/database.php';

// Temporarily connect without a dbname to create the database
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'domain_generator';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "Database '$dbname' created or already exists.\n";

    // Now connect to the new database to create tables
    $pdo->exec("USE `$dbname`");

    // Read and execute the setup.sql file
    $sql = file_get_contents(__DIR__ . '/../../setup.sql');
    $pdo->exec($sql);

    echo "Tables created successfully.\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
