<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once "database_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Ungültige ID."
    ]);
    exit;
}

try {
    // Existenz prüfen
    $stmt = $pdo->prepare("SELECT id FROM article_types WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Artikeltyp wurde nicht gefunden."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM article_types WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        "success" => true,
        "message" => "Artikeltyp erfolgreich gelöscht."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Datenbankfehler.",
        "error" => $e->getMessage()
    ]);
}