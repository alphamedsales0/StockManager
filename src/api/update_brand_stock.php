<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');

if ($id <= 0 || $name === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID und Name erforderlich."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = :name AND id != :id LIMIT 1");
    $stmt->execute(['name' => $name, 'id' => $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Name existiert bereits."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE brands SET name = :name WHERE id = :id");
    $stmt->execute(['name' => $name, 'id' => $id]);

    echo json_encode(["success" => true, "message" => "Marke aktualisiert."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}