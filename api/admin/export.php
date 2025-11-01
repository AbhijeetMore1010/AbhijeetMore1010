<?php
// api/admin/export.php
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

// --- Fetch Data ---
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT keyword, domain_name, tld, category, reason, ip_address, user_agent, timestamp FROM generated_domains $where_sql ORDER BY timestamp DESC");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $format = $_GET['format'] ?? 'json';
    $filename = "domain_export_" . date('Y-m-d') . "." . $format;

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        // Add headers
        fputcsv($output, array_keys($data[0] ?? []));
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    } else { // Default to JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'An internal server error occurred during export.']);
    error_log("Export failed: " . $e->getMessage());
}
