<?php
// api/audit_logs_list.php
// Liste les logs d'audit — admin uniquement

header('Content-Type: application/json; charset=utf-8');

$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// --- Session ---
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin_check = $_SERVER['HTTP_ORIGIN'] ?? '';
$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($origin_check, 'localhost') !== false ||
    strpos($origin_check, '127.0.0.1') !== false
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax'),
]);
session_name('STOCKMANAGER');
session_start();

// --- DB ---
require_once __DIR__ . '/database_connect.php';
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Datenbank nicht verfügbar.']));
}

// --- Auth ---
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Nicht authentifiziert.']));
}

// --- Rôle admin uniquement ---
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$role = strtolower($stmt->fetchColumn() ?: '');
if ($role !== 'admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Keine Berechtigung.']));
}

// --- Filtres ---
$limit        = min(200, max(1, (int)($_GET['limit']  ?? 50)));
$offset       = max(0, (int)($_GET['offset'] ?? 0));
$action       = trim((string)($_GET['action']         ?? ''));
$targetUserId = (int)($_GET['target_user_id'] ?? 0);
$actorId      = (int)($_GET['actor_id']       ?? 0);

$where = [];
$params = [];

if ($action !== '') {
    $where[] = 'a.action = ?';
    $params[] = $action;
}
if ($targetUserId > 0) {
    $where[] = 'a.target_type = ? AND a.target_id = ?';
    $params[] = 'user';
    $params[] = $targetUserId;
}
if ($actorId > 0) {
    $where[] = 'a.actor_id = ?';
    $params[] = $actorId;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Total (pour la pagination) ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// --- Liste ---
$sql = "SELECT a.*, u.username, u.email AS actor_email
        FROM audit_logs a
        LEFT JOIN users u ON u.id = a.actor_id
        $whereSql
        ORDER BY a.id DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode([
    'success' => true,
    'total'   => $total,
    'limit'   => $limit,
    'offset'  => $offset,
    'logs'    => $stmt->fetchAll(),
], JSON_UNESCAPED_UNICODE);