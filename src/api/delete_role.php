<?php
// api/delete_role.php
// Supprimer un rôle — Admin uniquement

require_once __DIR__ . '/permission_helper.php';

// ✅ 1. CORS + OPTIONS
applyCors();

// ✅ 2. Session
ensureSession();

// ✅ 3. AUTH : utilisateur connecté
requireAuth();

// ✅ 4. AUTH : Admin uniquement
requireAdmin();

// ✅ 5. DB
require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// ✅ 6. Lecture du body
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int)($input['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

try {
    // ✅ Vérifie que le rôle existe
    $exists = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
    $exists->execute([$id]);
    $role = $exists->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rolle nicht gefunden']);
        exit;
    }

    // ✅ Interdit de supprimer le rôle "admin"
    if (strtolower($role['name']) === 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => 'Die Admin-Rolle kann nicht gelöscht werden'
        ]);
        exit;
    }

    // ✅ Vérifie qu'aucun utilisateur n'a ce rôle
    $usersWithRole = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(role) = ?");
    $usersWithRole->execute([$role['name']]);
    $count = (int)$usersWithRole->fetchColumn();

    if ($count > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error'   => "Diese Rolle ist $count Benutzer(n) zugewiesen. Bitte zuerst zuweisen ändern.",
            'count'   => $count
        ]);
        exit;
    }

    // ✅ Supprime le rôle (les permissions sont supprimées via ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Rolle gelöscht'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'DB error: ' . $e->getMessage()
    ]);
}