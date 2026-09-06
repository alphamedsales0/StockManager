<?php
// api/get_notifications.php
error_log("==== get_notifications.php called ====");

// --- CORS (wie in auth_stock.php) ---
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

// --- Session (identisch mit auth_stock.php) ---
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

// Prüfen, ob Benutzer eingeloggt ist
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// --- Datenbankverbindung ---
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
    exit;
}

// Letzte 5 Benachrichtigungen
$stmt = $pdo->prepare("
    SELECT id, type, title, message, is_read, link, 
           DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as formatted_date
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY is_read ASC, created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Anzahl ungelesener
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = (int)$stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unreadCount' => $unreadCount
]);