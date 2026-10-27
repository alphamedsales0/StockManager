<?php
// api/get_role_permissions.php?id=X

// ============================================================
// SESSION — config ULTRA-STRICTE (avant même cors.php)
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

// ✅ FORCER le nom AVANT tout (contre session.auto_start)
session_name('STOCKMANAGER');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax')
]);

// ✅ Démarrer la session
session_start();

// ✅ MAINTENANT cors.php (qui ne doit PAS appeler session_start)
require_once __DIR__ . '/cors.php';

// ============================================================
// AUTH
// ============================================================
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Nicht authentifiziert',
        'debug'   => [
            'session_id'   => session_id(),
            'session_name' => session_name(),
            'session_keys' => array_keys($_SESSION),
            'cookie'       => $_COOKIE,
            'host'         => $host,
            'origin'       => $origin_check,
        ]
    ]);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'Admin') {
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

$roleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($roleId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing role id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, display_name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Role not found']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, key_name, display_name, category, sort_order
        FROM permissions
        ORDER BY category ASC, sort_order ASC
    ");
    $stmt->execute();
    $allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $assignedIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'permission_id');

    $grouped = [];
    foreach ($allPermissions as $p) {
        $cat = $p['category'] ?: 'Autre';
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = [
            'id'           => (int)$p['id'],
            'key_name'     => $p['key_name'],
            'display_name' => $p['display_name'],
            'granted'      => in_array($p['id'], $assignedIds)
        ];
    }

    echo json_encode([
        'success'       => true,
        'role'          => $role,
        'permissions'   => $grouped,
        'assigned_ids'  => array_map('intval', $assignedIds),
        'total'         => count($allPermissions),
        'granted_count' => count($assignedIds)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}