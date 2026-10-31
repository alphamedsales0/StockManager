<?php
// api/employee_reset_password.php
// POST { token, newPassword } → change le mot de passe après validation du token

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Datenbank nicht verfügbar.']));
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token       = trim((string)($input['token'] ?? ''));
$newPassword = (string)($input['newPassword'] ?? '');

if (strlen($token) !== 64) {
    exit(json_encode(['success' => false, 'message' => 'Token ungültig.']));
}
if (strlen($newPassword) < 8) {
    exit(json_encode(['success' => false, 'message' => 'Passwort muss mindestens 8 Zeichen haben.']));
}
if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    exit(json_encode([
        'success' => false,
        'message' => 'Passwort muss mindestens einen Großbuchstaben und eine Ziffer enthalten.',
    ]));
}

$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare("
    SELECT id, user_id, expires_at, used_at
    FROM password_reset_tokens
    WHERE token_hash = ?
    LIMIT 1
");
$stmt->execute([$tokenHash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['used_at'] !== null) {
    exit(json_encode(['success' => false, 'message' => 'Token ungültig oder bereits verwendet.']));
}
if (strtotime($row['expires_at']) < time()) {
    exit(json_encode(['success' => false, 'message' => 'Token abgelaufen.']));
}

try {
    $pdo->beginTransaction();

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$hash, (int)$row['user_id']]);

    $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?")
        ->execute([(int)$row['id']]);

    $pdo->commit();

    // --- Audit log ---
    audit_log(
        $pdo,
        (int)$row['user_id'],
        'password_reset_completed',
        'user',
        (int)$row['user_id'],
        ['via' => 'magic_link']
    );

    echo json_encode(['success' => true, 'message' => 'Passwort erfolgreich geändert.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[password_reset] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfehler.']);
}