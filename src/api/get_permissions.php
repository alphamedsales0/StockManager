<?php
// api/get_permissions.php
// Retourne toutes les permissions disponibles
// ✅ Auth requise + Admin uniquement

require_once __DIR__ . '/cors.php';

// ============================================================
// SESSION — compatible localhost ET production
// ============================================================
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
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax')
]);

session_name('STOCKMANAGER');
session_start();

// ============================================================
// AUTH — obligatoire
// ============================================================
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Nicht authentifiziert',
        'debug'   => [
            'session_keys' => array_keys($_SESSION),
            'host'         => $host,
            'origin'       => $origin_check,
        ]
    ]);
    exit;
}

// ✅ APRÈS
$userRole = strtolower($_SESSION['user_role'] ?? '');
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nur Admin erlaubt']);
    exit;
}

// ============================================================
// DB
// ============================================================
require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, key_name, display_name, category, description, sort_order
        FROM permissions
        ORDER BY category ASC, sort_order ASC, display_name ASC
    ");
    $stmt->execute();
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($permissions as $p) {
        $cat = $p['category'] ?: 'Autre';
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = $p;
    }

    echo json_encode([
        'success'     => true,
        'permissions' => $permissions,
        'grouped'     => $grouped,
        'count'       => count($permissions)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}