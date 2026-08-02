<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID fehlt."]);
    exit;
}

try {
    // Prüfen, ob Marke in Produkten verwendet wird (Annahme: Spalte 'brand' in 'articles')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE brand = (SELECT name FROM brands WHERE id = :id)");
    $stmt->execute(['id' => $id]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Marke wird noch verwendet."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode(["success" => true, "message" => "Marke gelöscht."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}