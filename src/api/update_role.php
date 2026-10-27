<?php
// api/update_role.php
// Modifier un rôle — Admin uniquement

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
$name = trim($input['name'] ?? '');
$display_name = trim($input['display_name'] ?? '');
$description = trim($input['description'] ?? '');

if (!$id || !$name || !$display_name) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'ID, Name und Anzeigename sind erforderlich'
    ]);
    exit;
}

// ✅ 7. Normalise le nom en minuscule
$name = strtolower($name);

try {
    // ✅ Vérifie que la nouvelle value n'est pas déjà prise par un autre rôle
    $check = $pdo->prepare("SELECT id FROM roles WHERE LOWER(name) = ? AND id != ?");
    $check->execute([$name, $id]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error'   => 'Rolle mit diesem Namen existiert bereits'
        ]);
        exit;
    }

    // ✅ Vérifie que le rôle existe
    $exists = $pdo->prepare("SELECT id, name FROM roles WHERE id = ?");
    $exists->execute([$id]);
    $existing = $exists->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rolle nicht gefunden']);
        exit;
    }

    // ✅ Interdit de renommer le rôle "admin"
    if (strtolower($existing['name']) === 'admin' && $name !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => 'Der Name der Admin-Rolle kann nicht geändert werden'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE roles
        SET name = ?, display_name = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $display_name, $description, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Rolle aktualisiert'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'DB error: ' . $e->getMessage()
    ]);
}