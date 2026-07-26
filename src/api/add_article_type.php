<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once "database_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');
$value = trim($data['value'] ?? '');

if ($name === '' || $value === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Name und technischer Wert sind Pflichtfelder."
    ]);
    exit;
}

try {
    // Prüfen, ob Wert bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM article_types WHERE value = :value LIMIT 1");
    $stmt->execute(['value' => $value]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Dieser technische Wert existiert bereits."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO article_types (name, value)
        VALUES (:name, :value)
    ");
    $stmt->execute(['name' => $name, 'value' => $value]);

    echo json_encode([
        "success" => true,
        "message" => "Artikeltyp erfolgreich erstellt.",
        "item" => [
            "id" => $pdo->lastInsertId(),
            "name" => $name,
            "value" => $value
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Datenbankfehler.",
        "error" => $e->getMessage()
    ]);
}