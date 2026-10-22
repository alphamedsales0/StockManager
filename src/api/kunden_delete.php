<?php
// kunden_delete.php
// Supprime un KUNDE (client e-commerce) et toutes ses données liées
// ✅ Contrôle de permission : session STOCKMANAGER, rôle Admin ou Office
// ✅ Suppression en cascade manuelle des tables enfants
// ✅ Transaction PDO avec rollback

// ============================================================
// CORS — identique à auth_stock.php (cookies cross-origin)
// ============================================================
$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// SESSION — mêmes paramètres que auth_stock.php
// ============================================================
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

// ============================================================
// 🔒 CONTRÔLE DE PERMISSION
// ============================================================
$ALLOWED_ROLES = ['Admin', 'Office'];

$currentUserId = $_SESSION['user_id']   ?? null;
$currentRole   = $_SESSION['user_role'] ?? null;

if (!$currentUserId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Nicht eingeloggt.'
    ]);
    exit;
}

if (!in_array($currentRole, $ALLOWED_ROLES, true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Zugriff verweigert. Nur Admin oder Office dürfen Kunden löschen.'
    ]);
    exit;
}

// ============================================================
// DB
// ============================================================
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

// ============================================================
// Récupération ID (GET ?id=... ou POST JSON { id: ... })
// ============================================================
$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$kundeId  = (int)($_GET['id'] ?? $input['id'] ?? 0);

if (!$kundeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Kunden-ID fehlt.']);
    exit;
}

// ============================================================
// SUPPRESSION
// ============================================================
try {
    // Vérifier l'existence
    $stmt = $pdo->prepare("SELECT id, kundennummer FROM kunden WHERE id = ?");
    $stmt->execute([$kundeId]);
    $kunde = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kunde) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Kunde nicht gefunden.']);
        exit;
    }

    $pdo->beginTransaction();

    // Tables enfants (ordre n'a pas d'importance ici)
    $childTables = [
        'kunden_authentifizierung',
        'kunden_personendaten',
        'kunden_gewerbedaten',
        'kunden_identifikation',
        'kunden_klassifizierung',
        'kunden_adressen',
        'kunden_kommunikation',
        'kunden_bankverbindung',
        'kunden_rechnungsdaten',
        'kunden_preisgruppen',
        'kunden_artikelrabatte',
        'kunden_mahngebuehren',
        'kunden_skonto',
        'kunden_bemerkungen',
        'kunden_zusatzfelder',
    ];

    foreach ($childTables as $table) {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE kunde_id = ?");
        $stmt->execute([$kundeId]);
    }

    // Suppression du client principal
    $stmt = $pdo->prepare("DELETE FROM kunden WHERE id = ?");
    $stmt->execute([$kundeId]);

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Kunde gelöscht.',
        'kundennummer' => $kunde['kundennummer'] ?? null
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("kunden_delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}