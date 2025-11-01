<?php
// api/admin/trending-keywords.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

start_secure_session();
require_admin_login();

// --- Filter Logic ---
$params = [];
$where_clauses = [];

if (!empty($_GET['start_date'])) {
    $where_clauses[] = "timestamp >= :start_date";
    $params[':start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $where_clauses[] = "timestamp <= :end_date";
    $params[':end_date'] = $_GET['end_date'];
}
if (!empty($_GET['tld'])) {
    $where_clauses[] = "tld = :tld";
    $params[':tld'] = $_GET['tld'];
}
if (!empty($_GET['category'])) {
    $where_clauses[] = "category = :category";
    $params[':category'] = $_GET['category'];
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT keyword, COUNT(*) as count
        FROM generated_domains
        $where_sql
        GROUP BY keyword
        ORDER BY count DESC
        LIMIT 20
    ");
    $stmt->execute($params);

    $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($keywords);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred while fetching trending keywords.']);
    error_log("Trending Keywords API failed: " . $e->getMessage());
}
