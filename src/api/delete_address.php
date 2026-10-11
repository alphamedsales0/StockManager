<?php
// api/delete_address.php
header('Content-Type: application/json');

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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);
session_name('ALPHAMEDCARE');
session_start();
require_once 'database.php';

if (empty($_SESSION['kunde_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt.']);
    exit;
}
$kundeId = $_SESSION['kunde_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adress-ID fehlt.']);
    exit;
}
$addressId = (int)$input['id'];

try {
    $stmt = $dbh->prepare("DELETE FROM kunden_adressen WHERE id = ? AND kunde_id = ?");
    $stmt->execute([$addressId, $kundeId]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'Adresse gelöscht.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Adresse nicht gefunden oder keine Berechtigung.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}