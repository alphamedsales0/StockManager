<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['name']) || !isset($input['value'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige Eingabe: name und value erforderlich']);
    exit;
}

$name = trim($input['name']);
$value = trim($input['value']);

if ($name === '' || $value === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name und Wert dürfen nicht leer sein']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM article_types WHERE value = ?");
    $check->execute([$value]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ein Artikeltyp mit diesem Wert existiert bereits']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO article_types (name, value) VALUES (?, ?)");
    $stmt->execute([$name, $value]);
    $id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Artikeltyp angelegt']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
?>