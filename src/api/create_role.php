<?php
// api/create_role.php
// Créer un nouveau rôle — Admin uniquement

require_once __DIR__ . '/permission_helper.php';

// ✅ 1. CORS + OPTIONS
applyCors();

// ✅ 2. Session (compatible localhost + prod)
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
$name = trim($input['name'] ?? '');
$display_name = trim($input['display_name'] ?? '');
$description = trim($input['description'] ?? '');

if (!$name || !$display_name) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Name und Anzeigename sind erforderlich'
    ]);
    exit;
}

// ✅ 7. Normalise le nom en minuscule
$name = strtolower($name);

try {
    // ✅ Vérifie que le nom n'existe pas déjà
    $check = $pdo->prepare("SELECT id FROM roles WHERE LOWER(name) = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error'   => 'Rolle mit diesem Namen existiert bereits'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO roles (name, display_name, description)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$name, $display_name, $description]);
    $id = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Rolle angelegt',
        'id'      => $id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'DB error: ' . $e->getMessage()
    ]);
}