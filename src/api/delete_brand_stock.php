<?php
// api/delete_brand_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID fehlt']);
    exit;
}

try {
    // Protection : refuser si utilisée par des articles
    $stmt = $pdo->prepare("SELECT name FROM marken WHERE id = ?");
    $stmt->execute([$id]);
    $name = $stmt->fetchColumn();

    if (!$name) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Marke nicht gefunden']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel WHERE marke = ?");
    $stmt->execute([$name]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Diese Marke wird noch von Produkten verwendet und kann nicht gelöscht werden.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM marken WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Marke gelöscht.'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}