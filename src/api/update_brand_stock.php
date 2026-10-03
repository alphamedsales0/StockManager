<?php
// api/update_brand_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');

if (!$id || $name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM marken WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Diese Marke existiert bereits.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE marken SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    echo json_encode(['success' => true, 'message' => 'Marke aktualisiert.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}