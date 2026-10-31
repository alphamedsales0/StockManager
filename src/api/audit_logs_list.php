<?php
require_once __DIR__ . '/database_connect.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => $isSecure,
    'httponly' => true, 'samesite' => $isSecure ? 'None' : 'Lax',
]);
session_name('ALPHAMEDCARE');
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Nicht authentifiziert.']));
}

// Admin uniquement
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$role = strtolower($stmt->fetchColumn() ?: '');
if ($role !== 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Keine Berechtigung.']));
}

$limit  = min(200, max(1, (int)($_GET['limit'] ?? 50)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$action = $_GET['action'] ?? null;

$sql = "SELECT a.*, u.username, u.email AS actor_email
        FROM audit_logs a
        LEFT JOIN users u ON u.id = a.actor_id";
$params = [];
if ($action) {
    $sql .= " WHERE a.action = ?";
    $params[] = $action;
}
$sql .= " ORDER BY a.id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode([
    'success' => true,
    'logs'    => $stmt->fetchAll(),
], JSON_UNESCAPED_UNICODE);