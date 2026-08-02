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
$name = trim($data['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name ist erforderlich."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $name]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Diese Marke existiert bereits."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (:name)");
    $stmt->execute(['name' => $name]);

    echo json_encode([
        "success" => true,
        "message" => "Marke erstellt.",
        "item" => ["id" => $pdo->lastInsertId(), "name" => $name]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}