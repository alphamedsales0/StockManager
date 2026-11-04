<?php
// api/change_password_profile_stock.php
declare(strict_types=1);

$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- SESSION (même config que send_verification_code.php / verify_code.php) ---
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax',
]);
session_name('STOCKMANAGER');
session_start();

require_once __DIR__ . '/database_connect.php';

$response = ['success' => false, 'message' => ''];

// --- Sécurité : le code doit avoir été validé ---
if (empty($_SESSION['verification_ok'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Verifizierung erforderlich.']);
    exit;
}

// --- Récupérer l'email depuis la session ou le body ---
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = $_SESSION['user_email'] ?? ($input['email'] ?? null);
$newPassword = trim($input['new_password'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Keine E-Mail gefunden.']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Das Passwort muss mindestens 8 Zeichen enthalten.']);
    exit;
}

try {
    // --- Vérifier que l'utilisateur existe ---
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden.']);
        exit;
    }

    // --- Mettre à jour le mot de passe ---
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("
        UPDATE users
        SET password_hash = :hash, updated_at = NOW()
        WHERE id = :id
    ");
    $update->execute([
        ':hash' => $hash,
        ':id'   => $user['id'],
    ]);

    // --- Nettoyer la session de vérification ---
    unset($_SESSION['verification_ok']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_code_expiry']);

    echo json_encode([
        'success' => true,
        'message' => 'Passwort erfolgreich geändert.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Interner Fehler beim Aktualisieren des Passworts.',
    ]);
}