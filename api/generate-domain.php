<?php
// api/generate-domain.php
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';

// Function to parse a .env file
function loadDotEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load the .env file
loadDotEnv(__DIR__ . '/.env');

// Get API key from environment variable
$apiKey = getenv('GOOGLE_AI_API_KEY');
$modelName = 'gemini-1.5-flash';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'API key is not configured.']);
    exit;
}

// Get the keyword and TLD from the request
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$tld = isset($_GET['tld']) ? $_GET['tld'] : '.com';

if (empty($keyword)) {
    echo json_encode(['error' => 'Keyword is required']);
    exit;
}

// Prepare the prompt for the AI model
$prompt = "Generate a JSON array of 10 short, creative, brandable domain name objects for the keyword '{$keyword}'. Each object must have three keys: 'domain' (the suggested domain name with the TLD '{$tld}'), 'category' (a relevant category like 'Tech', 'Health', 'E-commerce'), and 'reason' (a brief explanation of why the name is a good fit). Also include a boolean key 'available', true if the domain is likely available, false otherwise.";

// Set up the cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [[
        'parts' => [[
            'text' => $prompt
        ]]
    ]]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute the request
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Process the response
if ($httpcode == 200) {
    $responseData = json_decode($response, true);
    $jsonText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $cleanedJson = trim($jsonText, " \t\n\r\0\x0B`json");
    $suggestions = json_decode($cleanedJson, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($suggestions)) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO generated_domains (keyword, domain_name, tld, category, reason, ip_address, user_agent) VALUES (:keyword, :domain_name, :tld, :category, :reason, :ip_address, :user_agent)"
            );

            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            foreach ($suggestions as $suggestion) {
                $stmt->execute([
                    ':keyword' => $keyword,
                    ':domain_name' => $suggestion['domain'],
                    ':tld' => $tld,
                    ':category' => $suggestion['category'] ?? null,
                    ':reason' => $suggestion['reason'] ?? null,
                    ':ip_address' => $ip_address,
                    ':user_agent' => $user_agent
                ]);
            }
        } catch (PDOException $e) {
            // Log error, but don't block the user from getting suggestions
            error_log("DB insertion failed: " . $e->getMessage());
        }
        echo json_encode($suggestions);
    } else {
        echo json_encode(['error' => 'Failed to parse AI response or response is not an array.']);
    }
} else {
    echo json_encode(['error' => 'Error calling Google AI API.', 'details' => json_decode($response)]);
}
