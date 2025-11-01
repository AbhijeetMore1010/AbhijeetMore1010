<?php
// api/admin/login.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

start_secure_session();

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is allowed.']);
    exit;
}

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Input validation
if (!isset($data['email']) || !isset($data['password']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid input. Please provide a valid email and password.']);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Password is correct, so start a new session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $email;

        // Login successful
        echo json_encode(['status' => 'success', 'message' => 'Login successful.']);
    } else {
        // Password is not correct
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred.']);
    error_log("Login failed: " . $e->getMessage());
}
