<?php
// api/add_brand_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/marken_helper.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = markeNormalize($data['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name ist erforderlich.']);
    exit;
}

try {
    // Vérifie doublon insensible à la casse
    $stmt = $pdo->prepare("SELECT id, schluessel, name FROM marken WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->execute([$name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode([
            'success'  => true,
            'item'     => $existing,
            'existing' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Utilise le helper pour générer un schluessel propre
    $schluessel = markeMakeSchluessel($name);

    $check = $pdo->prepare("SELECT id FROM marken WHERE schluessel = ? LIMIT 1");
    $check->execute([$schluessel]);
    if ($check->fetch()) {
        $schluessel .= '-' . time();
    }

    $stmt = $pdo->prepare("INSERT INTO marken (schluessel, name) VALUES (?, ?)");
    $stmt->execute([$schluessel, $name]);

    echo json_encode([
        'success' => true,
        'message' => 'Marke erstellt.',
        'item'    => [
            'id'         => (int)$pdo->lastInsertId(),
            'name'       => $name,
            'schluessel' => $schluessel
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}