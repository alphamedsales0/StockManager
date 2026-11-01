<?php
// api/audit_logs_actions.php
header('Content-Type: application/json; charset=utf-8');

$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173', 'http://localhost:3000',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax'),
]);
session_name('STOCKMANAGER');
session_start();

require_once __DIR__ . '/database_connect.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false]));
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
if (strtolower($stmt->fetchColumn() ?: '') !== 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false]));
}

$rows = $pdo->query("SELECT action, COUNT(*) AS cnt FROM audit_logs GROUP BY action ORDER BY action")
            ->fetchAll();

echo json_encode(['success' => true, 'actions' => $rows], JSON_UNESCAPED_UNICODE);