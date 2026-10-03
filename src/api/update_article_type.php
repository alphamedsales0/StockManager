<?php
// api/update_article_type.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data  = json_decode(file_get_contents('php://input'), true);
$id    = (int)($data['id'] ?? 0);
$name  = trim($data['name']  ?? '');
$value = trim($data['value'] ?? '');

if (!$id || $name === '' || $value === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

try {
    // Doublon sur schluessel (autre id)
    $stmt = $pdo->prepare("SELECT id FROM artikeltypen WHERE schluessel = ? AND id != ? LIMIT 1");
    $stmt->execute([$value, $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Dieser technische Wert existiert bereits.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE artikeltypen SET schluessel = ?, bezeichnung = ? WHERE id = ?");
    $stmt->execute([$value, $name, $id]);

    echo json_encode(['success' => true, 'message' => 'Artikeltyp aktualisiert.'], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB-Fehler: ' . $e->getMessage()]);
}