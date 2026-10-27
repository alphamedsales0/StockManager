<?php
// api/update_role_permissions.php

require_once __DIR__ . '/cors.php';

// ============================================================
// SESSION — MÊME config que auth_stock.php
// ============================================================
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($origin, 'localhost') !== false ||
    strpos($origin, '127.0.0.1') !== false
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
// AUTH
// ============================================================
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Nicht authentifiziert',
        'debug'   => [
            'session_keys' => array_keys($_SESSION),
            'host'         => $host,
            'origin'       => $origin,
            'cookie'       => $_COOKIE,
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

$input = json_decode(file_get_contents('php://input'), true);
$roleId = isset($input['role_id']) ? (int)$input['role_id'] : 0;
$permissionIds = $input['permission_ids'] ?? [];

if ($roleId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing role_id']);
    exit;
}

if (!is_array($permissionIds)) {
    $permissionIds = [];
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Role not found']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    if (!empty($permissionIds)) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permissionIds as $pid) {
            $stmt->execute([$roleId, (int)$pid]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'message'  => 'Permissions updated',
        'role_id'  => $roleId,
        'count'    => count($permissionIds)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}