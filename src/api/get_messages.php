<?php
// api/get_messages.php
error_log("==== get_messages.php called ====");

// --- CORS ---
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

// Letzte 5 Nachrichten für den User (als Empfänger)
$stmt = $pdo->prepare("
    SELECT m.id, m.subject, m.message, m.is_read, m.created_at,
           u.name as sender_name
    FROM messages m
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Anzahl ungelesener
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = (int)$stmt->fetchColumn();

// Formatierung
foreach ($messages as &$msg) {
    $msg['formatted_date'] = date('d.m.Y H:i', strtotime($msg['created_at']));
    $msg['preview'] = strlen($msg['message']) > 60 ? substr($msg['message'], 0, 60) . '…' : $msg['message'];
    if (empty($msg['subject'])) {
        $msg['subject'] = 'Kein Betreff';
    }
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'unreadCount' => $unreadCount
]);