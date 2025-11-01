<?php
// api/admin/daily-generation.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

start_secure_session();
require_admin_login();

// --- Filter Logic ---
$params = [];
$where_clauses = [];

// Default to the last 30 days if no date range is provided
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-29 days'));
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$where_clauses[] = "timestamp BETWEEN :start_date AND :end_date";
$params[':start_date'] = $start_date;
$params[':end_date'] = $end_date . ' 23:59:59'; // Include the whole end day

if (!empty($_GET['tld'])) {
    $where_clauses[] = "tld = :tld";
    $params[':tld'] = $_GET['tld'];
}
if (!empty($_GET['category'])) {
    $where_clauses[] = "category = :category";
    $params[':category'] = $_GET['category'];
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT DATE(timestamp) as date, COUNT(*) as count
        FROM generated_domains
        $where_sql
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");
    $stmt->execute($params);

    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for Chart.js
    $labels = array_column($daily_data, 'date');
    $data = array_column($daily_data, 'count');

    echo json_encode(['labels' => $labels, 'data' => $data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred while fetching daily generation data.']);
    error_log("Daily Generation API failed: " . $e->getMessage());
}
