<?php
// api/add_article_type.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data  = json_decode(file_get_contents('php://input'), true);
$name  = trim($data['name']  ?? '');
$value = trim($data['value'] ?? '');

if ($name === '' || $value === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name und technischer Wert sind Pflichtfelder.']);
    exit;
}

try {
    // Doublon ?
    $stmt = $pdo->prepare("SELECT id FROM artikeltypen WHERE schluessel = ? LIMIT 1");
    $stmt->execute([$value]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Dieser technische Wert existiert bereits.']);
        exit;
    }

    // Ordre max pour le tri
    $max = (int)$pdo->query("SELECT COALESCE(MAX(sortierung), 0) FROM artikeltypen")->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO artikeltypen (schluessel, bezeichnung, sortierung)
        VALUES (:schluessel, :bezeichnung, :sortierung)
    ");
    $stmt->execute([
        ':schluessel'   => $value,
        ':bezeichnung'  => $name,
        ':sortierung'   => $max + 1
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Artikeltyp erfolgreich erstellt.',
        'item'    => [
            'id'    => (int)$pdo->lastInsertId(),
            'name'  => $name,
            'value' => $value
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB-Fehler: ' . $e->getMessage()]);
}