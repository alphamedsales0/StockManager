<?php
// api/get_user_permissions.php
// Retourne les permissions de l'utilisateur connecté

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Session — même config que auth_stock.php
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '.alpha-med-care.com',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);
session_name('STOCKMANAGER');
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? null;

try {
    // Récupère les permissions via le rôle (nom du rôle en DB)
    // NOTE: le rôle dans $_SESSION est peut-être "Admin" (casse différente de la DB "admin")
    // On fait un LOWER pour être robuste
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.key_name
        FROM permissions p
        INNER JOIN role_permissions rp ON rp.permission_id = p.id
        INNER JOIN roles r ON r.id = rp.role_id
        WHERE LOWER(r.name) = LOWER(?)
    ");
    $stmt->execute([$userRole]);
    $permissions = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'key_name');

    echo json_encode([
        'success'     => true,
        'user_id'     => $userId,
        'role'        => $userRole,
        'permissions' => $permissions,
        'count'       => count($permissions)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}