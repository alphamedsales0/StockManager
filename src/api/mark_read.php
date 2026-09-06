<?php
// api/mark_read.php
error_log("==== mark_read.php called ====");

// --- CORS (wie gehabt) ---
$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- Session ---
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$sameSite = $isSecure ? 'None' : 'Lax';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $sameSite
]);
session_name('STOCKMANAGER');
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// --- Datenbank ---
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';   // 'notification' oder 'message'
$id = (int)($input['id'] ?? 0);

if (empty($type) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
    exit;
}

if ($type === 'notification') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
} elseif ($type === 'message') {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$id, $userId]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unbekannter Typ']);
    exit;
}

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Als gelesen markiert']);
} else {
    echo json_encode(['success' => false, 'message' => 'Keine Änderung oder Eintrag nicht gefunden']);
}