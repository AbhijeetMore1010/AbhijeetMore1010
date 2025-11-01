<?php
// api/admin/categories.php
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

// Ensure category is not null
$where_clauses[] = "category IS NOT NULL AND category != ''";

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT category, COUNT(*) as count
        FROM generated_domains
        $where_sql
        GROUP BY category
        ORDER BY count DESC
    ");
    $stmt->execute($params);

    $category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for Chart.js
    $labels = array_column($category_data, 'category');
    $data = array_column($category_data, 'count');

    echo json_encode(['labels' => $labels, 'data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred while fetching category data.']);
    error_log("Categories API failed: ". $e->getMessage());
}
