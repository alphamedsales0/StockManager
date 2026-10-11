<?php
// api/get_roles.php
// ============================================================
// Liste des rôles
// ============================================================

require_once __DIR__ . '/cors.php';           // ✅ CORS en PREMIER (gère OPTIONS + headers)
require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, display_name, description, is_active, created_at
        FROM roles
        ORDER BY display_name ASC
    ");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'roles'   => $roles,
        'count'   => count($roles)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}