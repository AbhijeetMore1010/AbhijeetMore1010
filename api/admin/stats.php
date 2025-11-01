<?php
// api/admin/stats.php
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

    // Helper function to execute a query with filters
    function executeQuery($pdo, $sql, $params) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // 1. Total domains generated
    $total_domains = executeQuery($pdo, "SELECT COUNT(*) FROM generated_domains $where_sql", $params)->fetchColumn();

    // 2. Total unique keywords
    $total_keywords = executeQuery($pdo, "SELECT COUNT(DISTINCT keyword) FROM generated_domains $where_sql", $params)->fetchColumn();

    // 3. Most popular TLD
    $top_tld_sql = "SELECT tld, COUNT(*) as count FROM generated_domains $where_sql GROUP BY tld ORDER BY count DESC LIMIT 1";
    $top_tld_result = executeQuery($pdo, $top_tld_sql, $params)->fetch();
    $top_tld = $top_tld_result ? $top_tld_result['tld'] : 'N/A';

    // 4. Average domain length
    $avg_length = executeQuery($pdo, "SELECT AVG(LENGTH(domain_name)) FROM generated_domains $where_sql", $params)->fetchColumn();

    // 5. Number of suggestions generated today (ignoring filters for this specific stat)
    $today_count = $pdo->query("SELECT COUNT(*) FROM generated_domains WHERE DATE(timestamp) = CURDATE()")->fetchColumn();

    $stats = [
        'total_domains' => (int)$total_domains,
        'total_keywords' => (int)$total_keywords,
        'top_tld' => $top_tld,
        'avg_length' => $avg_length ? round($avg_length, 2) : 0,
        'today_count' => (int)$today_count,
    ];

    echo json_encode($stats);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred while fetching stats.']);
    error_log("Stats API failed: " . $e->getMessage());
}
