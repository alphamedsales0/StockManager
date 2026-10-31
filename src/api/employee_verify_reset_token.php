<?php
// api/employee_verify_reset_token.php
// GET ?token=XXX → vérifie que le token est valide (utilisé au chargement de la page)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // pas de credentials ici (token dans URL)

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$token = trim($_GET['token'] ?? '');
if ($token === '' || strlen($token) !== 64) {
    http_response_code(400);
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Token ungültig.']);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$tokenHash = hash('sha256', $token);

$stmt = $pdo->prepare("
    SELECT t.id, t.user_id, t.expires_at, t.used_at,
           u.email, e.vorname, e.nachname
    FROM password_reset_tokens t
    JOIN users u ON u.id = t.user_id
    LEFT JOIN employees e ON e.benutzer_id = u.id
    WHERE t.token_hash = ?
    ORDER BY t.id DESC
    LIMIT 1
");
$stmt->execute([$tokenHash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Token nicht gefunden.']);
    exit;
}
if ($row['used_at'] !== null) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Token bereits verwendet.']);
    exit;
}
if (strtotime($row['expires_at']) < time()) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Token abgelaufen.']);
    exit;
}

// Masquer l'email : j***@ex.com
$parts = explode('@', $row['email']);
$local = $parts[0];
$masked = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 1)) . '@' . ($parts[1] ?? '');

$fullName = trim(($row['vorname'] ?? '') . ' ' . ($row['nachname'] ?? ''));

echo json_encode([
    'success'       => true,
    'valid'         => true,
    'email_masked'  => $masked,
    'full_name'     => $fullName,
    'expires_at'    => $row['expires_at'],
]);