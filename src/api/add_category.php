<?php
// api/add_category.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data  = json_decode(file_get_contents('php://input'), true);
$name  = trim($data['name']  ?? '');
$value = trim($data['value'] ?? '');
$route = trim($data['route'] ?? '');

if ($name === '' || $value === '' || $route === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, Schlüssel und Route sind erforderlich.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM kategorien WHERE schluessel = ? LIMIT 1");
    $stmt->execute([$value]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Dieser Schlüssel existiert bereits.']);
        exit;
    }

    $max = (int)$pdo->query("SELECT COALESCE(MAX(sortierung), 0) FROM kategorien WHERE uebergeordnet_id IS NULL")->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO kategorien (schluessel, bezeichnung, route, sortierung)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$value, $name, $route, $max + 1]);

    echo json_encode([
        'success' => true,
        'message' => 'Kategorie erfolgreich angelegt.',
        'id'      => (int)$pdo->lastInsertId()
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB-Fehler: ' . $e->getMessage()]);
}