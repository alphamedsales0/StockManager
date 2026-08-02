<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once "database_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$id    = (int)($data['id'] ?? 0);
$name  = trim($data['name'] ?? '');
$value = trim($data['value'] ?? '');

if ($id <= 0 || $name === '' || $value === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Ungültige Eingaben."
    ]);
    exit;
}

try {
    // Vérifier existence
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Kategorie nicht gefunden."
        ]);
        exit;
    }

    // Doublon sur value (sauf soi-même)
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE value = :value AND id <> :id LIMIT 1");
    $stmt->execute(['value' => $value, 'id' => $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Technischer Wert existiert bereits."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE categories SET name = :name, value = :value WHERE id = :id");
    $stmt->execute(['name' => $name, 'value' => $value, 'id' => $id]);

    echo json_encode([
        "success" => true,
        "message" => "Kategorie erfolgreich aktualisiert."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Datenbankfehler.",
        "error"   => $e->getMessage()
    ]);
}