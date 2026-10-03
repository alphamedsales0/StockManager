<?php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
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
    echo json_encode(["success" => false, "message" => "Ungültige ID."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM positions WHERE id = :id");
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Position nicht gefunden."]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Position gelöscht."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}