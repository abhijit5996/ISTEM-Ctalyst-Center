<?php

// CORS + JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dotenvPath = realpath(__DIR__ . '/../.env');
$env = [];
if ($dotenvPath && file_exists($dotenvPath)) {
    foreach (file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$dbHost = $_ENV['DB_HOST'] ?? $env['DB_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? $env['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? $env['DB_DATABASE'] ?? 'istem';
$dbUser = $_ENV['DB_USERNAME'] ?? $env['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? $env['DB_PASSWORD'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, intval($_GET['limit'] ?? 10));

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    if (!empty($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $pdo->prepare('SELECT * FROM instruments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($item) {
            echo json_encode(['success' => true, 'data' => $item]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Instrument not found']);
        }
        exit;
    }

    $stmtTotal = $pdo->query('SELECT COUNT(*) FROM instruments');
    $total = (int) $stmtTotal->fetchColumn();

    $offset = ($page - 1) * $limit;
    $stmt = $pdo->prepare('SELECT * FROM instruments ORDER BY id ASC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    exit;
}